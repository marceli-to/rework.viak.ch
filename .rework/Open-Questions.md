# Open questions

Everything still waiting on an answer, in one place, with who owes it and what it
holds up. Answered questions are **not** kept here — they move into the chunk doc
they belong to, and `Todo.md` keeps the struck-through record of how each was
settled.

**Nothing on this list blocks anything that is being built.** Chunk 03 is built
and none of these held it up. Updated 2026-09-24, with the 2026-09-23 mockup
review (22–24 are from it), and 2026-09-29 with the licence catalogue (33–39).

**The live site is not fixed** (Marcel, 2026-09-29). What the rework finds in
legacy, including the `/expert/finish` account-takeover path found 2026-09-18
(`Todo.md`), is recorded as something the rework must not repeat, and goes away
at cutover. None of it is a question here.

`06-bookings.md` was scoped on 2026-09-17, after the legacy facade map showed
five facades with nowhere to land. It raised four client questions and **all four
were answered the same day**, so none of them appear below; they are in the chunk
doc with the reasoning. Nothing in chunk 06 waits on the client.

| # | Question | Owner | Blocks |
|---|---|---|---|
| 1 | Is the Bildung licence tier real? — **half answered 2026-09-29**: EDU licences are real, sold today as hidden products; what is left is 36 | Client | Chunk 05, partly |
| 2 | Licence dispatch before or after payment? | Client | Chunk 05, partly |
| 3 | Discount codes and student pricing on licences? | Client | Nothing — the line already holds a discount |
| 4 | Which of the six Vorhaben are real? — **narrowed 2026-09-23**: the template is settled on Räume (title, text, offer list); the other five were not discussed. **Built 2026-10-07** as a dashboard module, so the count no longer costs work: VIAK creates the ones that are real, with its own copy | Client | Copy only |
| ~~5~~ | ~~What does "image handling (frontend output)" mean?~~ — **answered 2026-09-18**, see 15 | — | — |
| 6 | Will EN ever be implemented? | Client | Nothing — a *never* would let us simplify |
| 7 | Do the historical invoice due dates matter? | Client | Cutover — `port:invoices` reports it every run |
| 8 | Event 213 — what was it meant to be? | Client | Cutover |
| 9 | The other 13 two-digit-year events: drop or restore? | Marcel | Cutover |
| 10 | `due_at` in the port: the 541 lost, and the open/overdue | Marcel | Cutover |
| 11 | What PHP does production run? | Marcel | Deploy |
| ~~12~~ | ~~What is actually in `courses.reviews`?~~ — **answered 2026-09-21: an Elfsight widget embed**, on all 32 non-empty rows, 23 distinct widget ids. Not testimonial data at all. Replaced by 18 | — | — |
| 13 | Is the Mailchimp newsletter sync still in scope? | Client | The homepage footer's signup, built 2026-10-07, only logs until this is answered |
| ~~14~~ | ~~Should an admin cancelling for a student charge the penalty?~~ — **answered 2026-09-24: the admin decides, per cancellation.** See `07-dashboard.md` | — | — |
| ~~15~~ | ~~Medialibrary, or `marceli-to/image-cache`?~~ — **answered 2026-09-18: neither.** Port the media subsystem from `forrerzimmermann.ch` — Glide, one `media` table, crop JSON, `<picture>` with AVIF/WebP. Answers 5 too. See `08-accounts.md` | — | — |
| ~~16~~ | ~~Is a user with financial history ever deleted, or only deactivated?~~ — **answered 2026-09-24: deactivated.** See `07-dashboard.md` | — | — |
| ~~17~~ | ~~Are the historical PDFs carried across?~~ — **settled 2026-09-18: yes, and they are.** `port:documents` carries all 1,005 distinct files, repairing the 271 broken paths on the way. The legacy tree is not repaired (2026-09-29) | — | — |
| ~~18~~ | ~~Do the Elfsight review widgets come across, get replaced, or go?~~ **Answered**: replaced by `Testimonial` (2026-09-23); the course page shows the published testimonials **about** the course, automatically, in their own *Kundenmeinungen* collapsible (Marcel, 2026-10-06) | Marcel | Built |
| 19 | The 67 past courses listed as *Gebuchte Kurse* on the live site | Marcel | **Nothing here** — the rework splits on the date. A live-site tidy-up, or nothing |
| ~~20~~ | ~~Which chunk installs dompdf?~~ — **answered 2026-09-22 by building it**: `dompdf/dompdf` and `sprain/swiss-qr-bill`, both checked against Laravel 13 / PHP 8.4. All three documents exist. See `03-invoices.md` | — | — |
| ~~22~~ | ~~Deleting an event with active bookings tells nobody?~~ — **withdrawn 2026-09-24: the premise was wrong.** Legacy's dashboard refuses the delete while active bookings exist; only the server-side check is missing, in both. Now a rule in `10-mail.md`, *Oddities* | — | — |
| ~~23~~ | ~~A late booker gets every earlier course message, one mail each — keep, digest, or drop?~~ — **answered 2026-09-24 by Marcel: keep it as legacy does.** See `10-mail.md`, *Oddities* | — | — |
| 24 | ~~Where do the new Kontakt and Firmenschulung forms send, and what do they keep?~~ **Answered 2026-10-06**: to `config('mail.admin')` with the sender as Reply-To, no copy to the sender, nothing stored; Cloudflare Turnstile, a honeypot and 5 an hour per IP ([[ContactController]]). Firmenschulung follows the same answer | Marcel | Built for Kontakt |
| ~~26~~ | ~~Admin-created students: a set-password invite?~~ — **answered 2026-09-24: yes.** See `07-dashboard.md` | — | — |
| ~~27~~ | ~~What belongs on the dashboard's landing page?~~ — **answered 2026-09-24: nothing yet; it stays empty.** | — | — |
| 28 | **Card payment for an invoice**: legacy's `/de/zahlung/rechnung/{uuid}` (Stripe). Still wanted? The course confirmation mail links to it; the rework serves a placeholder there (2026-09-29) | Marcel, then the client | Cutover. **Waits for chunk 05**: designed with the licence checkout (Marcel, 2026-09-29) |
| 25 | ~~Firmenschulung's URL, and does it launch before cutover?~~ **Answered 2026-10-06**: `/de/firmenschulung`, `/de/individualschulungen` 301s to it; built | Marcel | Built |
| 29 | Who manages the GTM container `GTM-M3L7WVP` and the ad accounts, VIAK or an agency? | Client | `11-tracking.md` step 2's container changes, and UTM templates on campaigns |
| 30 | Which ad networks, exactly? Google Ads and Meta today; LinkedIn, ChatGPT, others planned? | Client | Nothing in code; which tags go into GTM |
| 31 | The answer options for *Wie wurdest du auf uns aufmerksam?*, and is it optional? | Client | `11-tracking.md` step 4 |
| 32 | That question as a popup (as asked) or a field on the summary step (recommended)? | Client | `11-tracking.md` step 4 |
| 33 | **Prices and article numbers** for the 107 licence products, gross or net | Client | **The catalogue import.** Asked 2026-09-29 |
| 34 | A 3-year licence: fixed price, or 3× annual? | Client | Chunk 05, the term select. Asked 2026-09-29 |
| 35 | What does "buying" a demo mean? | Client | Chunk 05, 10 products. Asked 2026-09-29 |
| 36 | Can a customer order a hidden (EDU) product on the site, or does VIAK only invoice them? | Client | Chunk 05, the *not listed* state. Asked 2026-09-29 |
| 37 | Updates and upgrades: an edition in the dropdown, or their own product? | Client | Nothing structural — the shape takes either. Asked 2026-09-29 |
| 38 | "Nur zusammen mit Neulizenz": enforced by the basket, or a note? | Client | Nothing if a note. Asked 2026-09-29 |
| 39 | Minimum and maximum quantities beyond the Teams licences' 3? | Client | Nothing — `min_quantity` is planned either way. Asked 2026-09-29 |
| ~~40~~ | ~~Time zone: switch to `Europe/Zurich`?~~ — **answered 2026-09-30: yes, and built.** The app runs in Zurich, both connections read and write at `+00:00`, and the port shifts legacy's UTC moments (`LegacyTime`). See `07-dashboard.md`, *Zurich time* | — | — |
| ~~41~~ | ~~A confirmation missed at closing: a per-seat send, or never?~~ — **answered 2026-09-30: per seat, and built.** *Bestätigen* under a *Nicht teilgenommen* badge on a closed date. See `07-dashboard.md`, *Attendance is asked when closing* | — | — |
| ~~42~~ | ~~Kunden instead of Studenten?~~ — **answered 2026-09-30: yes, *Kunde / Kunden*.** See `12-customers.md` | — | — |
| ~~43~~ | ~~The customer portal's URL?~~ — **answered 2026-09-30: `/de/konto`**, with a 301 from `/de/student/profil`. See `12-customers.md` | — | — |
| ~~44~~ | ~~Stripe: Checkout or Payment Element?~~ — **answered 2026-09-30: Stripe Checkout**, Stripe's own page. See `13-checkout.md` | — | — |
| ~~45~~ | ~~A failed card payment in a mixed basket?~~ — **answered 2026-09-30: the course is booked anyway**; the licence order waits unpaid. See `13-checkout.md` | — | — |
| ~~46~~ | ~~The licence delivery e-mail: account or per order?~~ — **answered 2026-09-30: on the account.** See `13-checkout.md` | — | — |
| ~~21~~ | ~~Who signs a participation confirmation?~~ — **withdrawn 2026-09-23: the question rested on a misreading.** Legacy's signature partial is not empty, and every prod confirmation is signed. Restored | — | — |

