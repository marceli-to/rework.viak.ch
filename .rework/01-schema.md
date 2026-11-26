# 01 — Users, bookings and the port

People, the seats they hold, and the machinery that moves both out of the legacy
database. Everything money-related stops at the booking — the invoice is
`03-invoices.md`.

## Status

Built. `php artisan port:courses && php artisan port:users` reproduces the
2026-09-11 production data in full: 578 users, 120 addresses, 102 discount codes,
710 bookings, 0 skipped. 66 tests green.

**Two gaps found later, both while rebuilding the course page on 2026-09-21 and
both fixed in the ports** ([[09-public-site]]):

- **`course_videos` was never carried across.** The table is in the legacy
  schema and in none of these documents; 19 of 32 courses have a row, and those
  courses were losing a section of their page. Now a migration, a `CourseVideo`
  model and `PortCourses::portVideos()`.
- **`PortMedia` ignored `images.type`**, hardcoding `is_teaser` and `is_og` to
  false, so legacy's teaser / visual / open-graph distinction was flattened into
  one list of 382 course images. The card showed whichever image sorted first
  and every per-course `og:image` was lost.

Neither was caught by the reconciliation, because both ported the right *number*
of rows. A count check cannot see a column it does not read.

## Shape

| Legacy | Rework | Why |
|---|---|---|
| `users.firstname` + `users.name` | `first_name` + `last_name`, `name` accessor | `name` meant *surname* on User and *full name* on every other model |
| `users.gender_id` → `genders` | `users.gender` enum | Three fixed rows nobody can usefully extend |
| `users.country_id` → `countries.id` | `users.country_code` → `countries.code` | ISO 3166-1 alpha-2 is readable without a join and survives a re-import |
| `users.operating_system` CSV | dropped | Asked at registration, used for nothing; removed by decision on 2026-10-23 |
| `users.expert_*`, `publish`, `visible` | `expert_profiles` 1:1 | 17 of 578 have a bio; the public page should not query the password table |
| `discount_codes.fix` + `.percent` | `discount_codes.type` enum | Two booleans could say "both" and "neither" — states with no meaning |
| `bookings.discount_code` string | `discount_code_id` FK + kept `discount_amount` | All 79 uses resolve, so the link is safe; the amount stays frozen |
| `bookings.invoice_address` HTML | `invoice_address` JSON | A rendered `<br>` fragment cannot be corrected or exported |
| `has_rental` + `config('invoice.cost_rental')` | `has_rental` + frozen `rental_fee` | Added 2026-09-17: the invoice is raised a mean 27.7 days later, so a rate change would rebill the customer ([[03-invoices]]) |

### What is deliberately *not* modelled

- **No total on `bookings`.** A booking records what was *offered*; the invoice
  records what was *charged*. They legitimately disagree — see the findings.
- **`user_documents`** (1,162 rows: 568 invoice PDFs, 594 participation
  confirmations) is not ported. Its two halves belong to `03-invoices.md` and to
  whichever chunk owns generated documents, and the files themselves need a
  storage decision first. Chunk 03 keeps `invoices.filename` so the link back to
  a PDF a customer already holds is not lost in the meantime.

### Legacy uuids are carried across, not regenerated

`uuid` is the route key ([[HasUuid]]), so minting fresh ones would break every
bookmarked and emailed link on cutover day. It is also the join between the
ports, which works on a database built before ids were kept too.

This was a live bug in `port:courses` until now: it created courses and events
with new uuids, and `port:users` could not match a single booking to its event.
All 710 were skipped. Fixed by passing the legacy uuid through, under
`Model::unguarded()` — `uuid` stays out of `$fillable` so it can never be set
from a request.

`locations` is the one legacy table with no uuid; those are newly minted, and
nothing links to a location by uuid.

### Ids: legacy's, since 2026-11-26

Until 2026-11-26 the ports carried uuids and let auto-increment number the
rows, so the rework and the live site named the same row differently: against
the 2026-09-11 dump 566 of 573 users, all 569 invoices and 200 of 344 events
had another id. **That would have misfiled customers in the books:** Run My
Accounts knows every customer as `VIAK_{user id}`
(`../viak.ch/app/Actions/RMA/CreateInvoice.php:52`, [[RunMyAccounts]]), so an
existing customer's next invoice would have gone to someone else's number.

Every row that comes from one legacy row now keeps its id: users, addresses,
discount codes, bookings, courses, course videos, events, locations, the
taxonomies, invoices, messages, user documents. Rows made later continue above
legacy's highest. Not `media` (it merges legacy's `images` and `files`, whose
ids overlap), nor rows legacy has no table for (`event_dates` is rebuilt from
its own, expert profiles come out of `users`). Rehearsed on an empty database
2026-11-26: id and uuid agree with legacy for every ported row.

### Order: `port:users` twice

`port:courses` links each event to its Kursleitung (`event_expert`) and needs
the users for it; `port:users` ports bookings and needs the events. On the
empty database of the cutover, `port:courses` first dropped **every**
Kursleitung link (14 experts, found 2026-11-26 rehearsing; the local database
never showed it because its users were there from an earlier run). So:
`port:users` (people, addresses, codes; it skips the bookings while there are
no events and says so), `port:courses`, `port:users` again (now with the
bookings). Then the rest ([[Deploy]] *The cutover*).

