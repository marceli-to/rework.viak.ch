# 08 — Accounts, portals and documents (not yet built)

Who a user is, what they can see of their own history, and the generated
documents that history produces.

> **Numbering.** 07 is left for the editor chunk, already referenced from
> `Support/RichText.php` and `PortCourses.php` as `[[07-editor]]`. This chunk
> also absorbs `[[11-auth]]`, referenced from `resources/js/app/router/index.js`
> — accounts and auth are one surface, not two, and that comment should be
> repointed here when the chunk is built.

## Status

Not built, not previously scoped. Found on 2026-09-18 by the same method that
found chunk 06: mapping the legacy surface onto the rework and looking for what
has nowhere to land.

**Read the security section first.** Scoping this chunk turned up a live
account-takeover path on the production site. It is not a rework question and it
should not wait for this chunk.

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
documents, 579 user directories, 129 MB, protected only by the uuid in the path.

In the rework these are served by an authenticated route with a policy, and the
files live outside the public root. That is the storage decision `01-schema.md`
said had to come first.

### 4. Any student can read, and write, any event's messages

`GET /api/event/messages/{event:uuid}` and `POST /api/event/message` are gated by
`role:admin,expert,student` and nothing else — no policy call, and
`MessageStoreRequest::authorize()` returns `true`. So any authenticated student
can read the message thread of an event they have never booked, and post a
message to one, **which mails every participant of that event**.

### 5. Any expert can download any participant list

`GET /pdf/teilnehmer-liste/{event:uuid}` is gated by `role:admin,expert` with no
check that the expert teaches that event. The PDF carries participant names and
contact details. Note that the neighbouring API route *does* check —
`EventController::findExpertEvent` calls `authorize('containsEvent', $event)` —
so this is an omission on one route rather than a missing policy.

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

## What is *not* in this chunk

- **Bookmarks** — chunk 06, deliberately small (17 rows).
- **The invoice worklist** — chunk 03 deferred it, and it is `backoffice`.
- **`news`, `hero`, `team_member`, the grid** — chunk 04.
- **The Mailchimp sync.** `NewsletterSubscriber::update()` is called from
  registration and from every profile update, so this chunk touches it, but
  whether it survives is the open question `00-foundation.md` already carries.

## Measurements still owed

MySQL was not running when this was scoped (DBngin, 8.0.40 on :3307), so the
following are from the source and from `01-schema.md` rather than from a query.
Everything above stands without them; these sharpen the estimate.

| | Known | Still to measure |
|---|---|---|
| `user_documents` | 1,162 (568 invoice, 594 participation) | how many belong to deleted users |
| Files on disk | 579 dirs, 129 MB | orphans in either direction |
| `messages` / `message_user` | — | rows, and how many events ever used them |
| `files` / `images` | — | rows, and which `fileable_type`s occur |
| Users | 578, 17 with an expert bio | role distribution, soft-deleted count |
| Addresses | 126 bookings (18 %) with a non-default invoice address | distinct addresses per user |

The one that could change the shape of the chunk is **messages**: if the thread
feature was used a handful of times in four years, it gets the bookmark
treatment — built small, no module — rather than a 336-LOC port.

## Open questions

1. **The `/expert/finish` vulnerability** — needs fixing on the live site now,
   independently of this chunk. Ours to raise, not a client decision.
2. **Should an admin-initiated cancellation charge the penalty?** Chunk 06 needs
   a fourth `CancellationReason` either way. For Marcel.
3. **Medialibrary or `marceli-to/image-cache`** for the media pipeline — one
   decision shared with `04-content.md` question 6.
4. **Are the 1,162 historical PDFs worth carrying?** Inherited from
   `01-schema.md` question 1, and now answerable: they are customer-held
   documents referenced from invoices, so the default is yes, but 129 MB and a
   storage move is the cost.
5. **Is a user with financial history ever deleted**, or only deactivated?
6. **Does the message thread stay?** Depends on the row count above.