### ~~21. Who signs a participation confirmation?~~

**Withdrawn 2026-09-23 — the premise was wrong.** `pdf/partials/signature.blade.php`
is 61 bytes: `Oliver Schmid, Kurs Organisator<br>{{env('APP_NAME')}}`, which is
the company on production. All 447 participation confirmations in the prod
storage copy end with it. The rework's certificate now does too
(`config('documents.signature')`), with a test. What follows is the original
entry, kept so the mistake stays visible.

Found 2026-09-22 while building the certificate. Legacy's template ends with
`@include('pdf.partials.signature')` and **that file is zero bytes** — it has
been empty since 2022, so all 594 participation confirmations ever issued end in
mid-air with nothing signing them.

Not reproduced and not invented. A name, a title and a signature image are the
client's to choose, and the alternative — making one up — puts a person's
signature on a certificate without asking them.

**Blocks nothing.** The document is built and correct otherwise; adding a
signature block later is a template change.

### ~~20. Which chunk installs dompdf, and what is the letterhead?~~

Raised 2026-09-22, building the expert portal. **Three deferred documents are now
waiting on one decision**, and they were deferred separately:

| | where it was deferred |
|---|---|
| The QR-bill invoice | `03-invoices.md` |
| The participation confirmation | `03-invoices.md` |
| The *Teilnehmerliste* | the expert portal, 2026-09-22 |

