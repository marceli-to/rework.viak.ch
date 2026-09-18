# Todo

Open items that are deliberately *not* being solved yet. Each one records enough
detail to act on without redoing the investigation.

---

## Restore the 14 events lost to two-digit years

**Blocks the production migration. Not a chunk 02 decision — the port keeps
skipping and reporting until then. Marcel to confirm the reconstruction before
cutover.**

### What happened

`EventStoreRequest` validated `dates.*` with `required|min:1` — a string-*length*
check, not a date check. A day typed as `25.09.25` is parsed by
`Carbon::createFromFormat('d.m.Y', …)` as **year 25**, giving a large negative
timestamp. That value wins the `min()` in `EventController::store()` that derives
`events.date`, while the row's own `event_dates` entry falls back to the form
default — which is why so many date rows read the day the event was created.

An event stored in year 25 or 26 fails every upcoming-events comparison, so it
was published and then never appeared on the site. None of the 14 took a single
booking. Entered in three sittings: 2025-06-03 (11), 2025-11-13 (1), 2025-11-21 (2).

Fixed forward on the legacy branch `fix/invoice-due-at-auto-update` (commit
`d4974ff`): `date_format:d.m.Y|after:01.01.2000`. That stops new ones. The 14
existing rows are untouched.

### Reconstruction

`events.date + 2000 years` is always **the day that was mistyped**. Any
`event_dates` row that is not the creation date is a correctly-saved other day
of the same course. The affected courses run **Thursday + Friday** (Blender,
Godot) or a **single day** (SketchUp, Twinmotion).

The first four rows are where a real date row survived, and in every one of them
it is exactly the Thursday before / Friday after the mistyped day — which is what
validates the rule for the rest.

| # | Course | Mistyped day | Wkday | Real schedule | Basis |
|---|---|---|---|---|---|
| 202 | Blender Animation | 25.09.2025 | Thu | 25.–26.09. | both rows survived |
| 207 | Blender Rendering | 27.11.2025 | Thu | 27.–28.11. | both rows survived |
| 208 | Blender Animation | 12.12.2025 | Fri | 11.–12.12. | 11.12. survived |
| 211 | Godot Einführung | 05.12.2025 | Fri | 04.–05.12. | 04.12. survived |
| 203 | Blender Modeling | 09.10.2025 | Thu | 09.–10.10. | Thu+Fri pattern |
| 204 | Godot Einführung | 18.09.2025 | Thu | 18.–19.09. | Thu+Fri pattern |
| 205 | Godot Vertiefung | 06.11.2025 | Thu | 06.–07.11. | Thu+Fri pattern |
| 209 | Blender Modeling | 18.12.2025 | Thu | 18.–19.12. | Thu+Fri pattern |
| 210 | Godot Einführung | 04.12.2025 | Thu | 04.–05.12. | Thu+Fri pattern |
| 288 | Twinmotion | 12.03.2026 | Thu | 12.03. (1 day) | course is always 1 day |
| 294 | SketchUp | 26.01.2026 | Mon | 26.01. (1 day) | course is always 1 day |

### Three that need a human, not a rule

- **210 / 211 / 212** all resolve to *Godot Einführungskurs, 4.–5. Dezember 2025*.
  Almost certainly one course entered three times while the form misbehaved,
  not three courses.
- **294 / 295** are two identical SketchUp events on 26.01.2026. Same story.
- **213** lands on a **Saturday**; Blender Modeling has only ever run Thu+Fri.
  Here the day itself looks mistyped, not just the year. Do not restore from
  the data — ask what it was meant to be, or drop it.

Best estimate: ~10 real lost course dates, ~4 duplicate attempts.

### When it gets solved

**At migration, before we go to production.** Nothing in the build depends on the
answer: all 14 are in the past, none took a booking, and none of them has ever
been visible on the site. But the final port that produces the production
database cannot ship with a silent skip in it — whatever we decide has to be
written into `port:courses` and be reproducible on the cutover run.

`port:courses` currently **skips** all 14 and reports them. The two options:

1. Drop them. They are historical, unbooked, and invisible. Cheapest, loses nothing
   a customer ever saw.
2. Restore from the table above, so the course history is complete for reporting.

Either way the port must stop skipping silently once the decision is made — an
explicit drop list or an explicit reconstruction map, not a warning on stderr
that the cutover run is free to ignore.

---

## Fix `invoices.due_at` before the data goes to production

**Blocks the production migration. Same shape as the events above: nothing in the
build is waiting on it, but the port that produces the production database has to
have an answer written into it.**

### What happened

