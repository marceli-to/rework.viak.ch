# 07 — The dashboard (mapped, not built)

The admin's half of the app: the Vue SPA under `/dashboard`. Legacy's is
`resources/js/vue/backend/dashboard/` — 13 screen groups, about 55 routes and
8,600 lines of Vue.

## Status

**Mapped 2026-09-24, nothing built beyond two read-only lists.** Asked for by
Marcel before starting the field kit, because the kit is only worth designing
against the whole set of screens it has to serve.

What exists today:

- `/dashboard/termine` and `/dashboard/kurse`, both read-only lists
  (`resources/js/app/views/`). No `<input>` anywhere in the SPA.
- **The shell has no guard.** `Route::view('/dashboard/{any?}')` carries no
  middleware — the API behind it checks policies, but the page itself loads for
  anyone. Legacy's is `role:admin`. First thing to fix, before any form.
- **Most of the domain logic the screens need already exists** — chunks 02, 03
  and 06 built it as Actions: `SetEventState` (confirm/close/cancel, which raise
  the invoices), `CreateBookingForUser`, `CancelBooking`, `SetRental`,
  `RaiseCancellationPenalty`, `PostMessage`, the media Actions, all three PDFs.
  **The dashboard is mostly UI over logic that is already tested.** What is
  missing server-side is listed per group below.

Design is free here — the dashboard is not held to parity
(`resources/css/README.md`, *Which half am I in?*). Legacy's behaviour is the
reference for *what* an admin can do, not for how it looks.

## What is actually used

Counted on the legacy database (2026-09-11 snapshot), because a screen nobody
uses is not worth rebuilding ([[viak-frontend-rules]] — check usage before a
parity rebuild).

| | rows | activity |
|---|---:|---|
| events | 360 | **186 created since 2025** — the daily work |
| users | 578 | 200 created since 2025 (mostly self-registered) |
| invoices | 569 | 219 touched since 2025 |
| discount codes | 107 | 27 created since 2025 |
| courses | 41 | 7 created since 2025 |
| software | 22 | 4 created since 2025 |
| news | 5 | 3 since 2025, last 2026-08-17 |
| images / files | 492 / 44 | 92 / 18 since 2025 |
| categories, levels, languages, locations, tags | 4, 2, 2, 1, 21 | **untouched since 2022–2024** |
| heroes | 1 | never updated |
| team members | **0** | never used |
| homepage grid | 3 rows, 6 items | **hand-edited, last 2026-09-04** — see below |

**The homepage grid tells the clearest story.** One of its six items is a
**hand-written HTML table of the next ten course dates** — dates, titles and
`Anmelden` links typed into a code box, last edited 2026-09-04. Another is an
Elfsight Google-reviews embed. The 2026-09-23 review asked for both to become
automatic: *Nächste Kurstermine* as a widget (homepage marker 3) and testimonials
as a module (marker 10). That is the work those two markers remove.

## Legacy, group by group

Verdicts: **keep** (rebuild the capability), **change** (rebuild, but not as
legacy does it), **drop** (not rebuilt), **new** (no legacy screen).

### Courses and course dates — keep, the heart of it

Legacy: one index in two modes (every event chronologically, or courses as a
drag-sortable accordion with their events), a course form, an events-per-course
list, an event form, and an **event page** that is the operational screen.

- **Course form** — title, subtitle, fee, number; short/full description,
  two further-information fields, the PDF summary, three facts columns (all
  TinyMCE); online, publish; five taxonomy pickers; images (16:9 crop); videos
  (a repeater of title + embed code); SEO. → **Field-kit form #1.** Server:
  `StoreCourseRequest` exists; taxonomies must move from ids to uuids (the API
  only ever hands out uuids), and the SEO fields are nested in the Resource and
  flat in the Request — one shape has to win before the kit reads both.
- **Course order** — drag to reorder, which is how the public list is ordered.
  A sortable list, not a form field. Server: missing.
- **Event form** — min/max participants, rental laptops, fee override, online,
  free, publish, location, **the date builder** (rows of day + start + end),
  experts (at least one), registration deadline. → kit form with the date
  builder as the first `Field::custom()` — `04-content.md` named it as the
  escape hatch on day one. Server: exists (`StoreEventRequest`).
- **Event lifecycle** — *bestätigen*, *abschliessen*, *absagen*, *löschen*, each
  behind a confirm. **Confirming is what raises the invoices** and mails every
  participant; closing mails the participation confirmations; cancelling mails
  everyone. Server: `SetEventState` + `RaiseInvoicesForEvent` exist; **the mails
  are chunk 10**. Delete is only allowed with no bookings — keep that rule.
