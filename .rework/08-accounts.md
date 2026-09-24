# 08 — Accounts, portals and documents (not yet built)

Who a user is, what they can see of their own history, and the generated
documents that history produces.

> **Numbering.** 07 is left for the editor chunk, already referenced from
> `Support/RichText.php` and `PortCourses.php` as `[[07-editor]]`. This chunk
> also absorbs `[[11-auth]]`, referenced from `resources/js/app/router/index.js`
> — accounts and auth are one surface, not two, and that comment should be
> repointed here when the chunk is built.

## Status

**Partly built, 2026-09-18; both portals and the generated documents added
2026-09-22.** 451 tests green, Pint clean. Scoped by the method that found chunk 06: mapping the legacy
surface onto the rework and looking for what has nowhere to land.

| Part | |
|---|---|
| **Media** | Built — `media` table, Glide renderer, `<x-media.image>`, six Actions, `port:media` |
| **Documents** | Built — private disk, policy-gated download, `port:documents`, and **generated** since 2026-09-22 ([[03-invoices]]) |
| **Messages** | Built — schema, `PostMessage`, `MessagePolicy`, HTTP, `port:messages` |
| **Accounts** | Built — one profile controller for all three roles, addresses, `MustVerifyEmail` |
| **The student portal** | Built 2026-09-22 — four screens at `/de/student/profil`, see `09-public-site.md` |
| **The expert portal** | Built 2026-09-22 — five screens at `/de/experte/profil`, see `09-public-site.md` |
| **Admin user management** | **Not built** |

**Building the student portal turned up six defects**, four of them in code that
was already built and green, and they are listed in `09-public-site.md` under
*The student portal*. Two belong here rather than there:

- **`Event` had no `media()` relation.** `port:media` wrote 13 rows against
  `App\Models\Event` — course materials, zips and workshop PDFs across 5 events
  — and nothing could read them, because a morph with no relation on the owning
  side raises no error, no null and no missing column. `Open-Questions.md`
  already carries *count-check the other pivots the ports fill*; this is the
  second one found by tripping over it rather than by checking.
- **`MessageResource` read `$this->author->firstname`**, a legacy column name.
  Null, swallowed by a `trim()`, right by accident.

`MediaPolicy` and an authenticated `/medien/{media:uuid}` arrive with the
portal, so a course's materials go through a policy the way its documents
already do. **The files themselves are still on the public disk** — moving them
is a port change rather than a view change, and `Todo.md` carries it.

All three ports ran clean against the production snapshot:

| | |
|---|---:|
| `port:media` | 306 rows, 263 with a crop, 25 rescaled, **0 crops outside their image** |
| `port:documents` | 1,005 rows from 1,162, **271 paths repaired**, 157 duplicates collapsed, 0 missing |
| `port:messages` | 251 messages, 1,105 recipients, 33 attachments, 0 skipped |

**Read the security section.** Scoping this chunk turned up a live
account-takeover path on the production site. It is not a rework question and it
should not wait for this chunk — the two profile defects below are now fixed
*here*, but `/expert/finish` still needs fixing *there*.

### What is left

- ~~**The portal screens.**~~ **Both are built, 2026-09-22.** The student's
  four — *Mein Profil*, *Meine Dokumente*, the booked-event view and the
  invoice-address pages — and the expert's five, with the participant list, the
  message composer and the course-materials upload. Finding 5 is settled by
  `EventPolicy::viewParticipants`; finding 4 is settled by `MessagePolicy`,
  which the composer is the first thing to exercise in a browser.

  **One piece is deferred with its policy already in place**: legacy's
  *Teilnehmerliste (PDF)*. Nothing in the rework generates a PDF, and chunk 03
  deferred the QR bill and the participation confirmation to whichever chunk
  builds the document pipeline — so adding dompdf for the smallest of the three
  would set the letterhead conventions for all of them (Marcel, 2026-09-22).
  The route will ask `viewParticipants` the day there is a route.
- **Admin user and expert management.** The dashboard CRUD, which is Vue and
  wants the field kit from chunk 04 rather than ten hand-rolled forms.
- **Fortify's own routes and views** — login, registration, password reset.
  `User` now implements `MustVerifyEmail` and the profile flows are correct;
  wiring the screens is frontend work.
- ~~**Open question 16**~~ — **answered 2026-09-24: deactivated, never deleted**
  (`07-dashboard.md`). Was: is a user with financial history ever deleted, or only
  deactivated? Still Marcel's, and it blocks only the admin screens above.

