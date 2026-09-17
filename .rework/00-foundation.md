# 00 — Foundation

Scaffold, tooling, environment and the conventions every later chunk depends on.
Nothing product-specific lives here.

## Status

Built. `rework.viak.ch.test` serves, `php artisan test` green, `npm run build` clean,
`composer format:check` clean.

## Goals

- Clean checkout runs with `composer setup` + `composer dev`.
- Laravel 13 + Vue 3 + Tailwind 4 + Vite, on the conventions below.
- MySQL 8 rather than the legacy 5.7.
- Queue on the database driver, cron-driven — replacing the hand-rolled mail queue.
- The legacy app keeps running untouched alongside this one.

## Stack & versions

| | Legacy (viak.ch) | Rework |
|---|---|---|
| PHP | 8.2 | 8.3 (platform-pinned) |
| Laravel | 11 | 13.31 |
| MySQL | 5.7 (:3306) | 8.0.40 (:3307) |
| Frontend | Vue 2.7, Vuex 3, vue-router 3 | Vue 3.5, Pinia 4, vue-router 5 |
| Build | Laravel Mix 5 (webpack) | Vite 8 + laravel-vite-plugin 3 |
| Styling | SCSS, 106 files / 6.8k LOC | Tailwind 4 |
| Tests | PHPUnit/Pest, 24 files | Pest 4 |
| Auth | laravel/ui | Fortify |

Installed: `laravel/fortify`, `spatie/laravel-translatable`, `spatie/laravel-sluggable`,
`spatie/image`. Everything else gets added by the chunk that needs it — dompdf,
swiss-qr-bill, stripe and the excel exporter land with chunks 03/04.

### Why PHP is pinned to 8.3

`composer config platform.php 8.3.31`. The CLI here is 8.4 but Herd serves the site
with 8.3, and without the pin Composer locks a `>= 8.4.1` platform requirement that
the webserver then fails on. Pinning also makes the lockfile reproducible against a
declared target rather than against whatever CLI happens to be installed.

**Open:** what PHP does production actually run? If it is 8.4, raise the pin. This is
the kind of thing that only bites at deploy time.

## Databases

Three, all on MySQL 8 (`127.0.0.1:3307`):

| Database | Purpose |
|---|---|
| `viak_rework` | the new app |
| `viak_rework_test` | test runs |
| `viak_legacy` | copy of live data, imported from the 5.7 `viakch` database |

`viak_legacy` is what chunk 01 migrates *from* and what the port is verified against.
Its copy is current to **2026-09-11**, scrubbed in place — see *Working data* below.
A fresh dump is still required at the cutover rehearsal itself.

### Live data volumes (measured, not estimated)

| | 2025-09-18 | 2026-09-11 |
|---|---:|---:|
| users | 468 | 578 |
| bookings | 543 | 710 |
| invoices | 448 (CHF 313k lifetime; 6 open, 9 overdue) | 561 live + 8 soft-deleted (CHF 380k paid lifetime; 9 open, 3 overdue, 8 cancelled) |
| events | 240 | 360 |
| courses | 35 | 41 |
| laptop-rental invoices (`is_rental`) | 24 | 29 |
| `jobs` (legacy mail queue, never pruned) | 4,523 | 5,964 |

A year of trading adds roughly a quarter to every table and changes nothing about
the approach: 561 invoices can still be migrated and then reconciled **row by row**,
not sampled. See `01-schema.md`.

`is_rental` means a **CHF 80 laptop rental** added to a booking, not a software
licence — `config('invoice.cost_rental')`, offered in the basket to students
without a suitable machine. It is the only thing on the legacy site that has ever
carried VAT.

## Code style conventions

Carried over from `rework.projects.nightnurse.ch`, which these apply to unchanged:

- **Slim controllers.** A method type-hints a FormRequest, calls an Action, returns a
  Resource. No business logic.
- **Action classes** (`app/Actions/`) for write operations. One class per business
  operation, single `execute()`. Verbs: `CreateEvent`, `ConfirmEvent`.
- **FormRequest** for every write endpoint, and for index endpoints that take filters.
- **Resource** classes for every API response. Models are never returned directly.
- **Policies** for authorisation.
- **Enums** for every fixed set. The legacy app has none — states live in
  `spatie/laravel-model-flags` rows and loose strings.
