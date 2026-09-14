# 01 — Users, bookings and the port

People, the seats they hold, and the machinery that moves both out of the legacy
database. Everything money-related stops at the booking — the invoice is
`03-invoices.md`.

## Status

Built. `php artisan port:courses && php artisan port:users` reproduces the
2026-09-11 production data in full: 578 users, 120 addresses, 102 discount codes,
710 bookings, 0 skipped. 66 tests green.

## Shape

| Legacy | Rework | Why |
|---|---|---|
| `users.firstname` + `users.name` | `first_name` + `last_name`, `name` accessor | `name` meant *surname* on User and *full name* on every other model |
| `users.gender_id` → `genders` | `users.gender` enum | Three fixed rows nobody can usefully extend |
| `users.country_id` → `countries.id` | `users.country_code` → `countries.code` | ISO 3166-1 alpha-2 is readable without a join and survives a re-import |
| `users.operating_system` CSV | `users.operating_systems` JSON array | `"macOS,Windows"` and `"Windows,macOS"` were two different values |
| `users.expert_*`, `publish`, `visible` | `expert_profiles` 1:1 | 17 of 578 have a bio; the public page should not query the password table |
| `discount_codes.fix` + `.percent` | `discount_codes.type` enum | Two booleans could say "both" and "neither" — states with no meaning |
| `bookings.discount_code` string | `discount_code_id` FK + kept `discount_amount` | All 79 uses resolve, so the link is safe; the amount stays frozen |
| `bookings.invoice_address` HTML | `invoice_address` JSON | A rendered `<br>` fragment cannot be corrected or exported |

### What is deliberately *not* modelled

- **No total on `bookings`.** A booking records what was *offered*; the invoice
  records what was *charged*. They legitimately disagree — see the findings.
- **`user_documents`** (1,162 rows: 568 invoice PDFs, 594 participation
  confirmations) is not ported. Its two halves belong to `03-invoices.md` and to
  whichever chunk owns generated documents, and the files themselves need a
  storage decision first.

### Legacy uuids are carried across, not regenerated

`uuid` is the route key ([[HasUuid]]), so minting fresh ones would break every
bookmarked and emailed link on cutover day. It is also the only stable join
between the two ports, since auto-increment ids are not preserved.

This was a live bug in `port:courses` until now: it created courses and events
with new uuids, and `port:users` could not match a single booking to its event.
All 710 were skipped. Fixed by passing the legacy uuid through, under
`Model::unguarded()` — `uuid` stays out of `$fillable` so it can never be set
from a request.

`locations` is the one legacy table with no uuid; those are newly minted, and
nothing links to a location by uuid.

### Soft-deleted events are ported, still soft-deleted

19 legacy events are soft-deleted and 2 of them carry bookings — including two
seats that were **paid for** before the event was called off. Dropping the event
row would take that booking history with it.

## Data findings from the port

Against the 2026-09-11 dump, `port:users` reports 7 findings plus 1 accepted.
None blocks the build.

| Finding | Count | Disposition |
|---|---:|---|
| Booking invoiced at half its `course_fee` | 6 | Reported, not corrected — the invoice is the money |
| Live booking for a past event, never invoiced | 1 | **Accepted 2026-09-14** — left as it is |
| Discount code used on a booking, later deleted | 1 | Amount kept, link dropped |

### The invoice is the money, not the booking

Six bookings were invoiced at exactly half their `course_fee`, with nothing in
`discount_code` or `discount_amount` to say why:

| Booking | `course_fee` | Invoice | Charged |
|---|---:|---|---:|
| 000200 | 499.00 | 000182 | 249.50 |
| 000392 | 1800.00 | 000335 | 900.00 |
| 000396 | 1800.00 | 000336 | 900.00 |
| 000466 | 1295.00 | 000408 | 647.50 |
| 000594 | 499.00 | 000496 | 249.50 |
| 000680 | 549.00 | 000566 | 274.50 |

A half-price arrangement recorded only on the invoice. The port leaves both
sides as they are: the booking is the offer, the invoice is what was paid and
what the books show. This is why `bookings` has no total — a second answer to a
question that already has one is how the two drift apart.

### One booking that was never billed — accepted

Booking **000309**, event 2024-07-08, `course_fee` 0.00, no invoice, not
cancelled. Every other uninvoiced booking is for an event still in the future,
which is normal — the bill goes out closer to the date. This one is two years
past and free.

**Decided 2026-09-14: leave it.** Two years old, nothing to collect, nobody
waiting on it.

The check itself stays, because the *class* of problem is real — a booking for
an event that has happened with no bill against it is money that was never asked
for. 000309 is listed by number in `PortUsers::ACCEPTED_UNINVOICED` and reported
under "known and accepted" rather than as a finding, so a **new** one still
stands out instead of disappearing into a check somebody switched off.

## Reconciliation

Both ports print a table and a findings list rather than a success message.

```
php artisan port:courses          # courses, events, taxonomies, locations
php artisan port:users            # countries, users, addresses, codes, bookings
php artisan port:users --dry-run  # reconcile only, writes nothing
```

`port:users` refuses to run if the events table is empty, since every booking
points at one. Users are matched on **email** rather than created blind:
`port:courses` has already inserted the experts it found on events, and they
must keep the ids those `event_expert` rows point at.

Password hashes carry across unchanged, so everyone's existing password keeps
working; Laravel rehashes on next login if the cost has changed.

## Open questions

1. `user_documents` — where do 1,162 generated PDFs live, and are the historical
   ones worth carrying at all?
2. The 14 two-digit-year events remain skipped by `port:courses`, and their
   bookings with them. All 14 have zero bookings, so nothing is lost today — but
   restoring them later means re-running `port:users` too. See `Todo.md`.