## Why it exists

Legacy's dashboard has eleven screen groups. The chunks cover six of them.

| Screen group | Vue LOC | Owned by |
|---|---:|---|
| `course` | 2,467 | 02 (built), 04 for the form |
| `setting` | 1,436 | 02 taxonomies, 05 for software |
| **`student` (admin)** | **1,043** | — |
| **student portal** | **839** | — |
| `home` (dashboard grid) | 646 | 04 |
| **expert portal** | **580** | — |
| **`expert` (admin)** | **575** | — |
| `backoffice` | 452 | 03, deferred |
| `discount` | 444 | 06 |
| `team_member` | 437 | 04 |
| `news` | 409 | 04 |
| `hero` | 358 | 04 |
| **`admin` (own profile)** | **169** | — |
| **`files` module** | **505** | — |
| **`messages` module** | **336** | — |
| `images` module | 705 | 04's media field — see below |

**4,047 LOC with no chunk**, or 4,752 counting the image module, against 10,047
in the dashboard as a whole. Roughly **40 % of the admin front end**, and it is
parity work — every screen here exists on the live site today.

On the server side the same gap is **1,556 LOC across 17 controllers**:
`Api/StudentController`, `StudentAddressController`, `StudentRegisterController`,
`ExpertController`, `AdminController`, `UserController`, `EventMessageController`,
`EventFileController`, `FileController`, `ImageController`, their four
`Api/Dashboard/` counterparts, `DocumentController` and `ExportController`.

## What the chunk contains

Four things that share a spine — they are all *a user and what belongs to them*.

1. **Accounts and auth.** Registration, login, email verification, password
   reset, and the profile screens for all three roles. Fortify is already
   installed (`00-foundation.md`) and replaces almost all of it.
2. **The two portals.** *Meine Kurse* / *Meine Dokumente* / addresses for
   students; *Meine Kurse* / files / messages for experts.
3. **Documents.** `user_documents` — 1,162 generated PDFs, which `01-schema.md`
   deliberately left to "whichever chunk owns generated documents". This is that
   chunk.
4. **Files, images and messages.** The three shared modules, all of them
   user-or-event attachments.

The rework already has `User`, `UserAddress`, `ExpertProfile`, `Country` and the
`Role` enum from chunks 01–02, so the data spine exists. What is missing is
`user_documents`, `messages`, `message_user`, `files`, `fileables` and `images` —
and every screen.

## Security — findings on the live site

All six were found by reading the legacy source; the first and the fourth were
then confirmed against the production site. They are recorded here because this
chunk is where the rework's answer to each one lives, but **the first does not
belong on a chunk backlog.**

### 1. Unauthenticated password set — critical, live

`POST /expert/finish` (`routes/web.php:186`) sits outside every middleware group.
It reads a **user uuid from the request body**, sets that user's password and
marks their email verified:

```php
public function store(ExpertStorePasswordRequest $request)
{
  $user = User::where('uuid', $request->input('uuid'))->first();
  $user->email_verified_at = \Carbon\Carbon::now();
  $user->password = \Hash::make($request->input('password'));
```

The confirmation token is checked in `confirm($token)`, the **GET** that renders
the form. It is never checked again on the POST, and `ExpertStorePasswordRequest`
validates only `password` and `password_confirmation` — the uuid is not
validated at all. There is no signature, no expiry and no rate limit.

**User uuids are published.** `/de/experte/{slug}/{user:uuid}` is a public route
and the experts index links to it, so every expert's uuid is on the live site by
design. Verified 2026-09-18.

**This is an account-takeover path against named accounts, and at least one of
the exposed accounts holds an admin role** (`02-courses-events.md` records users
501 and 2 as Admin + Expert + Student).

The fix is small and does not need the rework: resolve the user **from the token**
rather than from the request body, expire the token, and drop the uuid from the
payload. Laravel's signed URLs or Fortify's reset flow both do this correctly,
which is what the rework will use.

### 2. Email and password change without confirmation

`StudentController::update`, and identically for experts and admins:

```php
if ($request->input('new_email')) {
  $user->email = $request->input('new_email');
  $user->save();
}
```

No current-password confirmation on either change, and **`email_verified_at` is
not reset**, so the new address silently inherits verified status without ever
being verified. Fortify's `UpdatePassword` and email-verification flows replace
both.

### 3. Generated PDFs are world-readable