- **No fat models.** Relations, casts, scopes, trivial accessors. Nothing else.
  (Legacy `Event` is 448 LOC with 14 `$appends`; that is the anti-pattern.)
- `declare(strict_types=1);` at the top of every PHP file.
- **Never `env()` outside `config/`.** Legacy has 53 runtime calls in `app/` and
  `routes/` — every one of its 24 mailables does
  `->from(env('MAIL_FROM_ADDRESS'), env('APP_NAME'))` — which means it cannot
  safely run `config:cache`. See `Todo.md`.
- Tabs, per `.editorconfig`. Pint, configured in `pint.json` with its five
  indentation fixers (`indentation_type`, `statement_indentation`,
  `array_indentation`, `method_chaining_indentation`, `heredoc_indentation`)
  switched off — Pint exposes no tab setting, so with those on it rewrites tabs
  to four spaces. With them off it leaves indentation alone and fixes everything
  else. Indentation is therefore the editor's job via `.editorconfig`, not the
  formatter's.

## Application structure

Legacy `app/` has 18 top-level directories, most of them not Laravel's. They are a
2020 house style, and the rework carries almost none of it over. Recorded here so
the question is not reopened once per chunk.

| Legacy | Size | Rework |
|---|---|---|
| `Facades/` | 16 files, 1,204 LOC | `Actions/` — see below |
| `Providers/` | 11 | One. Eight existed only to bind a facade; `Auth`, `Route` and `Broadcast` are Laravel 10 scaffolding that Laravel 11 folded into `bootstrap/app.php` |
| `Helpers/` | 5 | `Support/`. Business logic wearing a helper's name — `PenaltyHelper` — goes to an Action instead |
| `Stores/` | 4 | Pinia on the client. `CourseFilterStore` is client state and disappears; the basket is posted to a FormRequest at checkout and **priced server-side**, never from what the client sent |
| `Tasks/` | 5 | `Jobs/` for work, artisan commands for schedules — see below |
| `Traits/` | 4, all `*Scopes` | `Models/Concerns/` |
| `Services/` | 3 | `Support/`, or the Action that needs it |
| `Events/` + `Listeners/` | 11 + 11 | Kept, under the rule below |

`Support/` is the only home for stateless shared logic. No `Helpers/`, no global
functions, no second bag.

### The facade layer is not facades

`App\Facades\Booking` is a 243-line class of `public static` methods.
`BookingFacade` beside it is the real Laravel facade, whose accessor returns
`'booking'`. `BookingServiceProvider` binds that string to a hardcoded
`new Booking()`. Three files per domain, eight domains.

Both entry points are used interchangeably, sometimes inside one method:
`Booking::create()` calls `BookingFacade::can($event, $user)` — a container
round-trip to reach a static method on the class already executing.

It buys nothing that either half of a facade is for. The binding is not swappable:
it constructs one concrete class and nothing reads a contract. The static methods
cannot be mocked, so tests get the real thing regardless. What is left is 1,204
lines of business logic filed under a name for an indirection that is not
happening.

All of it becomes Actions. The conventions above already implied that; this says
which:

| Legacy facade | LOC | Rework |
|---|---|---|
| `Invoice` | 294 | `Actions/Invoices/*`, `Support/InvoiceNumber`, `Support/Vat` — **built**, chunk 03 |
| `Booking` | 243 | `Actions/Bookings/` — **chunk 06** |
| `RentalInvoice` | 208 | Nothing of its own — see below |
| `Discount` | 165 | `Actions/Discounts/`, with code generation in `Support/` — **chunk 06** |
| `Bookmark` | 82 | Model methods on `User`; 17 rows in three years — **chunk 06** |
| `ParticipantsChange` | 59 | A listener. It is pure notification — **chunk 06** |
| `NewsletterSubscriber` | 41 | Open — is the Mailchimp sync still in scope? |
| `Message` | 32 | Folded into the booking Action — **chunk 06** |

**`RentalInvoice` is a copy of `Invoice`.** The same nine methods —
`findFromBooking`, `findOrCreateFromBooking`, `createFromBooking`, `cancel`,
`delete`, `getGrandTotal`, `getNumber`, `pad`, `getVat` — differing mainly in
which fee they bill and that one sets `is_rental`. 502 lines for one idea. The
line-items decision in `03-invoices.md` removes the reason for the split: a
course and its rental are two lines on one invoice, so there is one set of
Actions. The *port* still keeps the 29 historical rental invoices as separate
documents — that is about not rewriting what customers already hold, not about
the code.