**Answered the same day, by building it.** `dompdf/dompdf ^3.1` and
`sprain/swiss-qr-bill ^5.3`, both verified against Laravel 13 and PHP 8.4 before
a line was written. The letterhead is legacy's own `pdf-header.svg` and the
typeface is legacy's own Effra, both carried across as files rather than fetched
over HTTP. All three documents are built and tested; the write-up is in
`03-invoices.md` under *The invoice PDF and its QR bill*.

### 19. The 67 past courses sitting in *Gebuchte Kurse* on the live site

Found 2026-09-22 while building the student portal. Legacy moves a booking from
*Gebuchte Kurse* to *Absolvierte Kurse* on the `isConcluded` flag, which
`EventClosedHandler` sets **only for a booking already flagged
`hasParticipated`** — so a seat nobody ticked off never leaves the first list.

Measured on the 2026-09-11 snapshot: **67 active bookings on courses that have
already run**, across **63 students**, the oldest from **16 March 2023**. Each
carries a live *Annullieren* button, and cancelling one would fire the 100 %
penalty rule against a course that ran two years ago.

The rework does not inherit it — it splits on the event's date, and neither flag
was ported. **The question is only about the live site**: is this worth a tidy-up
there before cutover, or does it simply go away at cutover? It goes away either
way; the risk in the meantime is a student pressing a button that bills them.

Ours to raise, then Marcel's to decide. Same conversation as the `/expert/finish`
hole and the 2023 participation confirmations.

Question 18 arrived with the course-page rebuild on 2026-09-21 and answers 12.
Questions 14–17 arrived with `08-accounts.md` on 2026-09-18. **5 and 15 were
answered the same day** — the media pipeline is a port of Marcel's
`forrerzimmermann.ch` subsystem, which is also the client's "image handling
(frontend output)" requirement met. 14, 16 and 17 remain.
| 14 | How does the queue worker run in production? | Marcel | Deploy |

---

## For the client

### 1. Is the Bildung licence tier real?