`EventParticipationConfirmation` writes to
`storage/app/public/files/{user_uuid}/` and stores the public path in
`user_documents.uri`. `public/storage` is symlinked, so **every invoice and
participation confirmation is fetchable without authenticating** — 1,162
document rows across 340 user directories, protected only by the uuid in the
path.

**And 239 participant lists are loose in the same public directory.**
`DocumentController::participantsList` writes
`viak-teilnehmerliste-{date}-{random}.pdf` straight into
`storage/app/public/files/`, writes no database row, and nothing ever deletes
them. 27 MB of participant names and contact details, world-readable, referenced
by nothing — so nobody would notice if they were read. Combined with finding 5,
any expert can also *generate* a fresh one for any event.

In the rework these are served by an authenticated route with a policy, and the
files live outside the public root. That is the storage decision `01-schema.md`
said had to come first.

**Closed on both halves, 2026-09-22.** The ported files went onto the private
`documents` disk when chunk 08 was built; the *generator* now writes there too,
so a newly issued invoice or certificate is never reachable without the policy
([[RenderInvoice]]). And the participant list is no longer written to disk at
all — it is rendered on request and streamed, because it belongs to a course
rather than to a customer and is out of date the moment somebody cancels
([[RenderParticipantList]]).

The 294 loose participant lists on the **live** site are still there and still
worth deleting; `Todo.md` carries it.

### 4. Any student can read, and write, any event's messages

`GET /api/event/messages/{event:uuid}` and `POST /api/event/message` are gated by
`role:admin,expert,student` and nothing else — no policy call, and
`MessageStoreRequest::authorize()` returns `true`. So any authenticated student
can read the message thread of an event they have never booked, and post a
message to one, **which mails every participant of that event**.

**Settled in the rework**, `MessagePolicy` — and since 2026-09-22 it is actually
reachable: the expert portal's composer is the first write path on the public
site, and `PostEventMessageRequest::authorize()` asks
`can('create', [Message::class, $event])`. Reading is
`viewForEvent`, which the student portal's booked-event screen asks. Legacy's
FormRequest authorises everything, which is where the hole is; ours authorises
against the object, which is the whole difference this chunk makes.

### 5. Any expert can download any participant list

`GET /pdf/teilnehmer-liste/{event:uuid}` is gated by `role:admin,expert` with no
check that the expert teaches that event. The PDF carries participant names and
contact details. Note that the neighbouring API route *does* check —
`EventController::findExpertEvent` calls `authorize('containsEvent', $event)` —
so this is an omission on one route rather than a missing policy.

**Settled in the rework, 2026-09-22.** `EventPolicy::viewParticipants` states
the neighbour's rule once — *admin, or teaches this event* — and every caller
asks it: the expert portal's course screen, which is what draws the list, and
whatever serves it as a PDF the day there is a PDF. It denies with a **404**
rather than a 403, because answering *forbidden* confirms that the uuid is a
real course.

~~The PDF itself is deferred, not ported~~ — **built 2026-09-22**
([[03-invoices]]). It lives under the portal now, at
`/de/experte/profil/kurs/veranstaltung/{uuid}/teilnehmerliste`, rather than at
legacy's top-level `/pdf/teilnehmer-liste/{event}`, so it inherits the same
object-level check as the screen that links to it and cannot drift away from it
again.

Worth keeping in view that **the PDF is the only place the phone numbers and
email addresses appear** — the screen shows name, town and firm and nothing
more, which is exactly what made the missing check on that one route matter.

### 6. The shape of it

| | Legacy |
|---|---:|
| FormRequests | 28 |
| …whose `authorize()` returns `true` | **28** |
| Policies | 4 |
| `$this->authorize()` calls, all controllers | **9** |

Role is checked by route middleware and is mostly right. **Object-level
ownership is checked in nine places in the whole application**, which is why
findings 1, 4 and 5 all look alike. `00-foundation.md` already makes Policies a
convention; this chunk is where that convention has to actually cover
user-owned data.

## Decisions this forces

### Admin booking-on-behalf is a second create path, and it resurrects the dead

`Api/Dashboard/BookingController::create` is 98 lines that bypass
`Booking::create()` entirely — no basket, no discount, no bookmark clearing, no
messages mailed. More importantly, before creating anything it looks for a
**soft-deleted** and then a **cancelled** booking for that user and event, and
brings it back:

```php
$deletedBooking->restore();
$deletedBooking->unflag('isCancelled');
$deletedBooking->cancelled_at = null;
$deletedBooking->booked_at = \Carbon\Carbon::now();
```

