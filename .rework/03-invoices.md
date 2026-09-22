# 03 — Invoices

The money. Invoices, their lines, the rule that decides *when* one is raised,
and the boundary to VIAK's books.

## Status

Built, 2026-09-17. `php artisan port:invoices` reproduces the 2026-09-11
production data exactly: **569 invoices, 569 lines, every row matching its
legacy self amount for amount** — Σ net 410,455.00, Σ discount 15,251.00,
Σ VAT 195.00, Σ grand total 395,399.00 on both sides. 110 tests green.

Two findings stand, both of them the `due_at` decisions already tracked in
`Todo.md`; the port names them on every run rather than carrying them quietly.

~~**Deferred, deliberately:** the QR-bill PDF and `user_documents`~~ — **the
document is built, 2026-09-22**; see *The invoice PDF and its QR bill*, below.
`user_documents` arrived with chunk 08.

**Still deferred:** the admin invoice worklist, and the checkout trigger (a seat
sold on an already-confirmed event bills at purchase —
[[RaiseInvoiceForBooking]] takes a single booking and is ready for it). Nothing
in the chunk waits on them.

One finding below is about the **live** site and should not wait for the
rework — see *`invoices.due_at` overwrites itself*.

## VAT — answered 2026-09-14

**Rate: 8.1 %, added on top of a net price.** Confirmed by the client against the
existing shop's basket summary: `Gesamtnettosumme 370.00` → `zzgl. 8.1 % MwSt.
29.97` → `Gesamtsumme 399.97`.

This unblocks the VAT half of the chunk. What the legacy system actually does
today, for the port to reproduce or deliberately depart from:

### Courses carry no VAT, and that is on purpose

532 of the 561 live invoices have `vat = 0.00`. Swiss VAT exempts education
(*Bildungsleistungen*), so course sales are outside the tax. The legacy code
hard-zeroes it in `BasketController::getTotals()`:

```php
// @todo: fix vat on event
$vat = round( ( ( $total - $discount ) / 100 * config('invoice.vat_rate')) * 20 ) / 20;
$vat = 0;
```

The computed line is dead — overwritten on the very next statement — and
`Invoice::…` writes `'vat' => 0.00` directly. The `@todo` reads like unfinished
work, but the zero is the correct answer for courses. **Keep the zero in the
rework; drop the dead line and the todo, and say why in a comment** so the next
person does not "fix" it.

### The only VAT the site has ever charged is the laptop rental

`is_rental` is a **CHF 80 laptop rental** attached to a booking, not a software
licence. 29 invoices, every one of them `80.00 + 6.50 = 86.50`.

### Rounding: to the centime — decided 2026-09-14

`RentalInvoice::getVat()` is `round( $amount / 100 * $rate * 20 ) / 20` — round to
the nearest **0.05**. On 80.00 that is 8.1 % = 6.48, stored as **6.50**. The shop
rounds to the centime instead: 370.00 × 8.1 % = 29.97, where 5-centime rounding
would give 29.95.

**The rework computes VAT to the centime.** Swiss practice is that VAT is figured
to the centime and only a *cash* payment total is rounded to 0.05; the legacy
`* 20 ) / 20` is that cash habit applied a step too early. The shop was right.

Two consequences for the build:

- **No 5-centime rounding anywhere in the VAT calculation.** `round($net * $rate,
  2)`. Worth a test with an amount whose centime and 5-centime results differ —
  80.00 is exactly such a case (6.48 vs 6.50), so it doubles as the regression pin.
- **The 29 historical rentals keep their `6.50`.** They are what the customer was
  invoiced and what the books recorded; the port copies `vat` across verbatim and
  never recomputes it. Any reconciliation that recalculates VAT from `total` will
  flag all 29 — that is expected, not a port bug, and the reconciliation should
  say so rather than "fix" them.

## Run My Accounts — answered 2026-09-14

Licence sales **post exactly like course sales** — same entry, no special case.

**Nothing may be posted to Run My Accounts while this is in development.** The
integration is mocked until cutover: a fake client in local/testing, asserted
against in tests, with the real endpoint reachable only from production config.
A prototype that quietly writes into the client's live accounting is the one
mistake here that cannot be undone.

## `invoices.due_at` overwrites itself

```sql
`due_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

`2023_01_11_133351_alter_invoices_table_add_due_at.php` added the column as a
bare `timestamp`. It was the first TIMESTAMP column in the table, so MySQL
applied its implicit rule and attached `ON UPDATE CURRENT_TIMESTAMP`. Nothing in
the Laravel code asks for this and nothing in the codebase reveals it.

**Consequence: any write to an invoice row resets its payment deadline to now.**

In the 2026-09-11 production data:

- **541 of 541 PAID invoices** have `due_at` within two hours of `paid_at`. The
  original deadline is gone on every paid invoice — overwritten at the moment
  the payment was recorded.
- Every OPEN and OVERDUE invoice has `due_at` set to **today**, bumped by
  whatever scheduled task last touched it. Invoice 000552, dated 2026-08-13 and
  marked OVERDUE, currently claims a deadline of today. The deadline moves
  forward every day and can never actually pass.

This undermines the most recent change on the legacy branch — *"set invoice
payment deadline to 10 days before event start"* — which is being silently
overwritten, and it means overdue handling and any printed due date cannot be
trusted.

## What to do

1. **On the live site, soon:** `ALTER TABLE invoices MODIFY due_at TIMESTAMP
   NULL DEFAULT NULL;`. Stops the bleeding. The deadlines already lost are not
   recoverable from this table.
2. **In the rework:** ~~declare `due_at` explicitly nullable, and add a test that
   updating an unrelated invoice column leaves `due_at` alone~~ — **done
   2026-09-17.** It is a `date` column, nullable, and MySQL's implicit rule
   cannot attach to a DATE at all, so the bug is not merely fixed but
   unrepresentable. `tests/Feature/Invoices/InvoiceTest.php` pins it by writing
   an unrelated column and asserting the deadline did not move. Still owed *on
   the cutover database*, which is where the verification has to happen.
3. **Client question:** do the historical due dates matter — for dunning,
   for the accounting export, for anything? If so, they may be reconstructible
   from the invoice PDFs or from Run My Accounts, which received `duedate` at
   creation time.

Items 2 and 3 are **owed at the migration** — the fix has to be verified on the
cutover database, and the open/overdue invoices need a deliberate `due_at` in the
port rather than the today's-date value they will otherwise carry across. Tracked
in `Todo.md`. Item 1 stands on its own and is worth doing on the live site now.

## An invoice is created on confirmation, not at checkout — 2026-09-17

This is the load-bearing fact about the whole chunk and it was not written down
anywhere. From the client: **a booking does not mean the course will run.** An
event is confirmed once it has the numbers, and *that* is when the invoice is
raised. Booking is a commitment; invoicing waits to see whether there is anything
to charge for.

Measured against the 2026-09-11 dump, across the 539 non-rental invoices that
have a booking:

| | |
|---|---:|
| invoiced later than the booking date | **430** |
| invoiced the same day | 109 |
| mean lag | **27.7 days** |
| longest lag | 209 days |

The 109 same-day ones are bookings taken *after* the event had already been
confirmed, which is the same rule seen from the other side.

And the multi-item baskets show exactly why this matters. User 32 booked two
courses on 2023-02-14 and was invoiced on **2023-03-15** and **2023-04-11** —
27 days apart, because the two courses confirmed at different times:

| booked | invoice | invoice date | event |
|---|---|---|---|
| 2023-02-14 | 000014 | 2023-03-15 | 2023-03-24 |
| 2023-02-14 | 000018 | 2023-04-11 | 2023-04-20 |

### So separate invoices are required, not a legacy wart

A combined invoice at checkout is not merely undesirable, it is **impossible**:
at the moment of the basket nobody knows whether either course will run, and
issuing one bill for two courses means re-issuing or crediting it when one of
them is cancelled. The legacy behaviour is correct and the rework keeps it.

**My earlier recommendation of invoice-per-order is withdrawn.** It was reasoned
from the customer's payment experience without knowing the confirmation rule, and
the rule beats the experience.

### Licences bill on a different trigger

A licence has no confirmation step — there is no minimum headcount and nothing to
call off. It is available the moment it is paid for. So the two things in the
catalogue are billed on genuinely different triggers:

| Sold | Invoice raised |
|---|---|
| Course booking | when the **event is confirmed**, often weeks later |
| Software licence | at **purchase** |

A basket holding a course and a licence therefore *has* to produce two invoices,
on different days. That is not a compromise — it falls out of the domain.

## The schema — decided 2026-09-17

**An invoice is a header with line items, and it covers what became billable at
the same moment.**

That rule reproduces legacy's per-course split exactly — two courses confirming
three weeks apart are still two invoices — and stops splitting in the one place
where the split only ever existed to work around the schema.

`invoices.booking_id` goes. The link to what was sold moves down to the line.

### Shape

```
invoices          number, date, due_at, status, user_id,
                  invoice_address, net, vat, grand_total, …

