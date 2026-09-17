# 03 — Invoices (not yet built)

Placeholder for the money chunk. One finding is recorded here already because it
affects the **live** site and should not wait for the rework.

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
2. **In the rework:** declare `due_at` explicitly nullable, and add a test that
   updating an unrelated invoice column leaves `due_at` alone.
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

### One thing to settle with the client

Invoice payment is offered ("Zahlung per TWINT, Kreditkarte oder Rechnung"), and
licence fulfilment is a human forwarding a key. **Does VIAK order from the
reseller before or after the money arrives?** On a course this does not arise —
the customer attends weeks later. On a licence paid by invoice, dispatching first
means handing over a key that may never be paid for, and dispatching second means
a customer waits on an invoice cycle for something the site implied was quick.
Worth asking; it decides whether fulfilment hangs off *paid* or off *ordered*.