`2023_01_11_133351_alter_invoices_table_add_due_at.php` added `due_at` as a bare
`timestamp`. It was the first TIMESTAMP column in `invoices`, so MySQL applied its
implicit rule and attached `ON UPDATE CURRENT_TIMESTAMP`:

```sql
`due_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

Nothing in the Laravel code asks for it and nothing in the codebase reveals it.
**Any write to an invoice row resets its payment deadline to now.** In the
2026-09-11 dump: all **541 paid invoices** have `due_at` within two hours of
`paid_at`, and every OPEN/OVERDUE invoice claims a deadline of today — bumped
daily by whatever last touched the row, so the deadline can never actually pass.
Invoice 000552 is dated 2026-08-13, marked OVERDUE, and due today. It also means
the legacy branch's *"payment deadline 10 days before event start"* change is
being silently overwritten.

Full write-up in `03-invoices.md`.

### What the migration has to decide

1. ~~**The column itself.**~~ **Done 2026-09-17, in chunk 03.** `due_at` is a
   nullable `date`: MySQL's implicit rule only attaches to TIMESTAMP and
   DATETIME columns, so the bug is unrepresentable rather than merely fixed, and
   `tests/Feature/Invoices/InvoiceTest.php` pins it. **Still to verify on the
   cutover database**, which is the half a test cannot do.
2. **The 541 lost deadlines.** Not recoverable from the `invoices` table. The
   question is whether they matter — for dunning, for the accounting export, for
   a reprinted PDF. If they do, they may be reconstructible from the invoice PDFs
   or from Run My Accounts, which received `duedate` at creation time. That
   reconstruction is migration work and has to happen against the dump we
   actually cut over, so it needs deciding before the port is frozen.
3. **The open and overdue ones.** These are live money. Whatever `due_at` they
   carry at cutover is the day the row was last touched, which is wrong for all
   of them. Either recompute from the booking's event start (the rule the legacy
   branch was reaching for) or carry them over knowingly wrong. Pick one, in the
   port.

   `port:invoices` carries them verbatim today and reports both halves as
   findings on every run — 541 paid deadlines lost, 12 open/overdue with a
   deadline equal to their last write, oldest 000419 dated 2025-05-26 and still
   OPEN. It will keep saying so until somebody decides, which is the point.

### Available earlier, independently

On the **live** site, `ALTER TABLE invoices MODIFY due_at TIMESTAMP NULL DEFAULT
NULL;` stops the bleeding immediately. It does not recover anything already lost,
and it does not remove any of the three decisions above — but every day it waits
is another day of open invoices having their deadline bumped. Worth doing whether
or not the migration is close.

---

## Fix `/expert/finish` on the live site — do not wait for the rework

**Found 2026-09-18 while scoping `08-accounts.md`, and confirmed against
production.** `POST /expert/finish` is unauthenticated, takes a **user uuid from
the request body**, and sets that user's password and `email_verified_at`. The
confirmation token is checked only on the GET that renders the form, never on the
POST, and the uuid is not validated at all — no signature, no expiry, no rate
limit.

User uuids are **public**: `/de/experte/{slug}/{user:uuid}` is a public route and
the experts index links to every one of them. At least one exposed account holds
an admin role (`02-courses-events.md`, users 501 and 2).

The fix is small and belongs in the legacy tree now: resolve the user **from the
token** rather than from the request body, expire the token, and drop the uuid
from the payload. Laravel's signed URLs or Fortify's reset flow both do this
correctly — which is what the rework uses, so this is not work that gets thrown
away.

Four related authorization gaps, none as urgent, are recorded in
`08-accounts.md`: unconfirmed email and password changes, world-readable
generated PDFs, any student reading or posting to any event's message thread, and
any expert downloading any participant list.

**One of those is worth doing at the same time as the fix above**, because it
needs no code: `storage/app/public/files/` holds **239 loose participant-list
PDFs**, 27 MB of names and contact details, written by
`DocumentController::participantsList`, recorded in no table, deleted by nothing,
and served publicly through the `public/storage` symlink. They can be removed
without touching anything that references them, because nothing does.

## Still outstanding on the live site — read first

Two items below need doing in the **legacy** tree, not this one, and neither is
waiting on the rework:

1. **`/expert/finish`** — an unauthenticated account-takeover path. See below.
2. **The 2023 participation confirmations** — 95 students with 404ing download
   links. See below.

A third is housekeeping: **294 loose participant-list PDFs** sit in
`storage/app/public/files/`, 27 MB of names and contact details served through
the `public/storage` symlink and referenced by no database row. They can be
deleted without breaking anything, because nothing points at them.

## Repair the 2023 participation confirmations — two UPDATEs, customer facing