The row keeps its **original number** and its **`course_fee` frozen at the first
booking**, so a re-booked seat can be billed at a price that is two years old.
And nothing touches the **penalty invoice the cancellation raised** — chunk 06
establishes that the penalty fires automatically, so a cancel-then-rebook leaves
a live penalty invoice against a booking that is no longer cancelled.

The rework does not resurrect. An admin booking creates a new booking at the
current fee, and the old cancelled row stays cancelled with its own history. If
the penalty should be waived, that is the deliberate-waiver `CancellationReason`
chunk 06 already adds.

### This answers chunk 06's open question 6

> *Does an admin cancel a booking on a student's behalf, and through which path?*

**Yes, and through the student's own path.** `PUT /api/booking/cancel/{booking}`
carries `role:admin,student`, calls `BookingFacade::cancel()`, and
`BookingPolicy::cancel` allows it for `$user->id === $booking->user_id || isAdmin()`.
So an admin cancellation is indistinguishable from a student's and **the penalty
fires**.

Creating is the asymmetric one: admins have their own path (above), cancelling
they share. That asymmetry is the bug, not the design — and it means chunk 06's
`CancellationReason` needs a case for *cancelled by an admin on the student's
behalf*, distinct both from a student cancelling and from VIAK calling off the
course. Whether that case charges a penalty is a question for Marcel, not a
thing to infer from legacy.

### `files` and `images` are the `Invoice`/`RentalInvoice` pattern again

Two tables, near-identical: uuid, name, original_name, extension, size, caption,
description, order, publish, locked, softDeletes. `images` adds orientation and
four crop coordinates. Then they relate differently for no stated reason —
`files` through a `fileables` pivot (many-to-many), `images` through a
`nullableMorphs('imageable')` (one-to-many).

`04-content.md` question 6 assumes `spatie/laravel-medialibrary` behind the image
field and asks for it to be confirmed against the client's "image handling
(frontend output)" requirement. This chunk is the other caller, and it should be
one decision, not two.

**One thing to check before assuming medialibrary.** The frontend output pipeline
is already a package Marcel maintains — `marceli-to/image-cache` — driving the
URL-based resizer at `/img/{template}/{filename}/{maxSize?}/{coords?}/{ratio?}`
with templates in `config/imagecache.php`. Whether that package or medialibrary
owns the pipeline is a real choice, and the crop coordinates stored per image are
the thing that has to survive either way.

### Deleting a student leaves invoices pointing at a trashed user

`Dashboard/StudentController::destroy` detaches the Student role if the user
holds more than one, and otherwise soft-deletes the user. Nothing considers that
the user may have paid invoices, which chunk 03 treats as documents that do not
change. The rework needs an explicit rule; the cheap one is that a user with
financial history is deactivated rather than deleted.

### PDFs are generated inside a Mailable

`EventClosedStudent` constructs `EventParticipationConfirmation` and writes both
a file and a `user_documents` row while building the email. A document that is
part of the customer's record should not be a side effect of rendering a
message — in the rework the Action creates the document and the mail attaches
it.

**Done, 2026-09-22.** [[RenderInvoice]] and [[RenderParticipationConfirmation]]
are Actions and nothing about them needs a mail. Which also means a document can
be produced for a preview or a test without sending anything, and a retried mail
does not make a second copy.

Two things the certificate corrected on the way:

- **Legacy dates it twice, differently.** The page says `Zürich, {closed_at}`
  and the *filename* is built from `date('d-m-Y', time())` — today — so a
  certificate reissued a year later is filed under a date that appears nowhere
  on it, and two for the same booking cannot be told apart by name.
- **`closed_at` can be null**, and legacy writes it into the row unchecked. The
  event's own date is the fallback here, and that is never null.

## What is *not* in this chunk

- **Bookmarks** — chunk 06, deliberately small (17 rows).
- **The invoice worklist** — chunk 03 deferred it, and it is `backoffice`.
- **`news`, `hero`, `team_member`, the grid** — chunk 04.
- **The Mailchimp sync.** `NewsletterSubscriber::update()` is called from
  registration and from every profile update, so this chunk touches it, but
  whether it survives is the open question `00-foundation.md` already carries.

## What the data says

Measured against `viak_legacy` at the 2026-09-11 dump, 2026-09-18.

