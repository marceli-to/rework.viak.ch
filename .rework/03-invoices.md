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

## Order/OrderItem — unblocked 2026-09-17

Invoice is 1:1 with booking (`invoices.booking_id`), which does not survive
contact with software licences. The Order/OrderItem design belongs in this chunk,
and both things it was waiting on have now been answered:

- **VAT treatment** — 2026-09-14, above.
- **Who may buy a licence** — 2026-09-17: **anyone**, student or not. A
  licence-only order has no booking at all, so `booking_id` is not merely awkward,
  it is unfillable. See `05-licences.md`.

### VAT belongs on the line, not the invoice

Legacy carries **one** `vat` column for the whole invoice and gets away with it
because it has never issued a mixed invoice. The only VAT it charges is the CHF 80
laptop rental, and every rental is billed on an invoice of its own — net `80.00`,
VAT `6.50`, nothing else on it. Checked against the 2026-09-11 dump: all 30
`is_rental` rows (29 live, 1 soft-deleted) are exactly that, and **no non-rental
invoice has ever carried VAT at all** — `WHERE is_rental = 0 AND vat <> 0` returns
zero rows.

A basket holding a course (exempt) and a licence (8.1 %) breaks that. One order,
one invoice, two lines, two treatments.

**Compute and store VAT per line item; the invoice total is the sum.** Splitting
mixed baskets into two invoices to keep a scalar `vat` column would be the legacy
workaround carried forward for no reason.

The 561 historical invoices keep their invoice-level `vat` verbatim on the port —
nothing is recomputed, per the rounding note above.