One thing the split hid: `Booking::getNumber()`, `Invoice::getNumber()` and
`RentalInvoice::getNumber()` are the same racy full-table load
(`withTrashed()->get()->last()->number + 1`). `Support/InvoiceNumber` fixes it
for invoices and documents why; bookings still need the same treatment when
their Action is built.

Mapping the eight also turned up the gap that `06-bookings.md` now fills: five of
them — `Booking`, `Discount`, `Bookmark`, `ParticipantsChange`, `Message`, 581
lines between them — had no chunk to land in. The rework could raise an invoice
from a booking before it could make one.

### Notify on crossing, not on equality

Legacy's notifications fire on `==` and nothing else:

- `ParticipantsChange::handle()` — `$bookingsCount == $max`, `== $min`,
  `== $min - 1` (and the last of the three governs an unbraced statement, which
  is correct today and one edit from not being).
- `ObserveEventState` — `where('date', now()->addDays(10))`, an exact day.

Any step that skips the value loses the notification for good: two bookings in
one cycle jump the count past `$max`, a missed scheduler run skips the day, and
nothing catches up because nothing asks whether the threshold has been *passed*.
The rework compares with `>=` / `<=` and guards with a flag, so a late run still
does the right thing and a repeated one does nothing.

### An event, or a direct call

Events are for *other things may want to react* — above all notifications, which
is what all 11 legacy pairs actually do. Anything that must happen, or money is
lost, is called directly from the Action.

`EventConfirmed → RaiseInvoicesOnConfirmation` sits on that line deliberately:
more than one thing hangs off a confirmation — invoices now, the confirmation
mail and its PDF later — and `SetEventState` should not have to know the list. It
is safe because there is exactly one dispatcher. The risk it accepts, and the
reason not to make a habit of it: a listener that silently fails to register is
an invoice that silently is not raised.

### Queue and schedule

Two different things, which legacy conflates into `Tasks/`.

**Work** — mail, PDFs, accounting posts — goes on the database queue. What that
replaces: `$schedule->call(new Job)->everyMinute()` taking `splice(0, 2)` off an
`App\Models\Job` table. Two emails a minute, a hard ceiling; one confirmed
twelve-seat course backs the mailer up for half an hour. On `Throwable` it logs,
writes the exception into an `error` column and sets `processed = 1` — **no
retry**, so a transient SMTP failure loses that mail permanently. Nothing prunes
it: 4,523 rows. Laravel's queue gives retries, backoff and `failed_jobs` for less
code.

**Schedules** — the reminder sweep, the invoice batch — are artisan commands
registered in `routes/console.php`, each with `withoutOverlapping()`. Legacy uses
`$schedule->call(new Invokable)`, which runs the work *inside the scheduler
process*: a slow send delays everything queued behind it, and
`RunInvoiceBatchProcess` runs `everyMinute()` with nothing stopping it
overlapping itself mid-batch.

**Open, and it is a deployment question:** how the worker runs — `queue:work`
under a supervisor, or, with only a crontab, `schedule:run` each minute driving
`queue:work --stop-when-empty --max-time=55`. The second is the honest choice on
shared hosting and is still strictly better than what legacy does. Same
conversation as the production PHP version.

## Directory shape

```
app/
  Actions/            # one class per business operation
  Enums/
  Http/
    Controllers/Api/  # SPA + public API
    Requests/
    Resources/
  Models/
  Policies/
  Schemas/            # admin form field definitions — see 04-content.md
  Jobs/
  Notifications/
  Support/

resources/
  css/                # app.css + partials/{fonts,colors}.css
  js/
    app/              # the dashboard SPA (Vue 3 + Pinia + router)
      components/fields/  # the field kit — see 04-content.md
    site/             # public-site Vue islands (basket, filter, checkout)
  views/
    components/layout/{app,site}.blade.php
    site/             # public marketing pages, Blade + Tailwind
```

### One SPA, not three

Legacy ships three separate Mix bundles — `dashboard` (72 components), `expert` (5),
`student` (7) — over 62 shared components. These collapse into one Vue 3 SPA under
`resources/js/app/` with role-gated routes. The public site stays Blade with Vue
islands, which is effectively what it already is.