| | |
|---|---:|
| Users | 578 (5 soft-deleted, 16 never verified) |
| — Student only | 555 |
| — Expert only | 15 |
| — Admin only | 4 |
| — Admin + Expert + Student | 3 |
| — Admin + Student | 1 |
| `user_addresses` | 125, held by **107 users** (93 with one, 10 with two, 4 with three) |
| `user_documents` | 1,162 — 568 `INVOICE`, 594 `PARTICIPATION_CONFIRMATION` (23 soft-deleted) |
| `messages` | **251**, across 122 events, 10 authors, none soft-deleted |
| `message_user` | 1,105 |
| `files` / `fileables` | 44 / 33 — 13 on events, 20 on messages, **11 attached to nothing** |
| `images` | 492, **all 492 carrying crop coordinates** — 382 Course, 75 User, 28 Hero, 7 News (159 soft-deleted) |

### The message thread stays, and it is staff-only in practice

251 messages over four years, and the rate is steady rather than trailing off —
51 in 2023, 98 in 2024, 64 in 2025, 38 so far in 2026. That settles the question
this scope opened with: it is **not** a bookmark-sized feature and does not get
the bookmark treatment. It is built properly.

**Every author holds Admin or Expert. Zero messages come from a student-only
account** — so finding 4 above, where any student may post to any event, has
apparently never been used. It is still open.

The distribution is lopsided in a way worth knowing before designing the screen:
one user (2, Admin + Expert + Student) wrote 129 of the 251, and user 401 (Admin)
wrote 60. Seven experts wrote 39 between them. This is mostly an admin tool with
an expert-facing corner, not a per-course conversation.

### The file attachment feature is barely used

44 files, 33 attachments, 11 files attached to nothing at all — against 492
images. Whatever the media decision turns out to be, `files`/`fileables` does not
justify its own module; it is an attachment on a message or an event and can be
built as one.

### Media: port `forrerzimmermann.ch` — decided 2026-09-18

**Marcel's call, and it replaces the functionality rather than migrating it.**
The media subsystem in `github.com/marceli-to/forrerzimmermann.ch` is the target,
and it settles both question 15 here and question 5 in `04-content.md`: neither
`spatie/laravel-medialibrary` nor `marceli-to/image-cache`.

It fits because it is already the rework's stack and conventions — Laravel 13,
PHP 8.3, Actions / FormRequests / Resources, Vue 3 — so it is a port between two
codebases that agree, not an adaptation.

| Piece | |
|---|---:|
| `Actions/Media/` — Upload, Attach, Crop, Delete, Normalize, Reorder, SetOg, SetTeaser, Update | 298 LOC |
| Vue — `MediaGrid`, `MediaCrop`, `MediaUploader`, `MediaCard`, `MediaEdit`, `views/media/Index` | 722 LOC |
| `ImageController` + `Support/ImageSupport` + `<x-media.image>` | ~250 LOC |
| `NormalizeImages`, `ClearImageCache` commands | 132 LOC |

What it does that legacy does not: **`league/glide` on Imagick** behind
`/img/{path}?w=&h=&fit=crop&crop=w,h,x,y&fm=&q=`, a `<picture>` element with
AVIF → WebP → JPEG negotiated against what Imagick actually supports, an 8-step
`srcset`, explicit `width`/`height` to stop layout shift, and an art-directed
**mobile variant** (a second `media` row with `variant = mobile` carrying its own
crop). One `media` table with a `nullableMorphs('mediable')` and a **`crop` JSON
column**, replacing legacy's `images` + `files` + `fileables`.

#### The coords port is a straight copy — the crop format already matches

This was the open worry, and the data says it is not one. Legacy stores
`coords_w/h/x/y` as `double(16,12)`, which reads like normalised fractions but is
not: **the values are pixels.** Verified twice — the range runs to 4608×3124,
and `MarceliTo\ImageCache\Templates\Crop` parses them as
`width,height,x,y` and hands them straight to `Intervention::crop()`.

Glide's `crop` parameter takes `w,h,x,y` in pixels, in that same order. So:

```
coords_w, coords_h, coords_x, coords_y   →   crop = {"w":…, "h":…, "x":…, "y":…}
```

No unit conversion, no reinterpretation.

**Correcting an earlier note in this doc:** "all 492 images carry crop
coordinates" was true but misleading. Only **407 are actually cropped**; the
other **85 hold `0,0,0,0`**, which is legacy's way of saying *no crop* — the
legacy template special-cases the string `'0,0,0,0'` precisely to avoid producing
a 1×1 pixel image. Those 85 port to `crop = NULL`, and porting them as zeros
would produce 85 broken images. No row is half-zero, so the test is clean.

#### The trap: normalising the source invalidates the crop