invoice_items     invoice_id
                  type           COURSE | RENTAL | LICENCE
                  itemable       polymorphic, nullable — Booking | LicenceOrderItem
                  description    frozen at issue, not derived at render time
                  net, vat_rate, vat, total
```

The invoice's `net`, `vat` and `grand_total` are the sums of its lines and are
stored, not computed on read — an invoice is a document that was sent, and it has
to keep saying what it said.

`description` is frozen for the same reason. A course renamed in 2027 must not
retitle an invoice issued in 2024.

### VAT on the line

Each line carries its own rate and amount: a course line at 0.00 (exempt), a
rental or licence line at 8.1 % of its net, **rounded to the centime** per the
rule above. The invoice's `vat` is their sum. This is what the decision buys —
a booking with a laptop rental becomes one invoice:

| | net | VAT |
|---|---:|---:|
| Blender Modeling, 12.–13.03. | 600.00 | 0.00 |
| Laptopmiete | 80.00 | 6.50 |
| | **680.00** | **6.50** |

Today that is two invoices, two numbers and two QR bills for one booking.

### The port does not merge anything — this is the important part

**Every legacy invoice becomes one invoice with one line.** All 561 of them,
including the 29 rentals, which stay 29 separate invoices in the rework.

Line items are for invoices the rework *issues*. They are not a licence to
rewrite history:

- those 29 rental invoices were sent as their own documents, with their own
  numbers, and the customer has the PDF;
- `user_documents` holds 568 invoice PDFs that must keep matching their rows;
- reconciliation is row-by-row against the legacy table, and merging pairs of
  invoices would make all 561 uncomparable.

So the port maps `invoices.is_rental = 1` to a single `RENTAL` line and
everything else to a single `COURSE` line, copies `total`, `vat` and
`grand_total` verbatim onto both the line and the header, and recomputes
**nothing**. The 6.50 values that 5-centime rounding produced stay 6.50.

The merged two-line invoice above is what a *new* booking with a rental produces
from cutover onwards. Old and new invoices will legitimately differ in shape, and
reconciliation should expect exactly one line on every ported row.

## Two changes to tables chunk 01 already built — decided 2026-09-17

Both fall out of invoicing on confirmation. **Both decided and built.**

### `bookings.rental_fee` — freeze it, the way `course_fee` is frozen

The bookings migration freezes the course price on purpose:

> What this booking was sold for, captured at booking time. The event's fee can
> change afterwards and this must not follow it.

The laptop rental gets no such treatment. It is a bare `has_rental` boolean, and
the price is read from `config('invoice.cost_rental')` when the invoice is
raised — which, per the confirmation rule above, is a **mean 27.7 days later, and
up to 209**.

**So a booking taken at CHF 80 is invoiced at CHF 90 if the rate changes in
between.** That is precisely the bug `course_fee` exists to prevent, on the same
row, through a window far longer than anyone would guess from reading the code.

It has never bitten because the price has never moved — all 30 `is_rental` rows
are 80.00. That is luck, not design.

```php
$table->decimal('rental_fee', 8, 2)->default(0);  // frozen at booking
```

**Built.** `port:users` sets it from `config('invoice.rental_fee')` for the 40
historical `has_rental` bookings, which is correct because the price has never
changed; the invoice's stored `vat` still governs, and nothing is recomputed.

One consequence worth knowing about: `RaiseInvoiceForBooking` **throws** rather
than bill a rental whose frozen price is 0.00. Both ways of guessing are wrong —
zero gives the laptop away, today's config rate overcharges someone who was
quoted last year's — so it names the booking and lets the other seats on the
course be billed.

### Where the discount lives — on the line

Legacy carried one `discount` column on the invoice header. **Decided
2026-09-17: the discount lives on the line, and the header sums it.**

A discount reduces the taxable base of the thing it was given against, so with
mixed lines an invoice-wide figure cannot be attributed to a VAT rate at all:
CHF 50 off a CHF 680 invoice is exempt if it came off the course and taxable if
it came off the laptop. `vat = round((net − discount) × rate, 2)`, per line.

`bookings.discount_code_id` and `discount_amount` stay frozen where they are —
the discount is part of what was *offered*, and 79 of 79 uses resolve to course
bookings. If codes ever apply to licences (**open question 3**), the line is
already where that discount would land.

**Superseded the same day, in the right direction.** `06-bookings.md` then
decided that a code discounts the **order**, not each course, and that each
invoice **consumes what is left of the checkout's discount, capped at its own
net**. That needs a per-line discount with a per-invoice cap — which is what was
built — so the only thing that changes is where the number is read from:
`RaiseInvoiceForBooking` takes the booking's frozen copy today and will ask the
checkout row once chunk 06 exists. The clamp is already in place, and there is a
test for it.

### One thing to settle with the client

Invoice payment is offered ("Zahlung per TWINT, Kreditkarte oder Rechnung"), and
licence fulfilment is a human forwarding a key. **Does VIAK order from the
reseller before or after the money arrives?** On a course this does not arise —
the customer attends weeks later. On a licence paid by invoice, dispatching first
means handing over a key that may never be paid for, and dispatching second means
a customer waits on an invoice cycle for something the site implied was quick.
Worth asking; it decides whether fulfilment hangs off *paid* or off *ordered*.


## As built — 2026-09-17

### The tables

```
invoices          number, user_id, status, date, due_at, paid_at, cancelled_at,
                  cancellation_reason, replaced_by_invoice_id, invoice_address,
                  net, discount, vat, grand_total, filename

