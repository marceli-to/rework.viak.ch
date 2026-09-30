# 07 — The dashboard (steps 1–7 built; step 8 waits on the client)

The admin's half of the app: the Vue SPA under `/dashboard`. Legacy's is
`resources/js/vue/backend/dashboard/` — 13 screen groups, about 55 routes and
8,600 lines of Vue.

## Status

**Mapped 2026-09-24**, at Marcel's request before any form work, because the
field kit is only worth designing against the whole set of screens it has to
serve. **Steps 1–5 were built the same day** (the guard, the shell, *Kurse* in
both modes, the course form and its images, testimonials, the field kit), and
**step 6 began with the course-date form**. See *Step 1* to *Step 6* below,
then *Loading* and *Polish*.

**Step 6 is done** (2026-09-29): every form on the kit (*Step 6 — experts*,
*— students*, *— discount codes*, *— settings*, *— profile*).

**Step 7 is done** (2026-09-29): *Rechnungen*, *Exporte*, the student page and
the event page, then an afternoon of Marcel's review against legacy's
screens. Where things stand, in the sections below:

- *Step 7 — the student page* and *— the course date's page* (the event
  page), with *Attendance is asked when closing* (the tick moved into
  closing), *Back is where you came from*, and *A course has
  Veranstaltungen*.
- **Matched to legacy on Marcel's screenshots**: the student page (*Profil
  Student*, teal *Download*, no *Details*), course documents ([[FileRow]],
  their own upload screen, *Bezeichnung* back on both portals), messages
  ([[MessageRow]], the portal's composer), empty lists ([[NoResults]]),
  square badges `px-6 py-2`.
- **Answered 2026-09-30**: #40 (the app runs in Zurich time, *Zurich time*)
  and #41 (a seat missed at closing is confirmed afterwards, *Attendance is
  asked when closing*).
- **Compared with legacy on 2026-09-30**: *Kurse*, the course form,
  *Experten* and its form, *Studenten*, *Rabatt-Codes*, *Einstellungen*,
  *Rechnungen*, at 1710px and 390px. See *The walkthrough against legacy*.