`Twinmotion-Lizenzen.html` shows a second tier beside Einzelplatz (CHF 590/Jahr):
**Bildung, CHF 145/Jahr, "pro Jahr, Nachweis nötig"**, and its button is
"Nachweis einreichen" rather than a buy button.

**Marcel, 2026-09-17: this may be mockup filler rather than a requirement.** The
mockups are wireframes and their content is not authoritative, so the first
question is whether VIAK sells an education licence at all.

**Decides:** if it is real, it is an upload, a human review and an approval
standing between the customer and the basket — a third flow beside "buy" and
"enquire", and the only place where *who* is buying matters. If it is not real,
the tier comes off the page. A cheap middle option: treat Bildung as an enquiry
variant, exactly like "Preis auf Anfrage", and handle the proof by email.

### 2. Does VIAK order from the reseller before or after the money arrives?

Fulfilment is manual — the customer orders, VIAK gets an email, a human orders
from the reseller and forwards the licence on — and payment by invoice is offered
alongside TWINT and card.

**Decides:** whether fulfilment hangs off *paid* or off *ordered*. Dispatching
first means handing over a licence that may never be paid for. Dispatching second
means the customer waits an invoice cycle for something the site implies is
quick. It changes the admin worklist and what the customer is told after checkout.

### 3. Do discount codes apply to licences, and do students pay a different price?

No pricing rules yet as of 2026-09-17. 102 discount codes exist today and all 79
uses resolve to course bookings.

**Decides:** nothing immediately — it can be answered once the catalogue exists.
Worth asking in the same conversation as 1 and 2. Chunk 03 settled where the
answer would land: `invoice_items.discount` is per line, so a licence discount
needs no schema change whichever way this goes.

### 33–39. The licence catalogue — asked 2026-09-29

The client sent their existing shop's 107 products as a spreadsheet, and the
rework replaces that shop (3d-software.ch). The list raised seven questions; Marcel
forwarded them the same day (`Fragen-Software.txt`). The reasoning behind each is
in `05-licences.md`, *The real catalogue*.

- **33. Prices and article numbers.** The list has neither; article numbers
  survive only inside the old shop's URLs. **Decides:** whether there is a
  catalogue to import at all. The only one of the seven that blocks.
- **34. The 3-year term.** 16 products advertise it, none has a product for it.
  **Decides:** whether a 3-year variant carries its own price.
- **35. Demos.** 10 products. **Decides:** free order, enquiry or link.
- **36. Hidden products.** **Decides:** whether a *not listed* product can be
  reached on the site, or is only a preset for an admin-made order. Also closes 1.
- **37. Updates and upgrades.** **Decides:** the grouping only.
- **38. "Nur zusammen mit Neulizenz"** (Maxwell V5 Rendernodes Bundle).
  **Decides:** whether the basket has dependency rules. A note is the proposal.
- **39. Quantity limits.** **Decides:** whether `min_quantity` needs a partner.

### 4. Which of the six Vorhaben are real, and are there more coming?

Räume, Bilder, Objekte, Bewegtbild, KI, Teams — six is assumed from the mockups,
not confirmed. The template is cheap; the count is what drives the work.

### 22. Is there a blog, and is Aktuelles its front page?

Raised in the 2026-09-23 mockup review, which marked Aktuelles *build in part*
with the note "Blog-Einstieg, noch offen", and put "Blog?" against the
homepage's Aktuelles strip. Nothing in the rework has a news or article model
yet; legacy has a `news` table (`08-accounts.md`).

**Decides:** whether `Article` is built at all, and whether it is a news strip
or a blog with its own pages, categories and URLs.

### 23. The homepage: what is on it?

Marked open in the review, but with eleven markers — the most decided of the
open screens. They are listed in `04-content.md` under *The mockup review*. The
nav change in marker 8 (*Angebot* replacing *Kurse* and *Software*,
Firmenschulung out of the menu) is site-wide, not a homepage detail.
Firmenschulung stays out of the menu (briefly in it on 2026-10-06, then taken
out again by Marcel).

**Decides:** the phase-two homepage, and the nav on every page.

### 5. What does "image handling (frontend output)" mean?

It is listed as a special task in the original brief. `spatie/laravel-medialibrary`
is assumed but not confirmed.

**Decides:** the media field in chunk 04's field kit is the thin part; the
pipeline behind it is the thick one. Worth pinning down before building either.

### 6. Will EN ever be implemented?