- **Event page** — the one screen an admin works in daily: participants with
  city, company, e-mail and rental; a *Teilgenommen?* checkbox per participant
  (what the confirmation PDF is issued from); *Teilnehmer hinzufügen* (search a
  student, book them — `CreateBookingForUser` exists); the participant-list PDF
  (exists); messages to participants (`PostMessage` exists, the expert portal
  already has the composer); course documents (upload exists). Custom screen,
  not a kit form. Server: attendance toggle **missing** — neither `hasParticipated`
  nor its replacement was ported ([[09-public-site]], question 19).

Legacy bugs **not** to port: reordering while a search is active saves the
wrong list; the EN SEO fields are bound to DE; short-description errors never
show; deleting a course deletes its events without checking for bookings; the
file-remove button removes the wrong file.

### Students — keep, change how accounts are made

- **List + search** — name, city, e-mail, phone. 578 rows today and growing
  ~200 a year, so **server-side search and pagination**, not legacy's load-all.
- **Student page** — booked, attended, documents (invoices and certificates),
  and **Annullieren** per booking with the penalty shown. `CancelBooking` +
  `RaiseCancellationPenalty` exist. **The admin decides, per cancellation,
  whether the penalty is charged** (#14, decided): the dialog shows the amount
  and asks. Server: `BookingCancellationReason::Administrator` charges
  unconditionally today, so the cancel endpoint needs the admin's answer as an
  input and `chargesPenalty()` has to take it.
- **Edit** — gender, names, phone, address, country, newsletter; invoice
  addresses (create/edit/delete). Kit form.
- **Create** — legacy has the admin **type the student's password**. Changed:
  the student gets the same set-your-password invite experts get (#26,
  decided). Needs mail.
- **Delete** — **there is none: an account is deactivated, never deleted** (#16,
  decided). Invoices, bookings and documents keep pointing at a real person,
  and a deactivated account cannot sign in or book. Legacy soft-deleted, or
  stripped the role when there were several.

### Experts — keep

- **List** — active (drag to set the public order) and inactive.
- **Form** — personal fields, `visible` and `publish` (the two flags the
  Experten page reads), **roles** (the only place legacy assigns any role,
  Admin included), title, bio (rich text), portraits (teaser + 16:9 visual).
  Kit form.
- **Create** sends an invite (*Dein VIAK-Zugang*, a signed 72-hour link).
  Mail, chunk 10.
- Legacy lets the e-mail change with no uniqueness check. Don't.

**Role management should not live on the expert form.** It is the only place
legacy can make someone an admin; the rework puts roles on the person, wherever
they are edited.

### Discount codes — keep, make the rules explicit

Code (generated, `VIAK-XXXX-XXXX`), amount, fixed or percent, valid from/to,
remarks. Lists split into valid and used-or-expired. **The usage rule is
implicit in legacy**: a code without dates is single-use, a code with dates is
unlimited while valid. Make it a visible field. Kit form. Server: chunk 06 holds
the model; the CRUD endpoints are missing.

### Invoices and export — keep, narrow the edit

- **Invoice lists** — open, overdue, paid, cancelled; number, date, amount,
  student; PDF link. Server-side search.
- **Invoice edit** — legacy's UI changes only the invoice address, but the
  endpoint mass-assigns **any** field and regenerates the PDF. The rework
  allows the address and nothing else, re-renders, and never touches Run My
  Accounts from a prototype ([[run-my-accounts-mock-until-cutover]]).
- **Export** — one Excel file, a sheet per course with past bookings: salutation,
  names, company, address, phone, e-mail, course date. Keep; it is someone's
  mailing list or accounting routine. Server: missing (`maatwebsite/excel` or a
  CSV).

### Settings — keep, all of them

Categories, languages, levels, tags (a translatable title each), locations
(title, address, map URL). **They stay** (Marcel, 2026-09-24): they are what
the course filter and the course pages are built on, even if the lists rarely
change. Rarely edited is a reason to build them cheaply — one generic taxonomy
schema in the kit rather than five hand-made screens — not to drop them. **Software leaves this group**: chunk 05
turns it into an entity with variants and a manufacturer, and it gets its own
screen there.

### Content — mostly drop, some new

| Legacy | Verdict | Why |
|---|---|---|
| Startseite (the grid) | **drop** — confirmed | The homepage is built differently, from the mockup; its editable parts become a `HomeSchema` once #23 is answered |
| Heroes | **drop** — confirmed | Legacy's homepage slider draws from them, but the mockups have no slider |
| Team | **new, later** — confirmed | Not carried across; team members are introduced with the phase-two Team page, as whatever that page needs |
| News | **change** | Five items, still in use — becomes `Article` if Aktuelles/the blog goes ahead (#22) |
| — | **new: Testimonials** | Quote, name, role, image, featured, order. Kit form #2 |
| — | **new: Media** | The forrerzimmermann grid/uploader/cropper, which the image field picks from |

### The rest

- **Landing page** — legacy's says *Hallo {Vorname}* and nothing else. **Left
  empty** (#27, decided): not in use, so `/dashboard` goes straight to the
  course dates, as it does today.
- **Own profile** — names, address, e-mail, password. Small kit form; changing
  e-mail should verify, as it does for students.

## The field kit, checked against every form

`04-content.md` said the inventory is closed at about twelve components. Every
form above, checked:

| Form | text | textarea | richtext | number/money | boolean | select | relation | repeater | image | date | group | custom |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Course | ● | ● | ● | ● | ● | | ● ×5 | ● videos, facts | ● | | ● SEO | |
| Event | | | | ● | ● | ● location | ● experts | | | ● deadline | | ● date builder |
| Expert | ● | | ● | | ● | ● gender, country | ● roles | | ● ×2 | | | |
| Student | ● | | | | ● | ● | | | | | | |
| Address | ● | | | | | ● | | | | | | |
| Discount | ● | ● | | ● | | ● type | | | | ● ×2 | | |
| Taxonomy | ● | ● location | | | | | | | | | | |
| Testimonial | ● | ● | | | ● | | | | ● | | | |
| Profile | ● | | | | | ● | | | | | | |
| Article (#22) | ● | | ● | | ● | | | | ● | ● | | |

**It holds.** No form needs a thirteenth component. What does not belong in the
kit is just as clear:

- **ordering** happens on lists (courses, experts, testimonials), so it is a
  sortable-list component beside the kit, not a field;
- **the event page, the student page and the invoice lists** are operational
  screens with actions, not forms — built by hand, from a small set of list,
  row and confirm-dialog components;
- **password and e-mail changes** are account flows with verification, not
  fields.

## A navigation for it

Legacy's top bar holds Kurse, Experten, Studenten; everything else sits in a
hamburger. Proposed, grouped by what the admin is doing:

- **Kurse** — courses, and each course's dates; **Kursdaten** — every date,
  chronologically (today's `/dashboard/termine`)
- **Personen** — Studierende, Experten
- **Verkauf** — Rechnungen, Rabatt-Codes, Export
- **Inhalte** — Startseite, Testimonials, Aktuelles, Medien
- **Einstellungen** — the taxonomies, Standorte
- Profil, Abmelden

## Build order

1. **Guard the shell** (`auth`, `role:admin`), and the list/detail/confirm
   building blocks the operational screens share.
2. **Course form, by hand** — kit form #1, against the existing API.
3. **Media admin** — ported from `forrerzimmermann.ch`, so a form can pick and
   crop.
4. **Testimonials, by hand** — kit form #2, and the model with it.
5. **Extract the kit** — `Field::` schemas beside the requests,
   `GET /api/schema/{type}`, `FormRenderer`, error mapping, leave guard. Move
   both forms onto it.
6. **The CRUD that remains, on the kit** — event form (date builder as the
   first custom field), experts, students, discount codes, taxonomies, profile.
7. **Operational screens, by hand** — the event page, the student page,
   invoices, export.
8. **Homepage schema** once #23 is answered; **Aktuelles** once #22 is.

**Mail (chunk 10) runs alongside, and gates step 7.** Confirming an event raises
invoices and must tell participants; an admin booking must confirm itself to the
student. Built before mail exists, those buttons would do half their job
silently. Steps 1–6 do not depend on it.

## Decided — 2026-09-24

Marcel, on the questions this map raised:

- **#14 — the admin decides** whether an admin cancellation charges the
  penalty, each time.
- **#16 — deactivate, never delete** a user.
- **#26 — invite** admin-created students to set their own password.
- **#27 — no landing page** for now.
- **The homepage grid and heroes are not built**; team members arrive with the
  new Team page; **the settings taxonomies stay**.

Nothing in this chunk is waiting on an answer. What it waits on is mail
(chunk 10), for step 7.
