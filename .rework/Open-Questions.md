# Open questions

Everything still waiting on an answer, in one place, with who owes it and what it
holds up. Answered questions are **not** kept here — they move into the chunk doc
they belong to, and `Todo.md` keeps the struck-through record of how each was
settled.

**Nothing on this list blocks anything that is being built.** Chunk 03 is built
and none of these held it up. Updated 2026-09-22.

**One item is not a question and is not waiting on anyone:** the
`/expert/finish` account-takeover path on the live site, found 2026-09-18 while
scoping `08-accounts.md`. It is written up in `Todo.md` and needs fixing in the
legacy tree now.

`06-bookings.md` was scoped on 2026-09-17, after the legacy facade map showed
five facades with nowhere to land. It raised four client questions and **all four
were answered the same day**, so none of them appear below; they are in the chunk
doc with the reasoning. Nothing in chunk 06 waits on the client.

| # | Question | Owner | Blocks |
|---|---|---|---|
| 1 | Is the Bildung licence tier real? | Client | Chunk 05, partly |
| 2 | Licence dispatch before or after payment? | Client | Chunk 05, partly |
| 3 | Discount codes and student pricing on licences? | Client | Nothing — the line already holds a discount |
| 4 | Which of the six Vorhaben are real? | Client | Chunk 04 page count |
| ~~5~~ | ~~What does "image handling (frontend output)" mean?~~ — **answered 2026-09-18**, see 15 | — | — |
| 6 | Will EN ever be implemented? | Client | Nothing — a *never* would let us simplify |
| 7 | Do the historical invoice due dates matter? | Client | Cutover — `port:invoices` reports it every run |
| 8 | Event 213 — what was it meant to be? | Client | Cutover |
| 9 | The other 13 two-digit-year events: drop or restore? | Marcel | Cutover |
| 10 | `due_at` in the port: the 541 lost, and the open/overdue | Marcel | Cutover |
| 11 | What PHP does production run? | Marcel | Deploy |
| ~~12~~ | ~~What is actually in `courses.reviews`?~~ — **answered 2026-09-21: an Elfsight widget embed**, on all 32 non-empty rows, 23 distinct widget ids. Not testimonial data at all. Replaced by 18 | — | — |
| 13 | Is the Mailchimp newsletter sync still in scope? | Client | Nothing yet — decides whether an integration exists at all |
| 14 | Should an admin cancelling for a student charge the penalty? | Marcel | **Nothing** — chunk 06 is built. `BookingCancellationReason::Administrator` exists and currently charges, as legacy did. Flipping it is one line in `chargesPenalty()`, and the reason is now recorded either way |
| ~~15~~ | ~~Medialibrary, or `marceli-to/image-cache`?~~ — **answered 2026-09-18: neither.** Port the media subsystem from `forrerzimmermann.ch` — Glide, one `media` table, crop JSON, `<picture>` with AVIF/WebP. Answers 5 too. See `08-accounts.md` | — | — |
| 16 | Is a user with financial history ever deleted, or only deactivated? | Marcel | Chunk 08's **admin user screens**, which are not built. Nothing else |
| ~~17~~ | ~~Are the historical PDFs carried across?~~ — **settled 2026-09-18: yes, and they are.** `port:documents` carries all 1,005 distinct files, repairing the 271 broken paths on the way. The only thing left to ask is whether the 2023 participation confirmations should have been repaired in the legacy tree too (`Todo.md`) | — | — |
| 18 | Do the Elfsight review widgets come across, get replaced, or go? | Marcel, then the client | The Kundenmeinungen column on the course page, and `04-content.md`'s `Testimonial` plan |
| 19 | The 67 past courses listed as *Gebuchte Kurse* on the live site | Marcel | **Nothing here** — the rework splits on the date. A live-site tidy-up, or nothing |
| 20 | Which chunk installs dompdf, and what is the letterhead? | Marcel | The expert portal's *Teilnehmerliste (PDF)*, the QR bill and the participation confirmation — three deferred documents waiting on one decision |

### 20. Which chunk installs dompdf, and what is the letterhead?

Raised 2026-09-22, building the expert portal. **Three deferred documents are now
waiting on one decision**, and they were deferred separately:

| | where it was deferred |
|---|---|
| The QR-bill invoice | `03-invoices.md` |
| The participation confirmation | `03-invoices.md` |
| The *Teilnehmerliste* | the expert portal, 2026-09-22 |

Nothing in the rework generates a PDF — `00-foundation.md` says dompdf gets
added by the chunk that needs it, and no chunk has yet. The participant list is
by far the smallest of the three and building it now would set the page
furniture — letterhead, margins, fonts, footer — for the two that carry money.
So it waits for the chunk that owns documents, and the policy in front of it is
already written (`EventPolicy::viewParticipants`).

**Not urgent, and not a blocker:** the expert's course screen draws the
participant list itself. The PDF is the printable form of a list that is on the
screen.

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

### 4. Which of the six Vorhaben are real, and are there more coming?

Räume, Bilder, Objekte, Bewegtbild, KI, Teams — six is assumed from the mockups,
not confirmed. The template is cheap; the count is what drives the work.

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

### 18. Do the Elfsight review widgets come across, get replaced, or go?

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
