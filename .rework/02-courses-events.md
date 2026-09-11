# 02 — Courses & Events

The first vertical slice: one domain taken through every layer, so the
conventions in `00-foundation.md` are proven on real data before the remaining
twelve modules repeat them.

## Status

Built. 37 tests green. 214 of 227 live events ported, 13 skipped with reasons.
Public pages at `/kurse` and `/kurse/{slug}`, dashboard at `/dashboard/termine`.

## Why this domain first

Courses and events are the heaviest part of the legacy model (`Event` 448 LOC,
`Course` 394 LOC) and they touch a public page, a dashboard CRUD surface and the
filter. Everything the later chunks need — translatable content, taxonomies,
state transitions, policies, a data port — appears here once.

## What was built

| Layer | Files |
|---|---|
| Enums | `EventState`, `Role`, `Locale` |
| Models | `Course`, `Event`, `EventDate`, `Location`, 5 taxonomies, `User` |
| Actions | `CreateCourse`, `UpdateCourse`, `CreateEvent`, `UpdateEvent`, `SetEventState` |
| Requests | `StoreCourseRequest`, `UpdateCourseRequest`, `StoreEventRequest`, `SetEventStateRequest` |
| Resources | `CourseResource`, `CourseSummaryResource`, `EventResource`, `TaxonomyResource` |
| Policies | `CoursePolicy`, `EventPolicy` |
| Controllers | `Api\CourseController`, `Api\EventController`, `Site\CourseController` |
| Support | `Slug`, `CourseNumber`, `RichText` |
| Port | `port:courses` |
| SPA | `views/Courses.vue`, `views/Events.vue`, `stores/events.js`, `api/` |
| Public | `site/courses/{index,show}.blade.php` + site components |

## Decisions

### State is one column, not two mechanisms

Legacy wrote event state to **both** nullable timestamp columns and rows in the
spatie `flags` table. They drifted: event 99 has `confirmed_at` set and no
`isConfirmed` flag, and since the app read flags it treated a confirmed event as
unconfirmed. `events.state` is now the single source of truth, written only by
`SetEventState`; the timestamps remain as an audit trail.

Cancelling is terminal. A cancelled event has told students it is off and may
have triggered penalty invoices, so reinstating it throws.

### Roles collapse from a pivot to a column

Of 468 legacy users, 465 hold exactly one role and all three exceptions are
Admins who additionally hold Expert and/or Student. The pivot only ever
expressed a hierarchy, so `Role` is an enum column with `atLeast()`. Verified
lossless against `viak_legacy`.

### `events.date` derives from the event's dates

Legacy let the two drift, so an event could sort under one date and display
another (3 events do). `CreateEvent`/`UpdateEvent` derive `events.date` from the
earliest `event_dates` row.

### An event happening today is upcoming

Legacy used `date > today` for upcoming and `date < today` for past, so an event
running *today* appeared in neither list.

### Resources return translation maps

Every translatable field serialises as `{de: …, en: …}` rather than the current
locale's string: the dashboard edits all locales and the public site picks the
one it needs. One shape, both consumers.

### Rich text goes through an allowlist

Course copy is admin-authored HTML rendered unescaped, so it passes through
`RichText::render()` (tag allowlist). When the editor moves to tiptap this
should become a real sanitiser with attribute filtering.

## Data findings from the port

`php artisan port:courses` reconciles and reports rather than silently coercing.
Against the current snapshot:

Against the 2026-09-11 production dump (41 courses, 341 live events):

| Finding | Count | Disposition |
|---|---:|---|
| `events.date` year typed as two digits | **14** | **skipped — needs a decision** |
| Published events with no date at all | 2 | skipped — invisible on the live site too, since a NULL date fails both the upcoming and past comparisons |
| `events.date` disagreed with first `event_date` | 3 | first date wins |
| Course rich text entity-encoded (`&auml;`) | 24 of 35 | decoded to UTF-8 on port |
| English subtitles still lorem ipsum | 6 | content task, not code |
| Event state: flag and timestamp disagree | 1 | flag wins (what the app read) |

### Two-digit years: a live bug, not a historical one

Fourteen events carry a `date` in year 25 or 26 AD, entered in **three separate
batches** — 2025-06-03 (11), 2025-11-13 (1), 2025-11-21 (2). It is an ongoing
failure mode in the live application, not one bad afternoon.

Where `events.date` disagrees with the `event_dates` rows, the latter fell back
to the creation date. For 202, 207, 294 and 295 the two agree and the real date
is recoverable; for the rest only the corrupted column carries intent.

**All fourteen have zero bookings, and that is the finding.** A date in year 25
fails every upcoming-events comparison, so these course dates were published and
then never appeared anywhere on the site. Events 294 and 295 (SketchUp, intended
2026-01-26) were entered 2025-11-21 and would have been sellable for two months.
This is lost revenue, not untidy data.

**Needs a decision:** drop them, or restore from `events.date + 2000 years`. And
separately: the legacy entry form should reject a date before 2000 — a one-line
fix worth making on the live site regardless of the rework.

## Open questions

1. The `0025` batch, above.
2. `courses.reviews` — a `text` column holding what looks like structured data.
   Ported as-is; needs a shape before it can be edited.
3. English is admin-only on the public site today (legacy gates `/en` behind
   `role:admin`). Does the rework ship EN publicly?
