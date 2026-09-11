# 03 — Invoices (not yet built)

Placeholder for the money chunk. One finding is recorded here already because it
affects the **live** site and should not wait for the rework.

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

## Also unresolved here

Invoice is 1:1 with booking (`invoices.booking_id`), which does not survive
contact with software licences. The Order/OrderItem design belongs in this
chunk. VAT treatment blocks it — see `00-foundation.md`.