invoice_items     invoice_id, type, itemable (nullable morph), description,
                  reference, position, net, discount, vat_rate, vat, total
```

Three details worth knowing before reading the migration:

- **`due_at` is a `date`, not a `timestamp`.** MySQL's implicit
  `ON UPDATE CURRENT_TIMESTAMP` rule only applies to TIMESTAMP and DATETIME
  columns, so the bug that destroyed every historical deadline cannot recur —
  not "is fixed", cannot recur. A payment deadline is a day anyway, never an
  instant.
- **`cancel_reason` became an enum plus a link.** Legacy wrote English prose
  into a text column and exactly two sentences ever occurred. `Replaced by
  Invoice No. 000182` is now `cancellation_reason = replaced` and
  `replaced_by_invoice_id`, so the penalty flow's 6 pairs are navigable instead
  of greppable.
- **`queued` is gone.** It was a boolean on every invoice driving a pair of
  nightly tasks — one setting it on all pending invoices, one taking a single
  row off the queue per run, so clearing a backlog of twelve took twelve
  nights. Which invoices need asking about is a query (`Invoice::pending()`),
  not state on a document. All 569 rows carry 0 anyway.

### Where the rule lives

| File | What it is for |
|---|---|
| `SetEventState` | dispatches `EventConfirmed` on the transition *into* confirmed — and only that transition |
| `RaiseInvoicesOnConfirmation` | the listener; synchronous, because raising a bill is not a mail's business |
| `RaiseInvoicesForEvent` | one invoice per active seat; failures collected, never thrown at the confirmation |
| `RaiseInvoiceForBooking` | composes the lines and the deadline; idempotent, so re-running picks up what is missing |
| `IssueInvoice` | the only place an invoice is created: number, lines, stored totals, then the books |
| `Vat` | 8.1 % to the centime, and the 80.00 → 6.48 pin |
| `InvoiceNumber` | six digits, never reused, minted under a row lock inside a transaction |
| `AccountingSystem` | the boundary; `FakeAccountingSystem` everywhere but production |
| `SyncInvoiceStatus` + `invoices:sync` | payment comes *from* the books; nobody marks an invoice paid by hand |

Legacy created invoices **inside a Mailable's `build()`** —
`EventConfirmationStudent` raised the invoice while rendering the confirmation
email, and `RentalAdded` and `BookingCancelledWithPenalty` did the same for
theirs. So the document a customer legally owes money against came into
existence when a mail template was rendered, and could be created twice or not
at all depending on the queue. That is the single biggest structural change in
the chunk: confirmation raises the invoice, the mail merely attaches it.

### What the port actually did

```
                 legacy      ported
