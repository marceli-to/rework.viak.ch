# 10 — Mail, and seeing the flows work (not yet built)

What the application tells people, when, and how to watch it happen before a
real student ever receives one.

## Status

**Not built. Scoped 2026-09-24.** No Mailable exists in the rework. The domain
events fire — `BookingMade`, `BookingCancelled`, `EventConfirmed`,
`ParticipantThresholdCrossed` — but the only listeners are
`RaiseInvoicesOnConfirmation` and `NotifyParticipantThreshold`, and neither
sends anything. `PostMessage` records its recipients and sends nothing
([[09-public-site]]). The only mail that leaves the app today is Fortify's:
the stock Laravel verification and password-reset notifications, in Laravel's
layout, not VIAK's.

So a student can register, book, have their course confirmed and invoiced, and
hear about none of it. **This is the crucial missing piece of every flow.**
Everything upstream of the mail — state, invoices, PDFs — is built and tested.

Nothing blocks it. Everything legacy attaches — invoices and the participation
confirmation — is already generated ([[03-invoices]]).
Two things to settle on the way in, both already written down:

- how the queue worker runs in production (`00-foundation.md`, *Queue and
  schedule*);
- legacy's `->from(env(...))` in all 24 mailables breaks under a cached config
  (`Todo.md`). The rework reads `config('mail.from')` and a new
  `config('mail.admin')` for what legacy calls `env('MAIL_TO')`.

## The rule that comes before any mail: nothing reaches a real inbox

**The ported database holds real student and expert addresses.** A prototype
that sends mail from it sends to VIAK's customers. Same principle as Run My
Accounts ([[03-invoices]]): mocked until cutover.

Legacy already did this, twice: `Mail::alwaysTo(env('MAIL_TO'))` in
`AppServiceProvider`, and `Tasks/Job` swapping the recipient for `MAIL_TO`
outside production. The rework keeps it as **one** guard —
`Mail::alwaysTo(config('mail.catch_all'))` in `AppServiceProvider` whenever
`! app()->isProduction()` — with a test that fails if it goes missing. Belt and
braces: locally the mailer is also pointed at a catcher (below), so even a
missing guard only reaches Herd.

## The flow table — the spec

Mapped from legacy on 2026-09-24: `app/Listeners/*Handler.php`,
`Tasks/ObserveEventState`, `Facades/ParticipantsChange`, `Facades/Message`,
`Api/EventMessageController`. Legacy has no `$listen` map — the handlers are
auto-discovered — so the listeners **are** the map. *Admin* means legacy's
`env('MAIL_TO')`.

This table is what "the flows work" means. Each row becomes a test and a step
in a scenario (below).