Answered for *this* rework — the admin edits DE only and the public site is DE —
but the data model stays translatable so the decision can be reversed cheaply.

**Decides:** nothing now. But a firm *never* would let a later chunk simplify the
translatable columns and the `{de, en}` Resource maps away, so it is worth asking
rather than carrying the option forever.

**Marcel, 2026-09-18: ignore EN for now, but keep the URL structure.** Which makes
this cheaper to leave open than it was. The `/de/` prefix is retained for SEO
(see `00-foundation.md`, *Public URLs and locale*), so `/en/` stays free and
turning EN on remains a config change plus content entry. The question is now
worth asking only for the simplification it would unlock, never for the option it
preserves — the option is preserved either way.

### 7. Do the historical invoice due dates matter?

`invoices.due_at` has been overwriting itself on every UPDATE since 2023, so the
original payment deadline is gone on all 541 paid invoices. They are not
recoverable from the invoices table, but Run My Accounts received `duedate` at
creation time and the invoice PDFs may carry it.

**Decides:** whether the port attempts a reconstruction, which is real work and
has to run against the dump we actually cut over. If dunning, the accounting
export and reprinted PDFs do not need them, we drop it. Full write-up in
`Todo.md`.

### 8. Event 213 — what was it meant to be?

One of the 14 events lost to two-digit years. The other 13 reconstruct from a
rule; 213 lands on a **Saturday**, and Blender Modeling has only ever run
Thursday + Friday. The day itself looks mistyped, not just the year, so it cannot
be recovered from the data.

**Decides:** restore it with a corrected date, or drop it. None of the 14 ever
took a booking or appeared on the site.

### 13. Is the Mailchimp newsletter sync still in scope?

Legacy syncs to Mailchimp on every profile save — `Facades/NewsletterSubscriber`
subscribes or unsubscribes `users.subscribe_newsletter` and tags the member
`Deutsch` plus whatever `env('MAILCHIMP_TAGS')` holds. Nothing in the rework
brief mentions it; chunk 04 treats "Newsletter" only as footer *copy*. So it is
either a live integration nobody has listed, or a feature that quietly lapsed.

**Decides:** whether the rework carries a Mailchimp dependency, an API key and a
sync at all, or whether `subscribe_newsletter` is just a flag the admin can read.

---

## For Marcel

### 9. The other 13 two-digit-year events: drop or restore?

They reconstruct reliably — `events.date + 2000 years` is the mistyped day, and
the Thu+Fri course pattern fills in the rest; the table is in `Todo.md`. Roughly
10 real lost dates and 4 duplicate entry attempts (210/211/212 are one Godot
course entered three times, 294/295 one SketchUp course entered twice).

**Decides:** whether the cutover history is complete for reporting. Either way
`port:courses` has to stop skipping silently — an explicit drop list or an
explicit reconstruction map, not a warning on stderr.

### 10. `due_at` in the port

Two decisions beyond the column fix itself: what to do about the **541 lost**
deadlines (see 7), and what the **open and overdue** invoices carry across.
Whatever `due_at` they hold at cutover is today's date, which is wrong for all of
them — either recompute from the booking's event start, or carry them over
knowingly wrong. Pick one, in the port.

### 11. What PHP does production run?

The rework is pinned to 8.3 because Herd serves 8.3 locally. If production runs
8.4, raise the pin. Only bites at deploy time.

### ~~18. Do the Elfsight review widgets come across, get replaced, or go?~~

**Answered and built 2026-10-06 (Marcel):** the course page's *Kundenmeinungen*
column shows the published testimonials whose subject is the course, in the
testimonials' order, as the Firmenschulung cards. Nothing picks them
(`Course::testimonialsAbout`); the dashboard's *Verwendet auf* counts that
course while it is published. Course placements stay unused for now.

**Mostly answered 2026-09-23, in the mockup review**: "Testimonials als Backend
Modul ersetzt bestehende Google Rez." — the widgets are replaced by quotes VIAK
enters, and `Testimonial` stays in `04-content.md`. What is left is whether the
course page's *Kundenmeinungen* column draws from the same module, or goes.
The original entry follows.

**Answers and replaces 12**, which asked what `courses.reviews` holds. Checked
on 2026-09-21 while rebuilding the course page: all 32 non-empty rows are

```html
<script src="https://apps.elfsight.com/p/platform.js" defer></script>
<div class="elfsight-app-{uuid}"></div>
```