- **Next**: step 8 (homepage, *Aktuelles*) waits on the client (#22, #23).

What exists at the end of 2026-09-24:

- **The shell is guarded** (`auth`, admin only; a student or expert is sent to
  their portal), and admin endpoints live under `/api/admin` (*Step 1*).
- **Screens built**: *Kurse* (both modes), *Kurs erfassen / bearbeiten* with
  *Bilder*, *Veranstaltung hinzufügen / bearbeiten*, *Testimonials* (list and form),
  *Experten*, *Studenten*, *Rabatt-Codes*, *Einstellungen*, *Mein Profil*
  (2026-09-29), *Rechnungen* and *Exporte* (step 7, same day), the student
  page and the course date's page (step 7, same day). **Step 7 is done.**
  Every other menu entry renders `Pending` under its own title.
- **The field kit** (`app/Forms/`, `FormNode`, `useResourceForm`,
  `ResourceForm`) draws every form but the image section.
- **Most of the domain logic the screens need already exists** — chunks 02, 03
  and 06 built it as Actions: `SetEventState` (confirm/close/cancel, which raise
  the invoices), `CreateBookingForUser`, `CancelBooking`, `SetRental`,
  `RaiseCancellationPenalty`, `PostMessage`, the media Actions, all three PDFs.
  **The dashboard is mostly UI over logic that is already tested.** What is
  missing server-side is listed per group below.

**The look is legacy's dashboard, in the site's classes** (Marcel,
2026-09-24 — see decision 4 below). Legacy's behaviour is the reference for
*what* an admin can do, and its dashboard for how that looks.

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
  experts (at least one), registration deadline. → **Built 2026-09-24** (*Step
  6*): the date builder turned out to be a repeater drawn inline, not a custom
  part.
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
- → **List, edit, create and deactivate built 2026-09-29** (*Step 6 —
  students*); **the student page the same day** (*Step 7 — the student page*).

### Experts — keep

- **List** — active (drag to set the public order) and inactive.
- **Form** — personal fields, `visible` and `publish` (the two flags the
  Experten page reads), **roles** (the only place legacy assigns any role,
  Admin included), title, bio (rich text), portraits (teaser + 16:9 visual).
  Kit form.
- **Create** sends an invite (*Dein VIAK-Zugang*, a signed 72-hour link).
  Mail, chunk 10.
- Legacy lets the e-mail change with no uniqueness check. Don't.
- → **Built 2026-09-29**, invite aside (*Step 6 — experts*).

**Role management should not live on the expert form.** It is the only place
legacy can make someone an admin; the rework puts roles on the person, wherever
they are edited.

### Discount codes — keep, make the rules explicit

Code (generated, `VIAK-XXXX-XXXX`), amount, fixed or percent, valid from/to,
remarks. Lists split into valid and used-or-expired. **The usage rule is
implicit in legacy**: a code without dates is single-use, a code with dates is
unlimited while valid. Make it a visible field. Kit form. Server: chunk 06 holds
the model; the CRUD endpoints are missing. → **Built 2026-09-29** (*Step 6 —
discount codes*).

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
schema in the kit rather than five hand-made screens — not to drop them. → **Built
2026-09-29** (*Step 6 — settings*). **Software leaves this group**: chunk 05
turns it into an entity with variants and a manufacturer, and it gets its own
screen there.

### Content — mostly drop, some new

| Legacy | Verdict | Why |
|---|---|---|
| Startseite (the grid) | **drop** — confirmed | The homepage is built differently, from the mockup; its editable parts become a `HomeSchema` once #23 is answered |
| Heroes | **drop** — confirmed | Legacy's homepage slider draws from them, but the mockups have no slider |
| Team | **new, later** — confirmed | Not carried across; team members are introduced with the phase-two Team page, as whatever that page needs |
| News | **change** | Five items, still in use — becomes `Article` if Aktuelles/the blog goes ahead (#22) |
| — | **new: Testimonials** | Quote, name, context, published, order — no photo, no placement flag. Kit form #2 |
| — | **new: Media** | The forrerzimmermann grid/uploader/cropper, which the image field picks from |

### The rest

- **Landing page** — legacy's says *Hallo {Vorname}* and nothing else. **Left
  empty** (#27, decided): not in use, so `/dashboard` goes straight to the
  course dates, as it does today.
- **Own profile** — names, address, e-mail, password. Small kit form; changing
  e-mail should verify, as it does for students. → **Built 2026-09-29**
  (*Step 6 — profile*).

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

- **ordering** happens on lists (courses, experts) and in the testimonial
  picker, so it is a
  sortable-list component beside the kit, not a field;
- **the event page, the student page and the invoice lists** are operational
  screens with actions, not forms — built by hand, from a small set of list,
  row and confirm-dialog components;
- **password and e-mail changes** are account flows with verification, not
  fields.

## How it is built — decided 2026-09-24

Talked through with Marcel before step 1, and agreed point by point.

1. **The shell is admin-only.** `/dashboard` takes `auth` and `role:admin`. A
   guest goes to the login and comes back; a signed-in student or expert is
   sent to their own portal rather than shown a bare 403 — they almost always
   arrived by mistake.
2. **Admin endpoints live under `/api/admin/…`**, with the role on the whole
   group. The public and portal endpoints stay where they are. Today the
   dashboard reuses `/api/courses` and relies on policies; that works for
   courses and not for users, invoices or discount codes, which have no public
   side. Legacy drew the same line with `/api/dashboard/*`.
3. **What a form loads is what it saves.** An admin endpoint's response has the
   shape of its request, and names everything by uuid. Two places break it
   today and get fixed on the way: course taxonomies go out as uuids and come
   back as ids, and SEO is nested in `CourseResource` and flat in the request.
   The field kit only stays simple if a form can PUT back what it got.
4. **The look is legacy's dashboard, drawn with the site's classes.**
   ~~A dense grey working admin~~ was built first and **rejected by Marcel on
   sight** as outdated-looking. Legacy's dashboard is the site's own design —
   the site header with a dashboard menu, collapsibles, stacked rows — and the
   rework already has all of it as Blade components. So every Vue component
   is the twin of a Blade one, with the same Tailwind classes and the Blade
   file named in a comment. Duplicating the classes is accepted: "only 2
   places". Measured against the local legacy dashboard at `viak.ch.test`.
5. **The stack is the one chunk 00 set: Vue 3, Vue Router, Pinia, Tailwind 4.**
   Nothing from legacy's dashboard comes across — it is Vue 2, Vuex,
   `vue2-dropzone`, `vue-the-mask`, TinyMCE, `vue-moment`, none of which runs on
   Vue 3. Legacy says *what* each screen does; every component is new.
6. ~~**Components: Reka UI underneath, ours on top.**~~ **Dropped with 4**:
   copying the site's controls leaves nothing for a headless library to do —
   legacy picks taxonomies and experts from checkbox lists, not comboboxes,
   and dialogs, toasts and collapsibles are a few lines each. Built from
   scratch; the one hard widget, the student search in *Teilnehmer
   hinzufügen*, is built by hand when it comes. What was recorded: Reka is the Vue port of
   Radix — dialog, combobox, select, checkbox, switch, popover, date picker,
   toast, tabs — and ships no styles, so everything is drawn in Tailwind in the
   look above while focus, keyboard and ARIA come from the library. That is the
   part hand-rolled components get 90 % right and never finish: a searchable
   multi-select for five taxonomies and a course's experts, focus inside a
   dialog, a date picker. shadcn-vue, built on Reka, is a source to borrow
   from, not a dependency. A full kit (PrimeVue, Vuetify) was declined: its own
   look and theming would fight the Tailwind conventions.
7. **Around it, proven pieces**: tiptap (already here), Uppy and
   `vue-advanced-cropper` 2 (both from `forrerzimmermann.ch`, whose media
   screens are ported as planned). **Sorting is the browser's own drag and
   drop** (`useSortable`), not `vuedraggable` — one list needs it so far.
8. **Lists that grow search and paginate on the server** — people and
   invoices. *Kurse* does not: both its modes are views of the catalogue (41
   courses, ~30 upcoming dates), and the accordion can only be dragged into
   order when every course is on the page. It loads whole and searches in the
   browser, as legacy does.
9. **Tests**: Pest for every endpoint, as now, and every screen driven in a
   browser. Vitest arrives with the field kit (step 5), because the renderer
   is the first piece of the SPA with logic worth testing on its own.

## Step 1 — built 2026-09-24

- **The guard.** `DashboardController` behind `auth`: an admin gets the shell,
  a student or expert is sent to their portal, a guest to the login.
  `Home::for()` sends an expert to the expert portal after signing in — it
  sent them to the dashboard, which is admins-only now.
- **`/api/admin`**, `role:admin` on the group: `GET courses` (the whole
  catalogue, each course with its upcoming, uncancelled dates),
  `POST courses/order`, `PATCH events/{event}/state`.
- **The shell** — `components/layout/Header.vue`, the twin of
  `x-layout.header` with legacy's dashboard menu: Kurse, Experten, Studenten
  left, 48px apart; the profile icon and a burger right; a 240px panel with
  the rest. **Every menu entry is routed**; what is not built renders
  `Pending` under its own title, so the layout is true from the first screen.
- **Kurse, both modes**, measured against legacy to the pixel at 1482px:
  - chronological — `EventLine.vue`, the 2 / 6 / 4 row with the pencil and
    the arrow;
  - courses — `Collapsible.vue` in legacy's `is-course-events` variant (a 40px
    heading, the pencil 48px down, unpublished at 80 %), `EventRow.vue` inside
    it with *Bearbeiten* and *Details*, `+` and `→` under each course;
  - drag to reorder, saved as 1…n with *Reihenfolge angepasst*; **off while a
    search is active**, where legacy saved the wrong list;
  - mode and search in the URL, and a form goes back to the list as it was
    left (*Polish*, below).
- **Not on the list any more: the state buttons.** Legacy confirms, closes
  and cancels on the course date's edit screen, and so will this; they were
  one click from raising every invoice for a date.

Not measured: phone width.

## Step 2 — the course form, built 2026-09-24

*Kurs erfassen* and *Kurs bearbeiten*, by hand — the first of the field kit's
two source forms. Measured against legacy's form on the local legacy
dashboard; the fields are the site's own (`x-form.field` sizes throughout).

- **Its own shape, both ways.** `GET /api/admin/courses/{course}` hands out
  what `PUT` takes back ([[CourseFormResource]], [[SaveCourseRequest]]):
  German strings, taxonomy uuids, SEO flat, three facts, the videos inline. A
  test sends a loaded form straight back and gets the same form.
- **German only, English kept.** A translatable field is written as
  `['de' => …]`, which spatie merges; facts carry their `en` over by hand.
- **The editor is the composer's**, in Vue (`form/Editor.vue`): tiptap, bold,
  list, link — everything the ported copy uses. **[[EditorHtml]] cleans it on
  the way in**, keeping `target` and `rel` (63 of 87 links open a new tab) and
  relative links.
- **Videos save with the form**, as a list: kept by uuid, added, removed,
  ordered ([[SaveCourseRelations]]).
- **Delete is refused while any date has a booking**, cancelled ones
  included; otherwise the course and its dates are soft-deleted together.
- **The old public write path is gone** — `POST/PUT/DELETE /api/courses`
  and their two requests; the guarantees their tests pinned (a number never
  reused, the slug kept on a new title) are tested on the admin endpoints now.
- **The number is typed, as in legacy** (Marcel, 2026-09-24 — it was
  server-assigned). Prefilled with the next free one, and refused if any
  course has it, **soft-deleted ones included**: numbers reach invoices
  through `Event::number()`, so one never comes back. Legacy's duplicates came
  from checking uniqueness on create only.
- **The site's confirm dialog, in Vue** (`ui/ConfirmDialog.vue`), for deleting
  and for leaving with unsaved changes; the browser asks on a reload.
- **Not there yet**: images (step 3, the media admin), and legacy's
  *Rezensionen* box, which held the Elfsight embeds (#18).

Differences from legacy, all deliberate: no DE/EN switch, three toolbar
buttons rather than seven.

## Step 3 — the course's images, built 2026-09-24

*Bilder* on the course form: legacy's image module, measured on its dashboard,
on the media subsystem ported from `forrerzimmermann.ch` in chunk 08.

- **Every action saves at once**, as legacy's does — upload, type, alt text
  and caption, crop, order, delete — so the course form never sends its images
  and its round trip is untouched. `/api/admin/courses/{course}/media` lists,
  uploads and orders; `/api/admin/media/{media}` edits, crops and deletes.
- **One teaser and one OpenGraph image per course**, as the ported data has
  them (40 teasers on 41 courses, never two): making one the teaser takes the
  flag off the other ([[SetMediaRole]]). The first image a course gets
  becomes its teaser.
- **The crop's shape follows the type**: square for the teaser, the card's
  shape; 16:9 for the rest. In the file's own pixels, as Glide takes them.
  `vue-advanced-cropper`, which legacy and forrerzimmermann both use — the one
  widget not built from scratch.
- **The crop dialog is legacy's, to the pixel** (measured 2026-09-24): no
  title, the *16:9* / *1:1* buttons top left and the crop's size in pixels top
  right, the image 473px high and washed out in white outside a teal frame with
  10px teal handles, *Schliessen* and *Speichern* as two halves, and the close
  cross fixed at the window's top right. It opens on the format the image is
  for. The cropper's colours are marked `!` — its stylesheet is not in a
  cascade layer, and unlayered CSS beats Tailwind's utilities.
- **The cards show the crop**, through Glide at 480px, three to a row, drag to
  reorder. The drop box is the site's (`x-form.file-input`), with the 1px
  black line of 2026-09-23 rather than legacy's grey dashes.
- **A new course takes images too** (Marcel, 2026-09-24 — legacy said *Bilder
  können erst nach dem Speichern hochgeladen werden*). They wait in the browser
  as cards with their type, alt text, caption and order, and go up in that
  order right after the course is created. **Cropping waits for the upload**:
  a large file is scaled down on the server, and a crop drawn on the
  original's pixels would land in the wrong place.
- **Two save buttons**: *Speichern* saves and goes back to the list;
  *Speichern und Weiterbearbeiten*, secondary, below it, saves and stays — on a
  new course, by opening it for editing.
- **Left out, on the numbers**: legacy's eye icon — one of its 333 images was
  ever hidden, so an unwanted image is deleted — its *Listen Ansicht*, and
  forrerzimmermann's art-directed mobile variant.

## Step 4 — testimonials, built 2026-09-24

*Seiteninhalte → Testimonials*: the model, a list and a form — the second of
the field kit's two source forms, built by hand like the course form.

- **What one holds is what the mockups show**: the quote, a name, one line of
  context (*Architekturbüro, Zürich*). No photo, so the image field the kit
  matrix above pencilled in is not needed. Quote and context are
  translatable, the admin writes German.
- **A testimonial does not say where it appears** (Marcel, 2026-09-24). The
  page that shows them picks them: a testimonial picker on the course form
  and on the homepage form still to come. The `featured` flag this step first
  had was dropped the same day.
- **One save button**: a testimonial is three lines, so the form has no
  *Speichern und Weiterbearbeiten* (`ResourceForm`'s `stay`).
- **Where it stands is a placement table**, `testimonial_placements`
  (Marcel, 2026-09-24) — not a foreign key on the testimonial, because one
  quote can stand on the Rhino course, Rhinoceros and the homepage at once,
  each page in its own order. Polymorphic: a course or a software today
  ([[HasTestimonials]]), the homepage once it has a record, any new kind of
  page without a schema change. The pickers that write it come with the
  pages; the list and the form already show *Verwendet auf*, and deleting a
  placed testimonial says which pages it leaves. A page that is force-deleted
  detaches itself, since no foreign key reaches a morph.
- **And what it is about — *Bezieht sich auf*** (Marcel, 2026-09-24). A
  placement says where a quote is shown; a picker also needs to know which
  quote belongs to which course. So a testimonial has one optional subject —
  a course, a software, or none for VIAK as a whole (`subject_type` /
  `subject_id`, polymorphic like the placements). The form offers it as a
  grouped select, courses by number then software; the list shows it as a
  hint (*14 SketchUp Kurs*), and the pickers will show it the same way
  and sort a page's own quotes first. This brought **grouped options and a
  placeholder** to the kit's `select`. (The list's hint was *zu: 14 SketchUp
  Kurs* at first; the *zu:* was dropped the same day.)
- **The list is legacy's content list**, measured on its News screen: the
  header with its `+`, then one stacked row each (`list/EditableListItem.vue`):
  what it is about under the title, the quote over its author across the rest
  of the row, then where it is used. **Not dragged** (changed 2026-09-24): each
  page orders its own testimonials in the picker, so `PATCH testimonials/order`
  is gone. Unpublished rows are grey; *nicht publiziert* and *Noch nicht
  verwendet* are badges (*Polish*, below).
- **The form is the course form's frame**: the fields, *Publizieren*, the
  danger zone, the leave guard.

## Step 5 — the field kit, extracted 2026-09-24

From the course and testimonial forms, once both existed by hand — the
`04-content.md` plan, *Build it from two forms, not zero*.

- **A form is declared once, in PHP** — `app/Forms/`: a [[Schema]] of [[Field]]s
  beside the request it validates. **The request takes its rules, messages and
  German attribute names from it**, and the dashboard draws it from
  `GET /api/admin/forms/{form}`. A test walks every schema and checks that
  what the form marks required is exactly what the server requires.
- **The inventory stayed closed**: text, number, textarea, richtext, checkbox,
  checkboxes, select, repeater, section, row, custom — plus **hidden**, which
  the kit needed on first contact: a repeater row's `uuid` is not drawn, and
  without a rule validation dropped it and every saved video came back as new.
- **What a form *does* stays in its request** — German written as
  `['de' => …]`, HTML cleaned, relations synced. That genuinely differs.
- **`defaults` is a new record**: every field the form sends, so create and
  edit have one shape. It carries the next course number, which retired
  `/courses/options`.
- **The Vue half**: `FormNode` draws any schema with the twin components;
  `useResourceForm` holds what both forms repeated — load, *what it loads is
  what it saves* (the schema's keys are the form, the rest is `meta`), the two
  save buttons, the leave guard, errors by path (`videos.0.code`), a section
  turning red; `ResourceForm` is the frame. The course form is now 20 lines,
  the testimonial form 20.
- **Custom parts plug in by name** and take part through hooks: the image
  section reports what it holds, and uploads it once a new course exists.

## Step 6 — the course-date form, built 2026-09-24

*Veranstaltung hinzufügen* / *bearbeiten*, legacy's form in its order, measured
against it at 1291px: [[EventSchema]], `views/Event/Form.vue`.

- **The public API's event writes moved to `/api/admin`**: `POST
  courses/{course}/events`, `GET`/`PUT`/`DELETE events/{event}`, beside the
  state change already there. `StoreEventRequest` is gone; its rules and
  `EventApiTest`'s guarantees live on in `SaveEventRequest` and
  `tests/Feature/Admin/EventFormTest.php`, in German. The public side reads
  only.
- **The kit grew two types and no custom part**: `date` and `time`, typed
  `TT.MM.JJJJ` / `hh.mm` with the dots put in as legacy's mask did, sent
  `Y-m-d` / `H:i`. A half-typed day is sent as typed, so the server refuses
  it by name: *mit vierstelligem Jahr* (the two-digit-year bug that hid 14
  events in 2025). **The date builder is a repeater drawn `inline`**, not the
  `Field::custom()` planned above — it is exactly a list of the same small
  form. `row` takes `columns: 2` for *min.* / *max. Teilnehmer*.
- **Rules legacy had and the API had lost**: *Ort* and at least one expert
  are required. **Experts are checkboxes over the Expert role only**, so a
  student's uuid fails validation instead of being quietly dropped.
- **Cross-checks wait only for their own fields**: max below min is reported
  even when a day is mistyped.
- **A past date is locked**, as legacy's `form.is-disabled`: fields and
  *Speichern* at 40%, no delete box (`locked` on [[ResourceForm]]). Past means
  the first day has gone by, as legacy has it: a date running today is locked.
- **Deleting is refused while any booking exists**, cancelled ones included:
  they carry invoices, as the course form already rules.
- ~~**Not built: *Bestätigen*, *Schliessen*, *Absagen*.**~~ **Bestätigen and
  Absagen built 2026-09-29** with their mails: legacy's green and orange boxes
  between *Speichern* and the delete box, each behind a confirm, turning into
  *bestätigt am …* / *abgesagt am …* (`ActionBox`, the `actions` slot on
  [[ResourceForm]]). **Abschliessen too**, once the date has run, with its
  text corrected: legacy's said the experts are told, and they never were.
- Found on the way: the dev expert and dev admin had no uuid (the seeder
  found their rows, so `HasUuid`'s create hook never ran), so the dev expert
  could not be ticked. The seeder fills it now.

## Step 6 — experts, built 2026-09-29

*Experten* and *Experte hinzufügen / bearbeiten*, legacy's
`views/expert/Index.vue` and `Form.vue`: [[ExpertSchema]],
`views/Expert/`, `/api/admin/experts`.

- **The list is legacy's**: *Aktive Experten* open and **dragged into the
  Experten page's order** (`expert_profiles.order`), *Inaktive Experten*
  closed unless a search finds someone in it, greyed. A row is *name, city*
  and the address as a `mailto:`. Twenty people, so it loads whole and
  searches in the browser (name, e-mail, city); dragging is off while a
  search is active, as on *Kurse*. Active means *Experte aktiv*
  (`publish`), as legacy splits it.
- **The form, in legacy's order**: gender, names, company, e-mail, phone,
  address, country, newsletter; *Experte anzeigen* / *Experte aktiv*;
  *Benutzer-Rollen*; *Über* (title, bio in the editor); *Profilbild*.
  **Legacy's required fields are kept**: all 20 experts have gender, street,
  ZIP, city and country (checked 2026-09-29), so no edit forces anyone to
  invent one. Legacy's collapsible around the three role boxes, always open,
  is a plain checkbox group (four to a row, as its `span-3`).
- **The two flags stay two.** On the 2026-09-11 data they are always equal
  (10 both on, 7 both off), but the Experten page reads both and a merge is
  a data change, not a form change.
- **Roles are on the form** (Admin, Experte, Student) — they belong to the
  person, wherever the person is edited, and the student form will carry
  the same group. At least one. **An admin cannot take their own Admin role
  away**: nobody could give it back from a screen they can no longer open.
  Taking the Expert role away takes the person off this list; `{expert}`
  binds only Expert-role users.
- **E-mail is unique across every account, deleted ones too**, with a German
  message. Legacy let two people share one. An address the admin changes
  ~~stays verified, as legacy has it~~ **must be confirmed by the person**
  (Marcel, 2026-09-29): the *Bestätigung* mail goes out and the form notes
  the address is unconfirmed ([[RequireEmailConfirmation]]).
- **Invited.** Creating an expert makes the account with a password nobody
  knows, verified as legacy does, and mails *Dein VIAK-Zugang* with a link to
  set one ([[AccountInvitation]], built with the mails on 2026-09-29).
- **Deleting is for someone nothing points at** (#16): refused when the
  person has taught a date, booked, been invoiced, has a document, a message
  or a checkout (`User::hasHistory()`), or is you. That leaves four of
  today's twenty. What remains is a hard delete, portraits and all, so the
  address can be used again; legacy soft-deleted and kept it taken. Anyone
  else is switched off instead, and the danger zone says so.
- **The portraits are the course's image section**, told whose images they
  are (`owner`): teaser, visual and OpenGraph, as all 20 experts have
  today. The media Actions already took any owner; `MediaController` got
  expert entry points beside the course ones. A new expert's portraits wait
  in the browser until the first save, as a new course's do (legacy said
  *Bilder können erst nach dem Speichern hochgeladen werden*).
- **The bio goes through `EditorHtml`**, as the course texts do. Checked on
  the 17 real bios: the text and the tags survive; eight are reserialised
  (`<br />`, `&amp;`) and five lose a trailing empty paragraph.
- Found on the way: **country codes are lowercase** (`ch`). The form first
  defaulted to `CH`, which MySQL matched and `Rule::in` refused; a test now
  checks that the default is one of the offered options.

## Step 6 — students, built 2026-09-29

*Studenten*, *Student hinzufügen / bearbeiten*, and deactivating:
[[StudentSchema]], `views/Student/`, `/api/admin/students`. The student
*page* (bookings, documents, *Annullieren*) is step 7; the arrow on each row
leads to its `Pending` for now.

- **The list is legacy's row** (name and city, e-mail, phone, pencil, and the
  arrow under it 40px down, `icon-arrow-right.is-absolute`) **searched and
  paged on the server**: 50 at a time with *Weitere laden (50 von 570)*. Every
  word of the search must match one of name, e-mail, city, phone, company, so
  *achermann basel* finds one person. `%` and `_` are taken literally.
  *Deaktivierte Studenten* below, whole, opening when a search finds someone
  there.
- **The form is legacy's, with three changes.** *Firma* is on it (259 students
  have one, and legacy's admin form could not show it). *E-Mail* is on edit as
  well as create, unique across every account. Legacy's admin-typed password
  is gone: the student sets their own through the invite (#26), which is
  chunk 10, so for now the account is created silently
  ([[CreateAccount]], shared with the expert form, is where the invite goes).
  Legacy's required fields stay, phone included; five of 570 students lack one
  and are asked for it on their first edit.
- **Roles, as on the expert form**, with the same guard against removing your
  own Admin role. Gender, e-mail, country and roles are declared once in
  [[Schema]] and shared by both forms, and so is the request
  ([[SavePersonRequest]]).
- **Invoice addresses are rows of the form** (*Rechnungsadressen*), where
  legacy had two sub-screens. They take the portal's rule, not legacy's: a pair
  of names or a firm ([[StoreAddressRequest]]). A row left out is
  soft-deleted, as the portal deletes; a uuid that belongs to someone else is
  ignored, not updated. Invoices keep their own copy of the address, so
  editing one changes no invoice. The repeater gained *Entfernen* for rows
  with no checkbox to stand beside.
- **Deactivating (#16)** is new: `users.deactivated_at`.
  - The red box on the form says *Konto deaktivieren* where legacy's said
    *Student löschen*, asks first, and turns into *Konto reaktivieren*. The
    form notes since when. You cannot deactivate yourself.
  - **The login refuses a deactivated account** and says why, but only after
    the password matched, so it tells nobody else the account exists
    ([[FortifyServiceProvider]]).
  - **A session already open ends on its next request** ([[SignOutDeactivated]],
    on the `web` and `api` groups): the site redirects to the login with the
    message, the API answers 401.
  - `ResourceForm` takes a `danger` slot for this, in place of the delete box.
- **Not enforced yet, and where it will be**: an admin booking for a
  deactivated student must refuse (step 7, *Teilnehmer hinzufügen*); a
  deactivated expert can still be ticked on a course date (`EventSchema`), and
  the expert form cannot deactivate anyone yet, it only switches the public
  profile off.

## Step 6 — discount codes, built 2026-09-29

*Rabatt-Codes*: [[DiscountCodeSchema]], `views/DiscountCode/`,
`/api/admin/discount-codes`.

- **The list is legacy's two groups**, *Gültige Codes* and *Verwendete oder
  abgelaufene Codes*, but **the split is the checkout's own question**
  (`DiscountCode::isRedeemableOn`: dates, deleted, uses against the limit)
  rather than legacy's `isUsed` flag and `valid_to`. A row is the code, its
  dates and *Eingelöst: 1 von 1*, the amount, the remarks. Newest first,
  about a hundred codes, searched in the browser. Used codes keep their
  pencil (legacy commented it out): every booking keeps the amount it got,
  so editing a code moves no past order. `withUsage()` counts uses in the
  list's query instead of two per row.
- **The form is legacy's**, with the code generated when it opens and shown,
  not typed. Fixed or percent is a select (*Art*) where legacy had two
  radios; the kit has no radio and a two-option select says the same.
  **One new field: *Einlösbar (Anzahl Bestellungen)*, empty for unlimited**,
  the `usage_limit` chunk 06 made a column of. A new code starts at 1.
  Percent is capped at 100, *Gültig bis* cannot precede *Gültig ab*.
- **The generator is new** (`DiscountCodeGenerator`, legacy's alphabet and
  shape). Legacy's `Discount::generate()` retried a collision by calling
  itself and discarding the result, then testing the same code again.
- **Deleting is soft**, as legacy's: bookings and checkouts keep pointing at
  the code, and a deleted code is not redeemable.
- **The local database is stale here, not the port.** Every code reads
  `usage_limit = null` locally, so the 32 codes with no dates show as
  unlimited. `PortUsers` does set them to 1 (the legacy rule); the local
  data was ported before it did. A fresh `port:*` fixes it.
- The kit's `Field` gained `readonly` (the code) and `hint` (*Leer lassen für
  unbegrenzt.*).

## Step 6 — settings, built 2026-09-29

*Einstellungen*: categories, languages, levels, tags and places on one screen,
legacy's `views/setting/Index.vue`. **Two schemas for five lists**:
[[TermSchema]] (a name) for the four terms, [[LocationSchema]] (name, address,
map link, *Publizieren*) for places, and one controller
([[SettingController]], `/api/admin/settings/{kind}`).

- **The screen is legacy's**: a collapsible per list, a row per entry, a `+`
  under each list. The list a form returns to is open (`?liste=tags`), as
  legacy's `:type` param did. Each row says where it is used (*In 13 Kursen*,
  *In 325 Veranstaltungen*).
- **German only**, as the course form: legacy's *Beschreibung (en)* fields are
  not asked for, and English already stored is kept (#6). The list's second
  column, the English name, is gone with them.
- **Nothing in use is deleted.** A term a course is filed under, or a place a
  date is at, cannot be deleted, and the form says how many use it; legacy
  deleted it from under them. What is unused is soft-deleted.
- A new term goes to the end of its list, published. Order and *publish* on
  terms are not on the form, as they were not in legacy; the order is the one
  the port brought across.
- Software is not here: chunk 05 gives it its own screen.

## Step 6 — profile, built 2026-09-29

*Mein Profil* (the profile icon): legacy's `views/admin/Index.vue`,
[[ProfileSchema]], `/api/admin/profile`.

- **Legacy's fields**, then *Zugangsdaten*: e-mail, new password twice, and the
  current password. **The rules are the portal's, not legacy's**: changing the
  address or the password asks for the current one, and a new address is
  unverified until confirmed. It goes through the portal's own
  [[UpdateProfile]], so there is one place that does it; legacy asked for
  neither, on any of its three copies.
- **The form is the view.** Legacy showed the details with a pencil to open
  the form; here the form is the page, and it stays after saving
  (`singleton` on [[ResourceForm]]: no id in the path, no list, nothing to
  delete).
- Password inputs carry `autocomplete="new-password"`: a browser filling a
  remembered password into *Neues Passwort* would change it on the next save.

## Step 7 — invoices and export, built 2026-09-29

Taken ahead of mail: neither screen sends anything, so neither waits on
chunk 10 (the event page and the student page still do).

**Rechnungen** ([[InvoiceController]], `views/Invoice/`). Legacy's four lists
and its row (number as the PDF link, date, amount, *Name, Ort*), the pencil on
open and overdue, the download icon on paid and cancelled. Changed:

- **Each list is its own request, searched and paged on the server**, 50 at a
  time: the paid list is 542 rows. Search is every word against the number and
  the student's names, city and company.
- ***Fällige Rechnungen* second and open**, where legacy had it third and
  closed behind the paid ones. It is the list somebody acts on.
- **The PDF comes through `/dokumente/{uuid}`** and [[UserDocumentPolicy]],
  not the public disk legacy linked to. `Invoice::document()` finds it.

**Rechnung bearbeiten** ([[InvoiceSchema]], [[ChangeInvoiceAddress]]). The
address and nothing else, as decided above, **as fields**, not legacy's free
text: the QR slip prints the payer from the structured snapshot and cannot
split a text. Saved as `UserAddress::toSnapshot()`'s shape, then the PDF is
rendered again. Only while the invoice is owed: a paid or cancelled one
answers 409 and its form opens locked. The fields start from the frozen
address, or from the student's where there is none (431 ported invoices);
the 131 with printed `lines` start from the student's too, and the note says
what the invoice prints today. Run My Accounts is not told, as legacy did not.

**Exporte** ([[CourseParticipantsExport]], `GET /api/admin/exports/courses`).
Legacy's workbook: a sheet per course with past participants, its eleven
columns and headings, bold heading row, autosized. **Checked against the
legacy database the same day: 31 sheets, 503 rows on both sides**, once the
two local `dev@viak.test` bookings are left out. Sheet names are made legal for
Excel (no `: / \ ? * [ ]`, 31 characters, never twice), which legacy did not
do. Written with `phpoffice/phpspreadsheet` directly (new dependency), the
library under legacy's `maatwebsite/excel`. Fetched through the API client as
a blob, so the session and the top bar work as for every other request.

## A course has Veranstaltungen, 2026-09-29

Marcel: **a *Kurs* has *Veranstaltungen***, which is legacy's word throughout
its dashboard (*Veranstaltung bestätigen*, *Veranstaltung löschen*). The
dashboard had said *Kursdatum* in its URLs and some of its text. Now
`/dashboard/veranstaltung/{uuid}` (`/bearbeiten`, `/nachricht`) and
`/dashboard/kurs/{course}/veranstaltung/erfassen`, and *Veranstaltung* in
every label, title and message. Two *Kursdatum* stay on purpose: the public
course page's *Neues Kursdatum folgt in Kürze* (legacy's copy, held to
parity) and the Excel export's column heading, which is the date and
legacy's heading. English prose and code keep *event* / *course date*.

## Counts are badges, 2026-09-29

Marcel, while step 6 was being built: **a number that counts records or
relationships is drawn as a [[Badge]]**, not as text. So: the totals in
collapsible titles (*Aktive Studenten 570*, *Tags 21*), where a term is used
(*13 Kurse*), a discount code's use (*1 von 1 eingelöst*), and on *Kurse* the
participants and laptops (*3 / 8 Teilnehmer*, green when full, as the text
was). A sentence in a form note or a danger zone keeps its number inline.
**A zero is not drawn** (Marcel, 2026-09-29): a collapsible whose list is
empty says so in its own line, and a *0* beside the title only repeats it.

## Step 7 — the course date's page, built 2026-09-29

`/dashboard/veranstaltung/{uuid}`, legacy's `course/event/Show.vue`, built **as far
as attendance needs** (Marcel, 2026-09-29: non-participants get no
participation confirmation, so attendance has to be recorded):

- the course in the aside, *Informationen* (the date's row, as on *Kurse*),
  and *Teilnehmer*: name, city, firm (or the one billed), e-mail, *Mietcomputer*,
  and legacy's tick under *Teilgenommen?*. Once the date is closed the tick
  is *Ja* / *Nein*; a called-off date shows neither.
- stored as `bookings.participated_at` (legacy's `hasParticipated`, ported
  with the time it was set). **Superseded the same day**: the tick moved into
  closing, see *Attendance is asked when closing*.
- **The rest of legacy's page, the same afternoon**, as UI over what the
  expert portal already does ([[EventPageController]]; the portal's two
  requests now take a bound `{event}` as well as its `{uuid}`):
  - ***Teilnehmer hinzufügen*** opens a lightbox that searches as *Studenten*
    does, on the server, and books with one click ([[CreateBookingForUser]]:
    today's fee, the booking mails). Name, place and e-mail per hit (three dev
    fixtures share a name). Refuses a deactivated account (owed since
    *Step 6 — students*) and a date that is closed or called off; capacity is
    not checked, as legacy did not. Each participant's name links to their
    student page.
  - ***Teilnehmerliste (PDF)*** is the portal's [[RenderParticipantList]],
    fetched as a blob like the Excel export.
  - ***Nachrichten***: the portals' message row and box ([[MessageRow]], the
    Vue port of `row/message.blade.php`, Marcel 2026-09-29: match legacy):
    date, sender, 35 characters of the body and a teal *Anzeigen*, opening
    legacy's box with *Datum* and *Absender* over a rule, the subject, the
    body through [[RichText]], and *Anhänge* under a rule. The plus opens
    ***Nachricht erstellen***, legacy's screen and the expert portal's form
    class for class: legacy's sentence in the aside, *Betreff*, the editor,
    *Anhänge (max. 32 MB)* with a rule under the drop box, *Kopie der
    Nachricht an mich* over a rule, a full-width *Senden*. One multipart
    POST, the mails on [[MessagePosted]]. The body is cleaned by
    [[MessageHtml]] whatever `body_format` says. The portal's composer took
    legacy's label and lost the recipient count in its sentence the same day.
  - ***Kurs-Dokumente***: legacy's rows (Marcel, 2026-09-29, from legacy's
    page), [[FileRow]], the Vue port of the portal's `row/file`: the name
    (caption and file name where there is a caption), uploaded, size,
    *Download* over *Löschen*. The plus opens ***Dokumente hochladen***
    (`/dashboard/veranstaltung/{uuid}/dokumente`), a screen of its own as
    legacy has it and as the expert portal rebuilt it: the drop box
    ([[DropBox]]), the chosen files listed, a full-width *Speichern*, and
    **nothing uploaded before *Speichern*** (legacy uploaded on drop). **Each
    file has legacy's optional *Bezeichnung* again** (Marcel, 2026-09-29), on
    this screen and on the expert portal's (`x-form.file-input captions`),
    sent as `captions[]` in the files' order and stored as `media.caption`
    ([[UploadEventMediaRequest::uploads]]). The composer's *Anhänge* is the
    same box, without it. The date's row here has no *Details*, which would lead
    back to the page. A message's
    attachment cannot be removed here (404).
  - **Checked in the browser** on the dev fixtures: a student added, a message
    sent, and in MailHog the three message mails, the booking confirmation and
    the office notice.

## Step 7 — the student page, built 2026-09-29

`/dashboard/student/{uuid}`, legacy's `student/Show.vue` ([[StudentPageController]],
`views/Student/Show.vue`). The address and contact beside the name, *Bearbeiten*,
then four collapsibles:

- ***Gebuchte Kurse*** and ***Absolvierte Kurse*** **split on the course's date**,
  as the portal splits them, not on legacy's flags (which keep 67 seats on
  courses long over under *Gebuchte Kurse*, each with a live *Annullieren*).
  The row is [[BookingRow]], on [[EventRow]]'s geometry. A past seat says *Teilgenommen*, or *Nicht teilgenommen*
  once the date is closed.
- ***Annullierte Kurse*** is new: when (*Annulliert am …*) and by whom
  (*Durch Student*, *Durch VIAK*, *Durch VIAK, ohne Kosten*), both badges;
  a course called off says so in its own state badge. Shown only when there is one.
- ***Dokumente***: all of them, in the portal's 4 / 3 / 5 row with legacy's
  teal *Download* button (Marcel, 2026-09-29, from legacy's page), where
  legacy showed five and linked the rest.
- The heading is legacy's *Profil Student*, not the name; no row has *Details*,
  and *Annullieren* is the red danger button (Marcel, 2026-09-29).
- ***Annullieren* asks whether the cost is charged (#14)** when
  [[CancellationPenalty]] finds one today: the dialog names the amount and the
  rate and offers *Mit Kosten annullieren* and *Ohne Kosten annullieren*
  (`confirm()` gained `choices`). Outside the window it only asks.
  `PATCH /api/admin/bookings/{booking}/cancel` with `charge_penalty`. A waiver
  is **its own reason**, `administrator_waived`, written only when there was a
  cost to waive, so it stays readable in the data; it charges nothing, withdraws
  an unpaid invoice and credits a paid one, like a free cancellation. Only seats
  on courses still to come, as in the portal.
- **Checked in the browser** on the dev fixtures: a waived late cancellation
  moved to *Annullierte Kurse* with no invoice.
- Not built: legacy's separate *Alle Dokumente* screen (not needed, the list
  is whole). The old `/api/bookings/{booking}/cancel` still charges when an
  admin uses it on someone else's seat; the dashboard no longer does.

## Attendance is asked when closing, 2026-09-29

Marcel, on the event page as first built (a tick per seat there, the close on
the edit form, two screens for one decision):

- **The event page no longer ticks.** Each seat shows its attendance as a
  badge, always ([[AttendanceBadge]]): *Teilnahme offen* until the event is
  closed, then *Teilgenommen* (green) or *Nicht teilgenommen* (red). The
  *Teilgenommen?* header is gone. The student page shows the same badge on
  booked and past seats.
- **Closing asks who attended.** The edit form's *Veranstaltung abschliessen*
  box (button now *Abschliessen*) opens a lightbox, *Teilnehmer «Kursname»*,
  listing the live seats **none ticked** (Marcel, on second thought: tick
  who came), and **at least one** is wanted where there are seats: the
  lightbox says *Bitte mindestens einen Teilnehmer auswählen.*, and the
  server refuses it too.
  *Abschliessen und Bestätigungen senden* is one request,
  `POST /api/admin/events/{event}/close` with the attended booking uuids
  ([[EventPageController::close]]): the ticks and the close in one
  transaction, so they cannot disagree, then the confirmations to the ticked
  seats. Only once the date has run, and only once.
- **The plain state switch refuses `closed`** ([[SetEventStateRequest]]), and
  the per-seat tick endpoint is gone.
- **A seat missed at closing is confirmed afterwards** (Marcel, 2026-09-30,
  #41). On a closed date, a *Nicht teilgenommen* badge has *Bestätigen* under
  it: a confirm dialog, then `POST /api/admin/events/{event}/bookings/{booking}/confirm`
  ([[EventPageController::confirm]]) records the seat as attended and queues
  the same confirmation closing sends. Only on a closed date, only a live
  seat of that date, and only one not yet attended, so nobody gets a
  second. Checked in the browser on event a85ae67b (Ursula Roth).

## The walkthrough against legacy, 2026-09-30

Each list and form beside legacy's at `viak.ch.test`, measured where they
differed. Fixed:

- **The lists sat 20px high.** Legacy's `.collapsible-container` is
  `mt-12x md:mt-16x`, 24px and 32 from `lg`; only *Kurse*' course list has
  the tight `is-course-events` 6px. The other lists had copied *Kurse*' 12px.
  *Experten*, *Studenten*, *Rabatt-Codes*, *Einstellungen* and *Rechnungen*
  now measure as legacy's: rule, heading and first row to the pixel.
- **A count made the heading taller**: the badge is 23px on an 18px line, so
  a heading with one was 45px where legacy's is 40. `ui/Collapsible.vue` takes
  a `count` now, as its Blade twin does, and draws the badge with `-my-4`;
  the twelve hand-placed badges are gone.
- **The heading's label is 14/16/18px** in the Vue collapsible, as legacy's;
  it inherited 16px on a phone.
- **Course numbers have two digits**, *07 Rhino*, as legacy prints them
  (`Course::displayNumber()`, `courseNumber()` in `support/format.js`), on
  the dashboard and the portals. The number column stays an integer; *Kurse*'
  search finds *07*.
- ***Rechnungen* has legacy's column labels back** (*Nummer*, *Datum*,
  *Betrag*, *Student*), sticky over each list, and from `sm` only: on a phone
  legacy stacks them into four lines over nothing. Amounts are bare, *600.00*,
  as legacy's, now that the column says *Betrag*.
- ***Rabatt-Codes***: a validity reads *22.01.2026 – 22.01.2027*, legacy's
  en dash, not *bis*.
- **The header fits a phone.** Legacy's menu is 3xl with 48px gaps on a phone
  and its page scrolls sideways to 768px; the rework's did too, to 452. 18px
  and 24px apart below `sm` fit 390px with both icons.

- **The event page's participant row** was legacy's 2/2/2/3/1/2: *Mietcomputer*
  overran its one column and *Nicht teilgenommen* its two. Now 2/2/2/3/3 with
  the seat's badges together against the far edge, *Mietcomputer* one of
  them, as the expert portal's twin draws the row.

Left as they are, all decided earlier: the short date and the badges on
*Kurse*, the usage badge on a discount code, no DE/EN switch and three
toolbar buttons on the course form (with the text in black, as the student
will read it), labelled checkbox groups, the role group as plain checkboxes,
*Speichern und Weiterbearbeiten*, *Software* out of *Einstellungen*, and
pencils and search on a phone, which legacy hides.

*Experte anzeigen* and *Experte aktiv* stay two plain checkboxes on one line
(Marcel, 2026-09-30), where legacy asks two questions with a *Ja* box each.

## Zurich time, 2026-09-30

Open question #40, Marcel: yes. Legacy and the rework ran in UTC, so every
time shown was two hours off in summer, and a date read off a moment
(*Annulliert am …*) was the day before for anything after 22:00.

- **`app.timezone` is `Europe/Zurich`.** Moments are stored as Zurich wall
  clock; `toIso8601String()` sends them with `+02:00`, so the SPA's
  `slice(0, 10)` takes the Zurich day.
- **Both MySQL connections are pinned to `+00:00`.** Every moment is a
  `TIMESTAMP`, which MySQL converts through the session's zone. Left at
  `SYSTEM` it was CEST on a Mac and whatever the host is in production; pinned,
  a string reads back as it was written on any server. A named zone would need
  time-zone tables a host may not have.
- **The port shifts legacy's moments** from UTC ([[LegacyTime]]): read at
  `+00:00`, legacy's strings are UTC (its messages begin at 04:00 that way,
  06:00 in Zurich). Days (`date`, `due_at`, an event's days) are not moments
  and go across as they are.
- **The local database predates this.** Rows written before it read two hours
  early until the database is re-ported, or shifted once with
  `convert_tz(col, '+00:00', 'SYSTEM')` over every `TIMESTAMP` column.
- `failed_jobs.failed_at` defaults to MySQL's `CURRENT_TIMESTAMP`, which is UTC
  at `+00:00`. Nothing reads it but a person.

## Back is where you came from, 2026-09-29

Marcel: *Zurück* always went to a fixed list (the event form to *Kurse*),
wrong when the event was opened from a student's page. Now *Zurück* and
*Speichern* go **back in the history** when the page before was a dashboard
screen ([[goBack]], from vue-router's `history.state.back`), and to the list
as it was left only when the page was opened directly (a mail, a bookmark).
Going back rather than pushing keeps the history a path: student, event,
*Bearbeiten*, *Speichern*, *Zurück* lands on the student again. *Löschen*
still goes to the list, since the page before may be the record deleted.
Checked in the browser along that path, and opened directly.

On the event page, *Teilnehmerliste (PDF)* with an arrow became
*Teilnehmerliste* with the invoices' download icon.

## Loading, built 2026-09-24

Each screen had its own *Wird geladen …* and nothing else. Nothing showed
while a screen's code loaded on first open, or while the new order or a
delete was saving.

- **Legacy's bar**: NProgress, 2px teal across the top, spinner off
  (`_progress.scss`). Legacy started and stopped it by hand in 38 files. Here
  it is a count (`useProgress`): the API client counts every request and the
  router counts a navigation, so no screen calls it.
- **It waits 150ms before showing**, and finishing waits one turn of the
  event loop, so a screen's navigation and its data load are one run of the
  bar. Checked in the browser: a real request under 150ms never draws it.
- **Skeletons and `<Suspense>` were considered and not used.** Legacy had
  neither, and Suspense would change how every screen loads.
- *Wird geladen …* is now `<Loading>`, shown after 200ms so a fast load
  doesn't flash it. *Löschen* is disabled while the delete runs, as
  *Speichern* is while saving.

## Polish, 2026-09-24

Small fixes after step 6, most of them from Marcel using the screens.

- **One overlay** (`ui/Overlay.vue`): the veil, the teleport, Escape heard on
  the document, and a veil click that only closes when the press began on the
  veil. The lightbox and the confirm dialog both sit on it. Escape closes only
  the topmost, so a confirm over the cropper leaves the cropper open, and a
  crop drag released outside the box no longer closes it. The close cross is
  fixed at the window's top right on every lightbox, and the cropper ignores
  veil clicks (`closeOnBackdrop`).
- **`ui/Badge.vue`**, a coloured border with bold text in the same colour
  (neutral, success, warning, danger), for course-date states and the
  testimonial list's *nicht publiziert* and *Noch nicht verwendet*.
- **Deleting a testimonial asks with the start of its quote and its author**,
  on lines of their own; the confirm dialog's text keeps line breaks.
- **A short date in *Kurse*'s chronological column** (`shortDate()`,
  *24.09.2026*): the long form broke over two lines.
- **A form goes back to the list as it was left.** The router remembers each
  screen's last query (`returnTo()` in `router/index.js`); *Zurück*,
  *Speichern* and *Löschen* use it, so a search or a mode survives an edit.
  The menu's *Kurse* still opens the plain list.
- **No long dash in the dashboard's text**: a full stop, comma, parentheses or
  colon instead.

## A navigation for it

**Not taken up**: step 1 kept legacy's menu as it is (Kurse, Experten,
Studenten, the rest behind the burger). What was proposed:

Legacy's top bar holds Kurse, Experten, Studenten; everything else sits in a
hamburger. Proposed, grouped by what the admin is doing:

- **Kurse** — courses, and each course's dates; **Veranstaltungen**, every event,
  chronologically (today's `/dashboard/termine`)
- **Personen** — Studierende, Experten
- **Verkauf** — Rechnungen, Rabatt-Codes, Export
- **Inhalte** — Startseite, Testimonials, Aktuelles, Medien
- **Einstellungen** — the taxonomies, Standorte
- Profil, Abmelden

## Build order

1. **Guard the shell** (`auth`, `role:admin`), and the list/detail/confirm
   building blocks the operational screens share. (Built, *Step 1*.)
2. **Course form, by hand** — kit form #1, against the existing API. (Built,
   *Step 2*.)
3. **Media admin** — ported from `forrerzimmermann.ch`, so a form can pick and
   crop. (Built for the course's images, *Step 3*; no standalone *Medien*
   screen yet.)
4. **Testimonials, by hand** — kit form #2, and the model with it. (Built,
   *Step 4*.)
5. **Extract the kit** — `Field::` schemas beside the requests,
   `GET /api/admin/forms/{form}`, `FormNode`, error mapping, leave guard. Move
   both forms onto it. (Built, *Step 5*.)
6. **The CRUD that remains, on the kit** — ~~event form~~ (built, *Step 6*),
   ~~experts~~, ~~students~~, ~~discount codes~~, ~~taxonomies~~,
   ~~profile~~ (built, *Step 6 — …*).
7. **Operational screens, by hand** — ~~the event page~~, ~~the student page~~,
   ~~invoices, export~~ (built, *Step 7 — invoices and export*).
8. **Homepage schema** once #23 is answered; **Aktuelles** once #22 is.

**Mail (chunk 10) runs alongside, and gates step 7.** Confirming an event raises
invoices and must tell participants; an admin booking must confirm itself to the
student. Built before mail exists, those buttons would do half their job
silently. Steps 1–6 do not depend on it.

## Decided — 2026-09-29

Marcel, on the choices step 6 and the mails made without asking:

- **Roles stay on both person forms**, and any admin may change anyone's
  roles, Admin included, except their own Admin role. The whole dashboard is
  admin-only; nobody changes roles from the portals.
- **The expert delete rule stays**: only someone nothing points at is deleted,
  fully, so the address is free again. Anyone else is switched off.
- **No automatic reactivation.** A deactivated address that tries to register
  again is told to get in touch; an admin reactivates it.
- **An address an admin changes must be confirmed too**, like a person's own
  change: the *Bestätigung* mail goes out and the address is unverified until
  clicked.
- **Settings keep refusing to delete what is in use.**
- **Credit codes come back** (`10-mail.md`): a paid invoice on a cancelled
  seat becomes a discount code, as legacy did, named in the mail with the offer
  of a refund instead.

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