| Trigger | Legacy mailable | To | Attaches | Subject | Rework today |
|---|---|---|---|---|---|
| Student registers | `StudentRegistered` | student | — | Bestätigung Anmeldung | Fortify's stock verification mail only |
| Admin creates an expert | `ExpertCreated` | expert | — | Dein VIAK-Zugang | No admin expert screen yet ([[08-accounts]]) |
| Booking made | `BookingCompleted` | student | — | Buchungsbestätigung – *course* | `BookingMade` fires, nothing listens for mail |
| | `BookingCreatedInfoExpert` | each expert | — | Neue Anmeldung für *course* | ″ |
| | `BookingCreatedInfoAdmin` | admin | — | Neue Anmeldung für *course* | ″ |
| | `RentalAddedInfoAdmin` | admin, if rental | — | Buchung Mietcomputer für *course* | ″ |
| | `EventConfirmationStudent` | student, **if event already confirmed** | invoice (+ rental invoice) | Kursbestätigung – *course* | ″ — invoice is raised at checkout, not mailed |
| | `EventMessageStudent` × every earlier message | student, late booker | — (files linked in the body) | the message's subject | Not ported — kept as is, see *Oddities* |
| Student cancels, no penalty | `BookingCancelled` | student | — | Annullationsbestätigung – *course* | `BookingCancelled` fires, nothing mailed |
| | `BookingCancelledInfoAdmin` | admin | — | Abmeldung für *course* | ″ |
| Student cancels, with penalty | `BookingCancelledWithPenalty` | student | penalty invoice | Annullationsbestätigung – *course* | Penalty invoice raised, not mailed |
| | `BookingCancelledInfoAdmin` | admin | — | Abmeldung für *course* | ″ |
| Rental added later | `RentalAdded` | student | rental invoice | Buchung Mietcomputer für *course* | `SetRental` fires nothing |
| | `RentalAddedInfoAdmin` | admin | — | ″ | ″ |
| Rental removed | `RentalCancelledInfoAdmin` | admin | — | Stornierung Mietcomputer für *course* | ″ |
| Seats reach minimum | `ParticipantsMin` | admin | — | Min. Teilnehmerzahl erreicht – *course* | `ParticipantThresholdCrossed` fires, nothing mailed |
| Seats reach maximum | `ParticipantsMax` | admin | — | Max. Teilnehmerzahl erreicht – *course* | ″ |
| Seats drop below minimum | `ParticipantsBelowMin` | admin | — | Min. Teilnehmerzahl unterschritten – *course* | ″ |
| 10 days out, still planned | `EventCancelOrConfirmReminder` | admin | — | Reminder – *course* | No scheduled command yet |
| Event confirmed | `EventConfirmationStudent` | each student | invoice (+ rental invoice) | Kursbestätigung – *course* | `EventConfirmed` → invoices raised, not mailed |
| | `EventConfirmationExpert` | each expert | — | Bestätigung – *course* | ″ |
| Event cancelled | `EventCancelStudent` | each student | — | Kursabsage – *course* | `CancelBookingsForEvent` runs, nothing mailed |
| | `EventCancelExpert` | each expert | — | Kursabsage – *course* | ″ |
| Event closed | `EventClosedStudent` | each student who **participated** | participation confirmation | Teilnahmebestätigung – *course* | State exists, nothing mailed |
| Expert posts a message | `EventMessageStudent` | each booked student | — (files linked in the body, `storage/uploads/…`) | the message's subject | `PostMessage` records recipients, sends nothing |
| | `EventMessageExpert` | the author, if *selfcopy* | — (files linked) | ″ | ″ |
| Invoice paid | `InvoicePaidConfirmation` | student | — | Zahlungsbestätigung Rechnung *no.* | `SyncInvoiceStatus` sets `paid_at`, fires nothing |
| | `InvoicePaidNotification` | admin | — | ″ | ″ |
| Event deleted | nothing — **refused while active bookings exist** | — | — | — | The API deletes regardless — see *Oddities* |

24 mailables, 28 rows: four are sent from two triggers.

### Oddities worth deciding rather than copying

- **Deleting an event sends nothing — because legacy never lets it happen to
  anyone booked.** *Corrected 2026-09-24; the first draft said nobody was told.*
  The dashboard's event form
  (`resources/js/vue/backend/dashboard/views/course/event/Form.vue:212`) swaps
  the *Löschen* button for *"Diese Veranstaltung kann nicht gelöscht werden, da
  N Buchung(en) vorhanden sind"* whenever `data.bookings` is non-empty, and
  `Event::bookings()` is the **active** bookings (`notFlagged('isCancelled')`).
  Past events show no delete at all (`v-if="!data.is_past"`). So a delete never
  needs a mail: an event with people on it has to be cancelled, and cancelling
  is what tells them.

  **The gap is on the server, in both.** Legacy's `destroy()` detaches the
  experts and deletes, with no check — the rule lives only in the Vue template.
  The rework's `EventController::destroy` checks `EventPolicy::delete`, which
  is `isAdmin()` and nothing else. No rework screen calls it yet, but the route
  is live. **Tracked in `Todo.md`**, *With the admin dashboard's event screens*:
  refuse the delete on the server while the event has active bookings, and on
  past events.
- **A late booker receives every earlier message**, one mail each
  (`Facades/Message::past`, called from the checkout). A student booking into a
  course with eight notes gets eight mails at once. **Settled 2026-09-24
  (Marcel): keep it as legacy does** — one mail per message, no digest. The
  booking listener sends them after the booking confirmation, and records each
  student as a recipient the way `PostMessage` does.
- **Booking onto a confirmed event sends two mails** — the booking confirmation
  and the course confirmation with the invoice. Correct, since the invoice is
  raised at checkout in that case ([[RaiseInvoiceForBooking]]), but it is two
  mails where one would do.
- **Restoring a cancelled booking from the dashboard fires `BookingCompleted`**,
  so the student is sent a new booking confirmation. Probably right; note it
  when building `CreateBookingForUser`'s listener.