## Design tokens

Brand teal is unchanged from the legacy SCSS: `#46baba`. The mockup's derived tones
(`--color-teal-dark: #2d8f8f`, `--color-line`, `--color-paper`) are added in
`resources/css/partials/colors.css`.

**Decided (2026-09-11):** stay with **Effra**, the legacy typeface. The mockup
proposed Poppins; the client declined.

Effra comes from Typekit kit **`bmx5jih`** (400, 500, 700 + italics). Legacy loads a
second kit, `kcs4ept`, from `head.blade.php` — it serves *neuzeit-grotesk* and is
referenced by nothing in the stylesheets. It is a dead render-blocking request on
every page of the live site and is not carried over; worth deleting there too.

## Commands

| | |
|---|---|
| `composer setup` | install, key, migrate, build |
| `composer dev` | serve + queue listener + pail + vite |
| `composer test` | Pest |
| `composer format` / `format:check` | Pint |

## Open questions carried forward

**Blocking the build:**

1. Production PHP version (pin above). Only bites at deploy time.
2. How the queue worker runs in production — supervisor, or cron driving
   `queue:work --stop-when-empty`. Also a deploy-time question; see
   **Queue and schedule** above.

Nothing else blocks the build. Chunk 03 is buildable today and chunk 05's shape
is settled; the one licence question still open — whether the Bildung tier's
"Nachweis" flow is in scope — gates a corner of chunk 05, not the chunk. See
`05-licences.md`.

**Blocking the cutover, not the build** — see `Todo.md` for both:

3. The 14 two-digit-year events: drop or restore.
4. `invoices.due_at` rewrites itself on every UPDATE. The column fix belongs in
   chunk 03; what the port does with the 541 lost and the open/overdue deadlines
   is cutover work.
5. A fresh production dump before the cutover rehearsal.

**Answered:**

6. ~~VAT treatment for software licences~~ — **2026-09-14**: 8.1 % on the net
   price, rounded to the centime, posted to Run My Accounts exactly like a
   course. Courses stay VAT-exempt. See `03-invoices.md`.
7. ~~Poppins vs Effra~~ — **2026-09-11**: Effra, Typekit kit `bmx5jih`.
8. ~~Licence fulfilment: manual dispatch or reseller API?~~ — **2026-09-17**:
   **manual, no API.** The customer orders and pays, VIAK gets an email, a human
   orders from the reseller and sends the licence on. No integration to build.
9. ~~May a non-student buy a licence?~~ — **2026-09-17**: **yes, anyone.** So
   `invoices.booking_id` goes — a polymorphic `invoiceable` carries it.
10. ~~What a licence *is*~~ — **2026-09-17**: products have **variants**; some
    software is **not directly purchasable** ("Preis auf Anfrage") and renders as
    an enquiry; and *Meine Lizenzen* is **purchase history only** — no validity,
    no expiry, no renewal, and we never hold a licence key. See `05-licences.md`.
11. ~~One checkout, one invoice or several?~~ — **2026-09-17**: **several.**
    Invoices are raised when a course is **confirmed**, not at checkout, because a
    booking does not mean the course will run (mean lag 27.7 days in the live
    data). A licence has no confirmation step and bills at purchase. See
    `03-invoices.md`.
12. ~~Invoice line items?~~ — **2026-09-17**: **yes.** An invoice is a header plus
    lines covering what became billable at the same moment; VAT lives on the line.
    The port still maps all 561 legacy invoices to one line each and merges
    nothing. See `03-invoices.md`.

## Working data (updated 2026-09-11)

`viak_legacy` now holds the **2026-09-11 production dump**, scrubbed in place by
`php artisan db:scrub` — 578 users, 710 bookings, 569 invoices, 360 events.

The scrub replaces customer identities and leaves everything reconciliation
compares. Verified by comparing untruncated MD5 fingerprints of the
non-identity columns against a pristine import: identical for invoices,
bookings, events and the non-PII user columns.

Two things that verification caught, worth remembering:

- `GROUP_CONCAT` silently truncates at 1024 bytes. A fingerprint built without
  raising `group_concat_max_len` compares the first few rows and nothing else,
  and will happily report two different databases as identical.
- `invoices.due_at` rewrites itself on any UPDATE — see `03-invoices.md`.