`NormalizeAction` downsizes any source whose longest edge exceeds **3200 px**,
in place, on upload. The legacy crops are in **source pixels**, and **28 of the
407 cropped images come from sources wider than 3200** (measured as
`max(w+x, h+y) > 3200`; 52 exceed 2400).

Run the normaliser over the legacy files during the port and those 28 crops
silently point at the wrong region — the image still renders, just cropped
somewhere else, which is exactly the kind of failure nobody notices. The action
already **returns the scale factor** it applied, so the fix is to multiply the
four coordinates by it. The port must do that, or skip normalisation for rows
that carry a crop.

#### What the port needs that legacy does not store

`media` wants `width`, `height`, `mime_type` and `alt`; legacy's `images` table
has none of them — only `orientation` as `l`/`p`, which the new model derives
from the dimensions anyway. **Width and height have to be read from the files.**

Measured against the production snapshot on 2026-09-18, and the news is good:

| | |
|---|---:|
| `images` rows | 492 |
| — live, **file present** | **333** (100 % of live rows) |
| — soft-deleted, file gone | 159 |
| Live rows carrying a crop | 289 |
| Live rows with `0,0,0,0` | 44 |
| **Crops falling outside the image** | **0** |
| Sources over 3200 px — the normalise trap | **29** |
| `orientation` column disagreeing with the file | **49** |
| Files in `uploads/` referenced by no row | 5 of 371 |

Every live image has its file and every crop is geometrically valid, so the port
can run. Deleting an image removes its file, which is why the 159 absent ones are
exactly the soft-deleted rows — correct behaviour, not data loss.

Two things the measurement changed. The normalise trap is **29** rows, not the 28
estimated from `max(w+x, h+y)`. And the legacy **`orientation` column is wrong on
49 of 333 rows** — it is stale, so the new model deriving orientation from
width and height is a fix rather than a like-for-like port. Do not carry the
column across.

`alt` has no legacy source at all. It stays null and becomes an editorial task —
worth saying out loud, because an accessible site needs it and nobody has it.

#### One thing to add when porting

`/img/{path}` is **unsigned** in `forrerzimmermann.ch`, with `where('path','.*')`
and arbitrary `w`/`h` from the query string. On a small brochure site that is
fine. On a public site with VIAK's traffic it lets anyone generate unbounded
cache entries by varying the parameters. Glide ships `setSignKey` for this;
alternatively clamp `w`/`h` to the `WIDTHS` list the component already uses. Not
a flaw in what Marcel built — a different threat surface.

Note also that images attach to `Course`, `User`, `Hero` and `News` — two belong
to chunk 04 and one to this chunk, which is why this decision could not be taken
twice.

### Documents: nothing is lost — 2023 is broken instead

**Resolved 2026-09-18** against a production `storage/app/public` snapshot pulled
that day (paired with the 2026-09-11 dump; provenance in the snapshot's
`PROVENANCE.md`). The earlier reconciliation against an incomplete local copy is
superseded.

**All 1,162 documents exist. Zero are genuinely missing.** So question 4 is no
longer about feasibility — every invoice and every participation confirmation a
customer holds is still on disk, and carrying them across is a policy choice, not
a rescue operation.

What the 271 "missing" actually were is a **malformed `uri`**:

```
stored     /storage/filesf962c8c4-aa6c-4d20-9afd-3641823834fc/viak-teilnahme…pdf
on disk    /storage/files/f962c8c4-aa6c-4d20-9afd-3641823834fc/viak-teilnahme…pdf
                         ^ the separator
```

Put the slash back and **all 271 resolve**. `EventParticipationConfirmation`
builds the path as `"/app/public/files/{$userUuid}"`, which is correct today, so
this is an older defect whose rows were never repaired.

It is confined exactly: **271 rows, every one a 2023 `PARTICIPATION_CONFIRMATION`,
affecting 95 students.** 2024, 2025 and 2026 are clean, and all 568 `INVOICE`
rows are clean.

#### The same cohort is also duplicated

1,162 rows resolve to **1,005 distinct files** — 17 paths are shared by more than
one row, for **157 surplus rows**, again all 2023 participation confirmations.
Each group is one booking and one user, so it is the same confirmation written
repeatedly: 37 rows for one, then 19, 19, 18, 12. The 272 rows of 2023 sit on
**115 actual files**.

The likely cause is the legacy `Job` mail queue re-processing `EventClosedStudent`,
whose constructor generates the PDF and inserts the row as a side effect — the
anti-pattern already recorded further down. Each pass wrote a new row and
overwrote the one file.