- **`EventClosedStudent` goes only to bookings flagged `hasParticipated`**. The
  rework split *past* from *booked* on the date ([[09-public-site]]); closing is
  where attendance matters, so the rule holds.
- Legacy's threshold and reminder mails match on **equality**, and lose the
  notification when a step skips the value. Already fixed on the rework's side
  (`NotifyParticipantThreshold`, and `Todo.md` for the reminder); the reminder
  command must use the same *crossed, and not yet reminded* shape.

## How to watch the flows

Five layers, each catching what the others cannot. **1 to 3 come with the first
mailable**; 4 and 5 as the table fills.

### 1. The flow table above

The spec. A flow "works" when its rows are true — every recipient, every
attachment. Without it, "I clicked and a mail arrived" says nothing about the
three that did not.

### 2. A mail catcher, locally

`.env` ships `MAIL_MAILER=log` with `MAIL_PORT=2525`, which is Herd's mail port.
Set `MAIL_MAILER=smtp` and every mail lands in Herd's mail inbox (Herd Pro →
*Mail*), rendered, with attachments. Without Herd Pro, `brew install mailpit`
and point `MAIL_PORT` at 1025; same result.

The queue is `database`, so a mail that is queued sits in `jobs` until a worker
runs. Keep `php artisan queue:work` running alongside `composer dev`, or a flow
will look broken when it is only waiting.

### 3. Scenario commands — a lifecycle, step by step

`php artisan scenario:play <name>`. Each scenario builds its own throwaway
course, event and students, the way `DevUsersSeeder` does ([[Test-Users]]), then
goes through the **real Actions** in order, printing what it did and pausing for
Enter between steps. Between steps: look at the inbox, open the portals, check
the dashboard.

It goes through the Actions and not the models, so it runs the paths the site
runs. It does not go through HTTP, so it does not replace clicking the
auth-gated screens in a browser ([[viak-tests-cannot-see-middleware]]).

The scenarios, one per branch of the table:

| Scenario | Steps |
|---|---|
| `confirm` | register → book (×min) → *min reached* → confirm → invoices mailed → close → confirmations mailed |
| `cancel` | register → book (×2) → cancel event → students and experts told |
| `late-booker` | confirm an event → expert posts two notes → new student books → booking + course confirmation + earlier notes |
| `student-cancels` | book → cancel early (no penalty) → book again → cancel late (penalty invoice attached) → *below minimum* |
| `rental` | book with rental → add rental later → remove it |
| `reminder` | planned event 11 days out → move the clock a day → reminder → run again → no second reminder |
| `paid` | confirmed booking → invoice marked paid (Run My Accounts stays mocked) → both paid mails |

`--no-pause` runs one straight through, `--keep` leaves its data for clicking
around afterwards; otherwise the scenario cleans up after itself. The clock is
moved with `Carbon::setTestNow()`, so the reminder does not wait ten days.

### 4. Telescope, local only

`laravel/telescope` as a dev dependency, registered only when
`app()->isLocal()`. Every request and command shows which events fired, which
listeners ran, what was queued, which mails went out and which failed. It
answers *"I confirmed the event — why is there no invoice mail?"* without
guessing. Not installed on production.

### 5. Tests, and a preview page

- **One Pest test per row of the table**, with `Mail::fake()`: the right
  mailable to the right people with the right attachment, and nothing to anyone
  else. A flow proved once by hand stays proved.
- **A test that the non-production guard is in place.**
- **`/dev/mails`**, local only: every mailable rendered with fixture data, one
  link each. For design parity with legacy's markdown mails
  (`../viak.ch/resources/views/mail/`) the way the PDFs were measured against
  prod ([[03-invoices]]) — side by side, not from the source.

## Build order

1. Guard and config: `alwaysTo` outside production, `mail.from`, `mail.admin`,
   the test.
2. Catcher and worker locally (layer 2). No code.
3. The mail layout, ported from legacy's markdown theme, and `/dev/mails`.
4. The mailables by trigger, the table's order: booking made, cancelled,
   confirmed, closed, messages, thresholds, reminder, paid, rental. Each lands
   with its tests and its scenario step.
5. Telescope, once there is enough happening that reading the inbox is not
   enough.

Registration and password reset come in step 4 too: they send Laravel's stock
mail today, and legacy's `StudentRegistered` is VIAK's own.