**Found 2026-09-18** reconciling `user_documents` against a production storage
snapshot. Both bugs are confined to 2023 `PARTICIPATION_CONFIRMATION` rows;
invoices and every later year are clean. **No files are lost** — all 1,162
documents exist on disk.

1. **271 rows have a malformed `uri`** — the separator between `files` and the
   user uuid is missing, so `/storage/filesf962c8c4-…/x.pdf` instead of
   `/storage/files/f962c8c4-…/x.pdf`. Every one resolves once the slash is put
   back. **95 students** currently have at least one download link that 404s, and
   have had since 2023.
2. **157 surplus duplicate rows** — 17 file paths carry more than one row, up to
   **37 rows for a single booking**. The 272 rows of 2023 sit on 115 real files.

Together: a student who took a course in 2023 opens *Meine Dokumente* and sees up
to 37 identical entries, none of which download.

`EventParticipationConfirmation` builds the path correctly today, so (1) is an
old defect nobody repaired the rows for. (2) is most likely the legacy `Job`
queue re-running `EventClosedStudent`, whose constructor generates the PDF and
inserts the row as a side effect.

The rework's port normalises the uri and deduplicates on the file rather than the
row (`08-accounts.md`), so this is fixed at cutover regardless — but it is two
statements and two years of broken links, so it is worth doing in the legacy tree
now.

## SEO: redirects, canonical, sitemap

**Blocks the cutover, not the build.** Raised by Marcel on 2026-09-18 — the legacy
URLs are indexed and must survive. The route decision itself is in
`00-foundation.md` under *Public URLs and locale*; what follows is the cutover
work it produces.

### The live domain is `visualisierungs-akademie.ch`

Checked 2026-09-18, because the repository name is misleading. **`viak.ch` 301s to
`visualisierungs-akademie.ch`**, which is the canonical host and the one that is
indexed. Every redirect, canonical tag and sitemap entry below targets that host,
and the `viak.ch` 301 has to keep working.

Three things confirmed on production at the same time:

- `/` and `/de` **both return 200** with an identical `<title>` and no canonical
  tag — the duplicate content is live, not theoretical.
- `/kurse` (unprefixed) returns **404**, so the `/de/` prefix is not optional.
- `/de/kurs/{slug}/{uuid}` is the real detail URL, as linked from `/de/kurse`.

One thing that makes the redirect cheap, from `01-schema.md`: **legacy uuids are
carried across rather than regenerated**, so the 301 can resolve the old URL by
its uuid and look up the current slug.

### The redirect map

About **60 URLs**, which is the good news — 41 courses, ~17 experts with bios, and
a handful of fixed pages. Not thousands.

| From (legacy, indexed) | To |
|---|---|
| `/de/kurs/{slug}/{uuid}` | `/de/kurs/{slug}` — 301 |
| `/de/experte/{slug}/{uuid}` | `/de/experte/{slug}` — 301 |
| `/` | `/de` — canonical, not a redirect |

Everything else (`/de/kurse`, `/de/kontakt`, `/de/experten`,
`/de/individualschulungen`) is preserved as-is and needs nothing.

The uuid form must keep resolving after cutover, so the redirect reads the uuid
and looks up the current slug rather than pattern-matching. Note the legacy quirk
it retires: the uuid resolves and the slug is *ignored*, so
`/de/kurs/anything/{uuid}` renders the course today.

### Three gaps legacy has and the rework should not

Measured on the legacy tree 2026-09-18. None is expensive, and all three are
easier to do while the templates are being rebuilt than afterwards.

1. **No canonical tag anywhere** in `resources/views/web/partials/head.blade.php`.
   `/` and `/de` both serve the homepage with nothing distinguishing them —
   duplicate content on the live site right now.
2. **No `hreflang`.** Costs nothing to emit and matters the day EN ships.
3. **No sitemap.** `public/robots.txt` is `User-agent: *` + `Disallow:` and
   nothing else.

One unrelated thing worth doing at the same time, already noted in
`00-foundation.md`: `head.blade.php` loads a second Typekit kit, `kcs4ept`
(*neuzeit-grotesk*), referenced by no stylesheet. It is a dead render-blocking
request on every page of the live site.

## Other open questions

**Live questions now live in `Open-Questions.md`** — what is still unanswered,
who owes each answer, and what it holds up. What follows is the record of how
things were settled, kept so that no answer has to be re-derived, plus the few
items that are ours rather than the client's.