across **23 distinct widget ids**. So the *Kundenmeinungen* cards on the live
course page are Google reviews fetched and drawn by a third party in the
visitor's browser. VIAK owns none of that text, and there is nothing to port
into the `Testimonial` model `04-content.md` proposes — that plan rested on the
column holding structured data, and it does not.

`PortCourses` never carried the column: `maybeJson()` returns null for anything
that is not JSON, so all 32 were dropped without a word. It now reports one
finding per row, and the page has the column ready but empty.

**Decides** three things at once: whether every course page loads a third-party
script (a consent banner question as much as a performance one), whether the
reviews appear at all in the rework, and whether `04-content.md` keeps a
`Testimonial` model or drops it. The cheapest honest option is probably to keep
the widget and declare it; the most work is to ask the client for real quotes.

### ~~12. What is actually in `courses.reviews`?~~

Answered 2026-09-21 — see 18.
Ours to answer by looking, not a client question.

### ~~34. Should the office hear *Min. Teilnehmerzahl unterschritten* for a date it cancelled itself?~~

Found 2026-09-29 by `scenario:play cancel` (`10-mail.md`, layer 3). Cancelling
a date gives up its seats through the same path a student's cancellation
takes, so the threshold listener sees the count fall and mails the office.
Legacy did not: its handler flagged the rows directly. The office has just
done it, so the mail says nothing new.

**Decided 2026-09-29 (Marcel): no.** `NotifyParticipantThreshold` records the
band but announces nothing for a seat whose reason is `EventCancelled`.

### ~~25. Firmenschulung's URL, and does it launch before cutover?~~

**Answered and built 2026-10-06 (Marcel):** `/de/firmenschulung`, with a 301
from legacy's `/de/individualschulungen`. It ships with the rest at cutover.


Decided 2026-09-24 that Firmenschulung is built from the mockup and legacy's
`/de/individualschulungen` is not rebuilt (`04-content.md`). That leaves the
indexed legacy URL needing a target. If the new page is not ready at cutover,
the choice is a 301 to Kontakt, or a temporary parity page.

**Decides:** the redirect map at cutover, and whether Firmenschulung is on the
cutover's critical path.

### ~~24. Where do the new Kontakt and Firmenschulung forms send, and what do they keep?~~

**Answered 2026-10-06 (Marcel), and built for Kontakt.** The office address
(`config('mail.admin')`), Reply-To the sender; **no mail to the sender**
(dropped the same day); **nothing stored**. Spam: a honeypot field and five messages an hour
per IP, and **Cloudflare Turnstile** (added the same day, Marcel). Firmenschulung's enquiry takes the same answer.


**Firmenschulung has one too** (added 2026-09-24): an *Anfrage senden* box with
Firma, Ansprechperson, E-Mail and a message. Same recipient question, same
spam question — worth answering once for both.


The review approved the Kontakt mockup with a form on it (marker 2,
"Hinzufügen"). Legacy's Kontakt page has none. It is the first public form that
mails VIAK rather than the customer, so it needs a recipient, spam protection
(honeypot or a rate limit — no third-party captcha without a consent question),
and a decision about whether submissions are also stored.

**Decides:** nothing until mail exists. Phase two.

### 14. How does the queue worker run in production?

The rework puts mail, PDFs and accounting posts on the database queue, replacing
legacy's two-emails-a-minute scheduler task. That leaves the worker's lifecycle
open: `queue:work` under a supervisor, or — if the host gives only a crontab —
`schedule:run` each minute driving `queue:work --stop-when-empty --max-time=55`.
The second is fine and still far better than what legacy does.

**Decides:** deployment, not code. Same conversation as 11, and worth settling in
the same breath.

---

## Not questions — pending actions

- **Turnstile at cutover: the production hostname.** The Kontakt form checks
  Cloudflare Turnstile ([[Turnstile]], 2026-10-06), widget
  `0x4AAAAAAFPGhsBYEI29m__y`, **invisible**. Its keys are in the local `.env`;
  production needs the same pair and `TURNSTILE_HOSTNAMES` set to the live
  hostname only (no `.test`, no localhost), and that hostname registered on
  the widget. The Datenschutzerklärung names Cloudflare Turnstile and its
  Privacy Addendum under 11.1, which invisible mode requires. **Confirmed
  2026-10-06** (Marcel): the widget is saved as invisible, and the entry is
  accepted.