invoices            569         569   ok
invoice_items       569         569   ok
Σ net        410,455.00  410,455.00   ok
Σ discount    15,251.00   15,251.00   ok
Σ vat            195.00      195.00   ok
Σ grand_total 395,399.00  395,399.00  ok
```

Every legacy invoice matches its ported row amount for amount, field by field —
the reconciliation compares `net`, `discount`, `vat`, `grand_total` and `status`
on all 569 and reports any difference as a finding, because an invoice that
changed in the port is an invoice that no longer matches the PDF the customer
holds.

**The 8 soft-deleted invoices come across still soft-deleted**, which the chunk
01 rule already implied but which has a second reason here: their numbers must
stay taken, or the next invoice this system issues reuses a number that is on a
document somebody already has. `InvoiceNumber::next()` returns `000570` against
the ported data, not `000562`.

Three observations, all settled, reported on every run so nobody "fixes" them:

- 30 rental lines keep a VAT figure this rework would compute differently
  (000248: stored 6.50, centime rounding gives 6.48);
- 19 invoices have a grand total of 0.00 — a fully discounted course, or a fee
  of zero. Real documents with real numbers;
- 10 bookings took a laptop and have no rental invoice (7 cancelled, the rest
  on events never confirmed). Nothing to bill: the rule is
  invoice-on-confirmation, seen from the other side.

And a third finding, added once `06-bookings.md` found it: **invoice 000419 has
a grand total of −149.00 and is still OPEN** — booking 000512 spent a fixed CHF
648 code on a CHF 499 course, because legacy clamped the discount at nothing.
Ported verbatim, like everything else, and reported on every run because it is
live money that needs a human rather than a migration rule. The rework cannot
produce another: a line takes at most what it is worth.

#### One honest caveat about ported descriptions

Legacy had **no description column at all** — the PDF derived the course title
at render time from the live course. So a ported line's `description` is
composed from what the course is called *now*, and can differ from what the PDF
says wherever a course has been renamed since. The PDF in `user_documents` is
the document that was sent; the ported description is a readable label for a row
in a list. Frozen descriptions are a promise the rework can only keep for
invoices it issues itself, and the port is where that stops being invisible.

Ported rental lines read `Mietcomputer für {Kurs}` and ported course lines
`{Kurs}, {Datum}`, matching the two legacy templates. A *newly issued* rental
line says `Laptopmiete` and sits on the course invoice instead of on its own.

### Tests, and what each one is really for

110 green. The ones that exist to stop a specific thing coming back:

- **`due_at` survives an unrelated write** — the bug that cost 541 deadlines.
- **80.00 → 6.48, and 370.00 → 29.97** — centime rounding, against the client's
  own basket summary.
- **a 100 % discount still issues a document, a free event does not** — the two
  halves of a distinction legacy also made, and the reason `grand_total = 0.00`
  is not a bug on 19 rows.
- **a rental rides on its course invoice, taxed alone** — 680.00 net, 6.48 VAT,
  one number, where today it is two invoices and two QR bills.
- **a renamed course does not retitle an issued invoice.**
- **a deleted invoice's number is never reissued.**
- **a discount bigger than the fee lands at 0.00, not at −149.00** — the shape
  of the one negative invoice in the live books.
- **the fake accounting system is bound even with credentials present**, and the
  real client refuses to exist without them. The one mistake here that cannot be
  undone is writing into VIAK's live books from a prototype.

## The invoice PDF and its QR bill — 2026-09-22

The deferred half of this chunk, built. Two packages, both checked against
Laravel 13 and PHP 8.4 before anything was written:

| | |
|---|---|
| `dompdf/dompdf` `^3.1` | HTML to PDF |
| `sprain/swiss-qr-bill` `^5.3` | the Swiss QR bill, legacy's own library |

**`barryvdh/laravel-dompdf` was installed and then removed.** It adds a facade,
a service provider and a 200-line published config over `loadHtml`, `render`
and `output` — and half of that config's defaults are ones this application
contradicts on purpose. [[PdfRenderer]] states every option where the reason
for it is, so the wrapper would only have been a second place to look.

### 273 lines become one renderer

Legacy's PDF layer is `Services/Pdf/Pdf.php`, `Invoice/EventInvoice.php`,
`Invoice/RentalInvoice.php` and `EventParticipationConfirmation.php`. The two
invoice services are **the same 115-line file with two strings changed**, and
each carries a `create()` and an `update()` that are themselves copies of one
another — four near-identical 40-line methods for one job.

They collapse because the rework's data collapsed first: one `Invoice` with
`InvoiceItem`s replaces `Invoice` + `RentalInvoice`, so there is one document
where there were two. A student who rented a laptop used to get **two invoice
numbers and two QR bills for one booking**.

### The reference number was right all along

Worth recording, because the code does not look it. `Invoice/Qr.php` assembles
the payment reference by hand — `esr_customer_id . ' 00000 ' . clientNumber . ' '
. paddedInvoiceNumber`, then a modulo-10 check digit from a lookup table — and
it reads like an ESR reference from the old orange payment slips. It is in fact
a **valid 27-digit QR reference** in the conventional 2-5-5-5-5-5 grouping, and
`QrPaymentReferenceGenerator` produces the identical string.

Checked for three invoice numbers before a line was changed, and pinned in a
test against a **real ported invoice** — `viak-rechnung-02-10-2024-000300.pdf`,
in a customer's hands, prints `00 00000 00000 00000 00000 03009`, which is what
the rework prints and encodes.

So deleting 150 lines of `Qr.php` and 251 lines of hand-laid Blade changes
**400 lines of code and not one character of output**.

### What the library does that legacy does not

- **`StructuredAddress`, because the standard withdrew the combined form.**
  v5 removed `CombinedAddress` outright, so this is not an API rename that could
  be worked around: the creditor's street number, postal code and town are
  separate fields now.
- **The bill is validated.** `getViolations()` is asked before anything renders,
  so a bad IBAN or an out-of-range amount is an exception rather than a slip a
  bank rejects. Legacy never calls it.
- **The debtor is encoded.** Legacy sets no `ultimateDebtor` at all, so a
  scanned bill reaches the bank with no payer while the hand-built HTML prints
  one beside it. The rework encodes the frozen invoice address, or the
  customer's own where there is none.
- The separation line and its *Vor der Einzahlung abzutrennen*, the Swiss cross,
  the corner marks on empty fields, the localised labels — all of it free, all
  of it to specification.

**The 131 historical invoices are the one case that loses something.** Their
`invoice_address` is legacy's rendered HTML fragment, which has no fields to
recover ([[LegacyInvoiceAddress]]), so the QR code gets an empty debtor box —
which is valid, and is printed with corner marks to be written in. A guess would
be a payment arriving from the wrong party. The invoice itself still prints
exactly the text that was billed.

### dompdf against the library's HTML: four rules

`HtmlOutput` lays the slip out for a browser, and almost all of it — tables,
margins, an SVG QR code — renders correctly. **The amount block does not**:
`#qr-bill-payment-part-left` is a floated, fixed-width box and
`#qr-bill-currency` floats again inside it, which dompdf cannot nest. *Währung*
and *Betrag* land on top of each other, and so do `CHF` and the figure.