- ~~VAT on software licences~~ — **answered 2026-09-14**: 8.1 % added to the net
  price, **rounded to the centime** (not the legacy 0.05), and it posts to Run My
  Accounts exactly like a course. Courses stay VAT-exempt. Chunk 03 is unblocked.
  One standing constraint, not a question: the Run My Accounts client stays
  **mocked until cutover** — nothing posts to the client's live accounting from a
  prototype. See `03-invoices.md`.
- ~~Licence fulfilment: manual dispatch or reseller API?~~ — **answered
  2026-09-17: manual, and there is no API.** The customer orders and pays, VIAK
  gets an email, a human orders from the reseller and forwards the licence on.
  Nothing to integrate. Two consequences: *paid but not yet fulfilled* is a normal
  order state that the customer has to be able to see, and the admin needs a
  worklist of outstanding licence orders — an email to `info@` is not a work
  queue. See `05-licences.md`.
- ~~May a non-student buy a licence?~~ — **answered 2026-09-17: yes, anyone.**
  A licence-only order has no booking, so `invoices.booking_id` goes. A
  polymorphic `invoiceable` is enough for that on its own.
- ~~One checkout: one invoice or several?~~ — **answered 2026-09-17: several,
  and it is not negotiable.** Invoices are raised when a course is **confirmed**,
  not at checkout, because a booking does not mean the course will run. Measured:
  430 of 539 invoices are dated after their booking, mean lag 27.7 days, max 209;
  one customer booked two courses the same day and was invoiced 27 days apart. A
  combined invoice would have to be credited when one course is cancelled. A
  licence has no confirmation step and bills at purchase, so a mixed basket
  produces two invoices on two different days by construction.
- ~~Should an invoice have line items?~~ — **decided 2026-09-17: yes.** An
  invoice is a header plus lines and covers what became billable at the same
  moment, so a course and its laptop rental become one invoice instead of two
  bills. VAT moves to the line. **The port merges nothing:** all 561 legacy
  invoices become one-line invoices, the 29 rentals stay separate documents
  (their PDFs and numbers are already with customers, and reconciliation is
  row-by-row), and nothing is recomputed. See `03-invoices.md`.
- ~~Is the cancellation penalty enforced automatically?~~ — **answered
  2026-09-17: yes, and it stays that way.** The rule fires on cancellation; if
  VIAK then cancels the invoice, that is their decision. The data backs it: all
  14 qualifying cancellations were handled correctly — 10 raised a penalty
  invoice at the right rate, 4 were already paid in full in the 100 % window — and
  2 of the 10 were later waived by hand. One thing owed: legacy records a waiver
  as `status = CANCELLED` with a **null** `cancel_reason`, so the rework adds a
  third `CancellationReason` for a deliberate waiver. See `06-bookings.md`.

- ~~Does a discount code discount the order, or each course in it?~~ —
  **answered 2026-09-17: the order.** Legacy promised one and charged the other:
  the basket screen took a fixed CHF 50 off the total, `Booking::create()` took
  CHF 50 off each course. Three baskets, CHF 80 given away. Two consequences, both
  in `06-bookings.md`: a completed checkout becomes a **row** so the discount has
  something to be level with (not an invoicing entity — chunk 03's rejection of
  Order/OrderItem stands), and the discount is **drawn down** by each invoice as
  it is raised, capped at that invoice's net, rather than split proportionally at
  checkout. Draw-down strands nothing on a course that never runs, never edits a
  raised invoice, and makes the −149.00 invoice unrepresentable. Percentage codes
  are unaffected.

- ~~A student who has already paid cancels inside the 50 % window~~ — **answered
  2026-09-17: VIAK corrects it by hand.** No credit-note flow is built. It has
  never occurred in 710 bookings: all four already-paid late cancellations sat in
  the 100 % window, where the full fee was owed anyway. The one thing to keep in
  view is that a correction made *entirely outside* the rework leaves the invoice
  here reading PAID at the full amount, which then disagrees with Run My
  Accounts; cancelling the invoice with a reason and replacing it — the same
  affordance the penalty waiver needs — avoids that for free.
- ~~Are bookmarks worth porting?~~ — **answered 2026-09-17: keep them.** 17 rows
  across the life of the site, so the answer sets the budget rather than the
  question: two model methods on `User` over the existing pivot, two routes, the
  star on a course card, and no place in the SPA's primary navigation.

- **Licence dispatch: before or after payment?** Invoice payment is offered and
  fulfilment is a human forwarding a key. Dispatch first risks handing over a key
  that is never paid for; dispatch second makes the customer wait an invoice cycle.
  Decides whether fulfilment hangs off *paid* or off *ordered*. Client question.
