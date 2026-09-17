# Open questions

Everything still waiting on an answer, in one place, with who owes it and what it
holds up. Answered questions are **not** kept here — they move into the chunk doc
they belong to, and `Todo.md` keeps the struck-through record of how each was
settled.

**Nothing on this list blocks anything that is being built.** Chunk 03 is built
and none of these held it up. Updated 2026-09-17.

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
| 5 | What does "image handling (frontend output)" mean? | Client | Chunk 04 media field |
| 6 | Will EN ever be implemented? | Client | Nothing — a *never* would let us simplify |
| 7 | Do the historical invoice due dates matter? | Client | Cutover — `port:invoices` reports it every run |
| 8 | Event 213 — what was it meant to be? | Client | Cutover |
| 9 | The other 13 two-digit-year events: drop or restore? | Marcel | Cutover |
| 10 | `due_at` in the port: the 541 lost, and the open/overdue | Marcel | Cutover |
| 11 | What PHP does production run? | Marcel | Deploy |
| 12 | What is actually in `courses.reviews`? | Us — check the data | Chunk 04 |
| 13 | Is the Mailchimp newsletter sync still in scope? | Client | Nothing yet — decides whether an integration exists at all |
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

### 12. What is actually in `courses.reviews`?

A `text` column on 35 rows holding what looks like structured data. The mockups
want quote + name + role + featured, which is a `Testimonial` model rather than a
column — but the proposed shape needs confirming against what those rows hold.
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