### Content from staging (2026-11-26)

What VIAK edits on staging and the live site does not have comes from staging
at the cutover, on top of the ports (Marcel): the Angebote with their licences
(software, licence products and variants, manufacturers, hosts), the five
categories, and the Seiteninhalte (testimonials, news, Vorhaben, team, the
pages), with their images and logos. Kurse, Veranstaltungen, users, bookings,
invoices and experts are the live site's and come from the ports.

`content:export` on staging writes one folder (`content.json` and `files/`);
`content:import <folder>` on production empties those tables and refills them
with staging's rows **and ids** ([[ContentBundle]], [[ExportContent]],
[[ImportContent]]). Their links to courses (an Angebot's or category's courses,
Vorhaben, recommendations, testimonials about a course) find the course by
uuid, else by slug, and a link to a course production lacks is dropped and
listed. `media` keeps its other rows; the bundle's images get new ids. It
refuses while licence orders exist (their lines point at the variants it
replaces), so it runs before `port:shop`. `--dry-run` shows the counts and the
dropped links. It replaces the `catalogue:*` steps at the cutover except
`catalogue:offers`, which makes the new courses the Angebote link to.
Tested in `tests/Feature/Port/ContentBundleTest.php`; rehearsed 2026-11-26 from
the local database into a freshly ported one.

### Soft-deleted rows are ported, still soft-deleted

The rule: if anything still points at a row, it comes across rather than being
dropped, and keeps its `deleted_at`.

- **19 events** are soft-deleted and 2 carry bookings — including two seats that
  were **paid for** before the event was called off.
- **5 discount codes** are soft-deleted and 1 had been spent: booking 000640
  used a CHF 50 voucher on 2026-04-02 that was deleted on 2026-05-07.

Dropping either would leave a booking pointing at nothing — a seat with no
event, or a CHF 50 discount with nothing to say what it was. Soft-deleting on
the way in keeps the history and still keeps the row out of everything live: a
deleted voucher is not redeemable, because `DiscountCode::validOn()` never sees
it. `tests/Feature/Bookings/BookingTest.php` pins both halves of that.

## Data findings from the port

Against the 2026-09-11 dump, `port:users` reports **no findings**: 6
observations and 1 accepted item, nothing outstanding.

The output has three buckets, because lumping them together buries the one
thing that actually needs somebody:

| Bucket | Means | Count |
|---|---|---:|
| **Findings** | Nobody has decided this yet. Blocks cutover. | 0 |
| **Observations** | The data is odd; the design already answers it. | 6 |
| **Accepted** | Looked at, deliberately left alone. | 1 |

| What | Count | Disposition |
|---|---:|---|
| Booking invoiced at half its `course_fee` | 6 | Observation — the invoice is the money |
| Live booking for a past event, never invoiced | 1 | Accepted 2026-09-14 — left as it is |

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
php artisan port:users            # countries, users, addresses, codes
php artisan port:courses          # courses, events, taxonomies, locations
php artisan port:users            # again: now the bookings
php artisan port:users --dry-run  # reconcile only, writes nothing
```

`port:users` ports no bookings while the events table is empty (every booking
points at one); it ports the people and says to run it again after
`port:courses` (*Order*, above). Users are matched on **email** rather than
created blind, so a second run updates them in place and keeps their ids.

Password hashes carry across unchanged, so everyone's existing password keeps
working; Laravel rehashes on next login if the cost has changed.

## Changed since the build — 2026-09-17

One change to `bookings`, driven by a chunk 03 finding rather than anything wrong
with the port: **`rental_fee`, frozen at booking time the way `course_fee` is.**
Decided and built the same day, in chunk 03.

The rental is a bare `has_rental` boolean today and its price is read from config
when the invoice is raised — which happens when the *event confirms*, a mean 27.7
days after the booking and up to 209. A rate change inside that window invoices
the customer at a price they were never quoted. It is the same bug `course_fee`
already guards against, on the same row.

Never triggered, because CHF 80 has never moved. `port:users` fills it from
`config('invoice.rental_fee')` for the 40 historical `has_rental` bookings, which
is right precisely because the price never moved, and `RaiseInvoiceForBooking`
refuses to bill a rental whose frozen price is 0.00 rather than guess.

Full reasoning, and the second item it came with — where a discount lives, now
settled as *on the invoice line* — in `03-invoices.md`.

## Open questions

1. ~~`user_documents` — where do 1,162 generated PDFs live?~~ — **answered
   2026-09-18.** Owner is `08-accounts.md`; the files are all present in
   production (1,005 distinct files behind 1,162 rows) and currently
   **world-readable** under `public/storage`, so the storage decision is a fix
   rather than a preference. Whether the historical ones are *carried* is still
   open, but it is now a policy question rather than a rescue — and the 2023
   participation confirmations need repairing either way (`Todo.md`).
2. The 14 two-digit-year events remain skipped by `port:courses`, and their
   bookings with them. All 14 have zero bookings, so nothing is lost today — but
   restoring them later means re-running `port:users` too. See `Todo.md`.
