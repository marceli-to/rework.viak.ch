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

## The invoice's shape — the real question is granularity

Two things had to be answered before this could be designed: VAT treatment
(2026-09-14, above) and whether a non-student may buy a licence (2026-09-17: yes,
anyone — `05-licences.md`). Both are answered. What they do **not** settle is the
shape, and an earlier draft of this section overstated the case for Order/OrderItem.
Correcting that, because it changes the decision.

### What the legacy data actually shows

Three facts, checked against the 2026-09-11 dump:

- **`invoices.booking_id` is already nullable**, and `invoices.user_id` already
  exists. An invoice already knows its customer without going through a booking.
- **Invoice is already not 1:1 with booking.** A booking with `has_rental` gets
  *two* invoices — the course and a separate CHF 80 rental. It is booking → many.
- **A basket has never produced a combined invoice.** 35 baskets hold more than
  one booking (three at most), and every booking in them was invoiced separately.
  Legacy's rule is simply **one invoice per item**.

### So relationships do solve the licence case

A polymorphic `invoiceable` — `Booking | LicenceOrderItem` — handles everything a
licence throws at this:

- a licence-only order has no booking, and `booking_id` disappears rather than
  going unfilled;
- **the single `vat` column survives**, because each invoice still covers exactly
  one thing with one VAT treatment. That is precisely why legacy bills the rental
  separately — it is the only VAT-bearing item, and splitting it keeps the scalar
  column honest;
- the port stays 1:1 and all 561 invoices map straight across.

The earlier claim that licences force Order/OrderItem, and force VAT onto the
line, was wrong as stated. Both follow only from **one checkout = one invoice**,
which legacy does not do and which nobody has decided.

### The decision, then

| | Invoice per item (polymorphic) | Invoice per order (Order/OrderItem) |
|---|---|---|
| Change | Small — `booking_id` → `invoiceable` | A new aggregate above the invoice |
| VAT | Stays one column per invoice | Must move to the line |
| Port | 561 rows straight across | Each legacy invoice wrapped in a synthetic one-line order |
| Customer buying a course **and** a licence | Two invoices, two QR bills, two payments | One invoice, one bill, one payment |
| Cancelling one item of three | Cancel that invoice | Partial-order logic |

**Recommendation: invoice per order.** Not because licences force it — they do
not — but because the point of the new site is selling licences alongside
courses, and the polymorphic route bills that customer twice. With a CHF 995
licence next to a CHF 1200 course, two separate QR bills for one checkout is a
worse experience than today, and it gets worse as cross-selling is the whole
premise. The cost is line-level VAT and a port that wraps each historical invoice
in a one-line order — both contained, and both cheaper now than retrofitted.

**If the answer is that a checkout may keep producing several invoices, take the
polymorphic route instead** — it is legitimate, materially smaller, and keeps the
single `vat` column. This needs deciding before the schema is written; it is a
business call about how customers pay, not a modelling preference.

### VAT per line, only under invoice-per-order

If the recommendation is taken, a basket holding a course (exempt) and a licence
(8.1 %) puts two treatments on one invoice, and **VAT is computed and stored per
line item with the invoice total as the sum**. Legacy's scalar column cannot
express that; it only ever worked because no mixed invoice has ever existed.
Checked: all 30 `is_rental` rows are a lone CHF 80 net with 6.50 VAT, and
`WHERE is_rental = 0 AND vat <> 0` returns zero rows.

Either way, the 561 historical invoices keep their stored `vat` verbatim on the
port — nothing is recomputed, per the rounding note above.