**So a student who took a course in 2023 opens *Meine Dokumente* and sees up to 37
identical entries, every one of which 404s.** That is live today.

#### What this means for the port

Three rules, all cheap:

1. **Normalise the `uri`** on the way in — insert the separator — rather than
   porting a path that does not resolve.
2. **Deduplicate on the file**, not on the row. 1,005 documents, not 1,162.
3. **Do not trust `created_at` as the document date.** The duplicates share a
   file but not a timestamp.

Worth fixing in the legacy tree too, since it is two `UPDATE`s and it is customer
facing — see `Todo.md`.

#### Orphans, for completeness

- **9** PDFs inside user directories with no row at all.
- **294** loose `viak-teilnehmerliste-*.pdf` participant lists directly in
  `files/`, up from 239 in the older copy — they accumulate and nothing deletes
  them. Orphans by construction; the controller that writes them writes no row.

### Media: port `forrerzimmermann.ch` — decided 2026-09-18

**Marcel's call, and it replaces the functionality rather than migrating it.**
The media subsystem in `github.com/marceli-to/forrerzimmermann.ch` is the target,
and it settles both question 15 here and question 5 in `04-content.md`: neither
`spatie/laravel-medialibrary` nor `marceli-to/image-cache`.

It fits because it is already the rework's stack and conventions — Laravel 13,
PHP 8.3, Actions / FormRequests / Resources, Vue 3 — so it is a port between two
codebases that agree, not an adaptation.

| Piece | |
|---|---:|
| `Actions/Media/` — Upload, Attach, Crop, Delete, Normalize, Reorder, SetOg, SetTeaser, Update | 298 LOC |
| Vue — `MediaGrid`, `MediaCrop`, `MediaUploader`, `MediaCard`, `MediaEdit`, `views/media/Index` | 722 LOC |
| `ImageController` + `Support/ImageSupport` + `<x-media.image>` | ~250 LOC |
| `NormalizeImages`, `ClearImageCache` commands | 132 LOC |

What it does that legacy does not: **`league/glide` on Imagick** behind
`/img/{path}?w=&h=&fit=crop&crop=w,h,x,y&fm=&q=`, a `<picture>` element with
AVIF → WebP → JPEG negotiated against what Imagick actually supports, an 8-step
`srcset`, explicit `width`/`height` to stop layout shift, and an art-directed
**mobile variant** (a second `media` row with `variant = mobile` carrying its own
crop). One `media` table with a `nullableMorphs('mediable')` and a **`crop` JSON
column**, replacing legacy's `images` + `files` + `fileables`.

#### The coords port is a straight copy — the crop format already matches

This was the open worry, and the data says it is not one. Legacy stores
`coords_w/h/x/y` as `double(16,12)`, which reads like normalised fractions but is
not: **the values are pixels.** Verified twice — the range runs to 4608×3124,
and `MarceliTo\ImageCache\Templates\Crop` parses them as
`width,height,x,y` and hands them straight to `Intervention::crop()`.

Glide's `crop` parameter takes `w,h,x,y` in pixels, in that same order. So:

```
coords_w, coords_h, coords_x, coords_y   →   crop = {"w":…, "h":…, "x":…, "y":…}
```

No unit conversion, no reinterpretation.

**Correcting an earlier note in this doc:** "all 492 images carry crop
coordinates" was true but misleading. Only **407 are actually cropped**; the
other **85 hold `0,0,0,0`**, which is legacy's way of saying *no crop* — the
legacy template special-cases the string `'0,0,0,0'` precisely to avoid producing
a 1×1 pixel image. Those 85 port to `crop = NULL`, and porting them as zeros
would produce 85 broken images. No row is half-zero, so the test is clean.

#### The trap: normalising the source invalidates the crop

`NormalizeAction` downsizes any source whose longest edge exceeds **3200 px**,
in place, on upload. The legacy crops are in **source pixels**, and **28 of the
407 cropped images come from sources wider than 3200** (measured as
`max(w+x, h+y) > 3200`; 52 exceed 2400).

Run the normaliser over the legacy files during the port and those 28 crops
silently point at the wrong region — the image still renders, just cropped
somewhere else, which is exactly the kind of failure nobody notices. The action
already **returns the scale factor** it applied, so the fix is to multiply the
four coordinates by it. The port must do that, or skip normalisation for rows
that carry a crop.

#### What the port needs that legacy does not store