- **`ALTER TABLE invoices MODIFY due_at TIMESTAMP NULL DEFAULT NULL;` on the live
  site.** Stops open invoices having their deadline bumped daily. Recovers
  nothing already lost, removes none of the decisions above, and is worth doing
  whether or not the migration is close.
- **Delete Typekit kit `kcs4ept` from the legacy `head.blade.php`.** It serves
  neuzeit-grotesk, no stylesheet references it, and it is a dead render-blocking
  request on every page of the live site.
- **Invoice 000419 is open at CHF −149.00.** Booking 000512 took a fixed CHF 648
  discount code against a CHF 499 course and nothing clamped it; the same booking
  carries a CHF 80 laptop rental that was never invoiced. One booking of 710, but
  it is a negative open invoice in the live books, and per chunk 03's doctrine the
  port copies rather than recomputes — so it comes across as it stands unless
  someone decides otherwise. Raise it with the client before the cutover. See
  `06-bookings.md`.
- **At cutover, re-check rentals against laptop counts.** On the 2026-09-11
  copy, one active rental sits on a date whose room legacy says has **no**
  laptops — event `eac2d8b5-3c87-43a1-a8aa-ab3e2e37fd70`, 2026-12-15; the count
  was most likely lowered after the booking. Legacy is in the same state and
  the rework refuses only *new* laptops, so it ports as it stands. Run against
  the cutover dump — any date where active rentals exceed `rentals_available`
  — and settle each with VIAK: raise the count, or drop the rental before its
  invoice is raised. Found 2026-09-24 with the laptop-count fix
  (`06-bookings.md`, *Laptops are counted*).
- **A fresh production dump before the cutover rehearsal.** The current copy is
  2026-09-11.
- **Licence copy at launch.** The mockups are wireframes, so nothing to decide,
  but the real copy cannot repeat `Twinmotion-Lizenzen.html`'s "Lieferung sofort
  per E-Mail" (delivery is a human at VIAK) or *Meine Lizenzen*'s validity dates
  (we do not track them).
- **Null Tailwind's nine paired line-heights, or keep naming `leading-`.**
  `resources/css/README.md` says the type scale carries no line heights, but
  redefining `--text-lg` does not remove `--text-lg--line-height`, so every
  `text-*` emits Tailwind's own ratio instead of legacy's inherited 1.3. The
  course filter is fixed in place; a global fix moves type on every page, and 12
  elements under `views/site` rely on the pairing today. Marcel's call whether
  that is one pass now or left until the remaining pages are built.
  See `09-public-site.md`.
- **Count-check the other pivots and morphs the ports fill.** `event_expert` was
  empty for weeks because legacy calls it `event_user` and `PortCourses` only
  ever cleared it. An unfilled pivot raises nothing — no error, no null, no
  missing column — it just makes a relation permanently empty.

  **A second one surfaced on 2026-09-22**, from the other direction: `port:media`
  had filled **13 rows** with `mediable_type = App\Models\Event` and `Event`
  carried no `media()` relation at all, so a course's materials were unreachable
  for four days. Same silence. `media` has rows against four owners — Course
  258, User 48, Event 13, Message 20 — and **`User` still has no relation for
  its 48** (`08-accounts.md` says images attach to `Course`, `User`, `Hero` and
  `News`; `Hero` and `News` are chunk 04 and do not exist yet).

  So the check is now two-sided: every pivot a port fills, **and every
  `mediable_type` it writes, against a model that can read it back**. Still not
  done as a sweep.
- **Move the 13 course-material files off the public disk.** The portal's
  *Kurs-Dokumente* links now go through `MediaController` and `MediaPolicy`, but
  the files still sit in `storage/app/public/uploads` under the storage symlink
  — so the direct path is reachable without authenticating, which is the same
  shape as `08-accounts.md`'s finding 3 for the generated PDFs. Those were moved
  to a private disk because the storage decision came first; these were not,
  because moving them is a change to `port:media` rather than to a view. Found
  2026-09-22.

- **A friendly 429 for a throttled login.** Fortify throttles through route
  middleware, so the sixth failed attempt in a minute is Laravel's bare 429 page
  rather than legacy's "Zu viele Loginversuche. Versuchen Sie es bitte in
  :seconds Sekunden nochmal." — which is sitting unused in `lang/de/auth.php`.
  A small view, but it is the page a locked-out customer sees.