- ~~What a licence actually is~~ — **answered 2026-09-17.** Products have
  **variants** (Einzelplatz / Netzwerk / Studierende) at their own prices; some
  software is **not directly purchasable** — "Preis auf Anfrage" is real and is
  the majority case, so a variant's price is nullable and a priceless variant
  renders as an enquiry; and *Meine Lizenzen* is **purchase history only**. That
  last one removes an entity: no licence record with a validity, no expiry to
  notice, no renewal flow, no "Verwalten", and no licence key stored anywhere —
  VIAK forwards it from their own mailbox. A deliberate departure from the
  mockup; see `05-licences.md` before anyone "fixes" it.
- **Is the Bildung tier in scope, with its Nachweis?** *The one licence question
  still open.* CHF 145/Jahr behind "Nachweis nötig", CTA "Nachweis einreichen" —
  an upload, a human review and an approval before the basket. Build it, treat
  Bildung as an enquiry variant (cheap, probably right for v1), or drop the tier.
  Gates a corner of chunk 05, not the chunk.
- **Licence copy at launch** — the mockups are wireframes and their wording is
  filler, so nothing to decide, but the real copy cannot repeat
  `Twinmotion-Lizenzen.html`'s "Lieferung sofort per E-Mail" (delivery is a human
  at VIAK) or *Meine Lizenzen*'s validity dates (we do not track them).
- **Licence pricing rules** — none yet (asked 2026-09-17). Whether discount codes
  apply to licences, and whether students pay a different price, stay open. Not
  blocking: decide after the shape is settled.
- **Historical invoice due dates** — recoverable from Run My Accounts? Folded
  into the `due_at` migration section above; only matters if dunning or the
  accounting export needs them.
- ~~Roles as a single enum column~~ — **resolved 2026-09-11**: reverted to a pivot.
  The hierarchy would have dropped the two top-listed public experts, who are
  Admin + Expert. See `02-courses-events.md`.
- ~~Poppins vs Effra~~ — **resolved 2026-09-11**: staying with Effra (Typekit
  `bmx5jih`).
- **Legacy quick win:** `head.blade.php` loads Typekit kit `kcs4ept`
  (neuzeit-grotesk), which no stylesheet references — a dead render-blocking
  request on every page. Safe to delete from the live site.
- **Legacy bug: the app cannot run `config:cache`.** 53 runtime `env()` calls in
  `app/` and `routes/`. Every one of the 24 mailables does
  `->from(env('MAIL_FROM_ADDRESS'), env('APP_NAME'))`; `Tasks/Job`,
  `Tasks/ObserveEventState` and `Facades/ParticipantsChange` read `env('MAIL_TO')`
  for the admin recipient; `Facades/NewsletterSubscriber` reads
  `env('MAILCHIMP_TAGS')`. Cache the config on the live site and mail goes out
  from a null address, to a null admin. Nothing to fix urgently — it works because
  the config is never cached — but nobody should "optimise" that deploy without
  moving these to `config()` first. The rework bans `env()` outside `config/`; see
  `00-foundation.md`.
- **Legacy bug: the cancel-or-confirm reminder misses a day whenever the
  scheduler does.** `Tasks/ObserveEventState` matches
  `where('date', now()->addDays(10))` — an exact day. One missed minute-run on the
  wrong day and those events never get their reminder, because nothing asks
  whether the threshold has been passed. Same shape in
  `Facades/ParticipantsChange`, which notifies only on `== $max` / `== $min` /
  `== $min - 1`, so two bookings in one cycle step over the count and the
  notification is lost. The rework compares with `>=` / `<=` and guards with a
  flag. See **Notify on crossing, not on equality** in `00-foundation.md`.
- **Legacy oddity worth a look:** `Providers/EventServiceProvider` maps
  `Registered::class` in `$listen` without importing it, so it resolves to
  `App\Providers\Registered` — a class that does not exist, making the
  `SendEmailVerificationNotification` mapping dead. Auto-discovery of
  `App\Listeners` covers everything else, so nothing visibly broke; worth
  checking whether email verification on registration was ever meant to work.
- **Production PHP version** — the rework is pinned to 8.3. Raise it if production
  runs 8.4.
- ~~English on the public site~~ — **answered 2026-09-16: not in this rework.**
  The admin edits DE only; the data model stays translatable so the decision can
  be reversed cheaply. See `04-content.md`.
- **Will EN ever be implemented?** Still worth asking the client. Nothing waits
  on it — the translatable columns and the `{de, en}` Resource maps are staying
  either way — but a firm never would let a later chunk simplify them away.
- ~~Does the client want to build pages we have not designed?~~ —
  **answered 2026-09-16: no.** The no-Statamic decision in `04-content.md` is
  settled rather than assumed.