`media` wants `width`, `height`, `mime_type` and `alt`; legacy's `images` table
has none of them — only `orientation` as `l`/`p`, which the new model derives
from the dimensions anyway. **Width and height have to be read from the files.**

Measured against the production snapshot on 2026-09-18, and the news is good:

| | |
|---|---:|
| `images` rows | 492 |
| — live, **file present** | **333** (100 % of live rows) |
| — soft-deleted, file gone | 159 |
| Live rows carrying a crop | 289 |
| Live rows with `0,0,0,0` | 44 |
| **Crops falling outside the image** | **0** |
| Sources over 3200 px — the normalise trap | **29** |
| `orientation` column disagreeing with the file | **49** |
| Files in `uploads/` referenced by no row | 5 of 371 |

Every live image has its file and every crop is geometrically valid, so the port
can run. Deleting an image removes its file, which is why the 159 absent ones are
exactly the soft-deleted rows — correct behaviour, not data loss.

Two things the measurement changed. The normalise trap is **29** rows, not the 28
estimated from `max(w+x, h+y)`. And the legacy **`orientation` column is wrong on
49 of 333 rows** — it is stale, so the new model deriving orientation from
width and height is a fix rather than a like-for-like port. Do not carry the
column across.

`alt` has no legacy source at all. It stays null and becomes an editorial task —
worth saying out loud, because an accessible site needs it and nobody has it.

#### One thing to add when porting

`/img/{path}` is **unsigned** in `forrerzimmermann.ch`, with `where('path','.*')`
and arbitrary `w`/`h` from the query string. On a small brochure site that is
fine. On a public site with VIAK's traffic it lets anyone generate unbounded
cache entries by varying the parameters. Glide ships `setSignKey` for this;
alternatively clamp `w`/`h` to the `WIDTHS` list the component already uses. Not
a flaw in what Marcel built — a different threat surface.

Note also that images attach to `Course`, `User`, `Hero` and `News` — two belong
to chunk 04 and one to this chunk, which is why this decision could not be taken
twice.

### Documents: the two copies do not line up, and that needs production to settle

Of the 1,162 document rows, **667 have a file in the local storage copy and 495
do not**. In the other direction, 120 PDFs sit inside user directories with no
row, plus the 239 loose participant lists.

The missing ones do **not** fall on a clean date cutoff:

| Year | Rows | Missing | |
|---|---:|---:|---:|
| 2023 | 414 | 271 | 65 % |
| 2024 | 379 | 0 | 0 % |
| 2025 | 242 | 97 | 40 % |
| 2026 | 127 | 127 | 100 % |

2026 at 100 % says the local storage copy is older than the 2026-09-11 database
dump. But 2024 at 0 % sitting between 2023 at 65 % and 2025 at 40 % is not
explained by a stale copy, and **this cannot be resolved from here** — a row with
no file locally may be fine in production.

So this is not a finding, it is a **cutover measurement**: the reconciliation has
to be redone against production, with a storage snapshot taken **at the same
moment as the database dump**. `Todo.md` already requires a fresh dump for the
rehearsal; it now also requires a storage snapshot to go with it. Until then the
honest answer to "are the 1,162 historical PDFs worth carrying" (question 4) is
that we do not yet know how many of them still exist.

One part is certain regardless: the **239 loose participant lists can never have
had a row**, because the controller that writes them writes no row. They are
orphans by construction, not by drift.

## Open questions

1. **The `/expert/finish` vulnerability** — needs fixing on the live site now,
   independently of this chunk. Ours to raise, not a client decision.
2. **Should an admin-initiated cancellation charge the penalty?** Chunk 06 needs
   a fourth `CancellationReason` either way. For Marcel.
3. ~~Medialibrary or `marceli-to/image-cache` for the media pipeline?~~ —
   **answered 2026-09-18: neither. Port the media subsystem from
   `forrerzimmermann.ch`.** See above; it also answers `04-content.md`
   question 6 and Open-Questions 5.
4. **Are the historical PDFs worth carrying?** Inherited from `01-schema.md`
   question 1, and now a clean question: **all of them exist** — 1,005 distinct
   files behind 1,162 rows. Since they are customer-held documents referenced
   from invoices, the default is yes. Worth asking the client only whether the
   **2023 participation confirmations** should be repaired and carried or quietly
   dropped, given 95 students have been looking at broken links for two years.
5. **Is a user with financial history ever deleted**, or only deactivated?
6. ~~Does the message thread stay?~~ — **answered 2026-09-18 by the data: yes,
   built properly.** 251 messages across 122 events, steady over four years. See
   above.