[[PaymentSlipStyles]] is four rules making those two cells table cells, labelled
as the dompdf quirk they are. Everything the standard governs stays the
library's.

Two more that cost a render each to find, both recorded in the templates:

- **`box-sizing: border-box` is not honoured.** A 210mm sheet with 42mm of
  padding stayed 210mm wide and the padding was added outside it, so every table
  ran off the right edge of the page. Content-box, stating the 168mm column.
- **The page box has to be zero.** The payment slip is **210mm wide by
  specification** and a `@page` margin crops it. The margins moved onto the
  sheet, which lets the slip have the page.

### The QR code is scanned in a test

The last check no amount of reading can make: the finished PDF is rasterised at
200 dpi and the code read back. It decodes to

```
SPC | 0200 | 1 | CH3130000001876763179 | S | Visualisierungs-Akademie Schweiz GmbH
    | Limmatstrasse | 291 | 8005 | Zürich | CH | … | 989.00 | CHF | …
    | QRR | 000000000000000000000003009 | Rechnung 000300 | EPD
```

Everything else can be right while the thing on the paper is unreadable —
scaled, cropped or dithered into something no banking app will take. This is the
only test that says it works.

### Hardening that came with it

- **No network at render time.** Legacy's templates load both Effra faces with
  `url('{{ url("/") }}/assets/fonts/…')` and the letterhead with `asset()`, so
  every render makes three HTTP requests to the application's own public URL. A
  queue worker with no route out, or a wrong `APP_URL`, gets Helvetica and no
  logo — and no error. Everything is a local file now and `isRemoteEnabled` is
  **false**.
- **`isPhpEnabled` is off and says why.** dompdf executes
  `<script type="text/php">` when it is on, and these templates interpolate a
  customer's own address. Legacy has a commented-out page-number script that
  would have turned it on.
- **The document is not a side effect of an email.** `EventConfirmationStudent`
  creates the invoice, renders the PDF and inserts the `user_documents` row
  **while composing a message** ([[08-accounts]]). The Action does it now and
  the mail will attach what it made.
- **Re-rendering replaces.** Legacy's `update()` writes a new file under a name
  built from the invoice's date and does not touch the row, so moving a date
  orphans a PDF and leaves the row pointing at it. `Todo.md` already counts 294
  such orphans on the live site.
- **The private disk.** Legacy writes under the `public/storage` symlink
  ([[08-accounts]], finding 3).

### A finding this confirmed rather than found

Ported invoice 000300 carries `due_at = 17.10.2024`. **The PDF in the
customer's hands says 07.10.2024.** That is `invoices.due_at overwrites itself`
— already written up in `Todo.md` — seen from the other side: the document is
the record of what was actually demanded, and the column no longer agrees with
it.
