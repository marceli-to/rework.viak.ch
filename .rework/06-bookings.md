# 06 — Bookings, basket and cancellation (not yet built)

How a seat is sold, discounted, cancelled and charged for.

## Status

**Built 2026-09-18.** 184 tests green, Pint clean, Vite builds. Scoped
2026-09-17, and every client question it raised was answered the same day.

The chunk was found by mapping the legacy facade layer onto the rework in
`00-foundation.md`: five of the eight facades — `Booking` (243 LOC), `Discount`
(165), `Bookmark` (82), `ParticipantsChange` (59), `Message` (32) — had no chunk
to land in. 581 lines of behaviour with nowhere to go is not a refactoring
question, it is a missing chunk. `Models/Booking` existed, chunk 01's port filled
it and chunk 03's `RaiseInvoiceForBooking` read it; nothing in the rework had
ever *created* one.

**The short version:** the money rules were the work, not the CRUD. Three of them
— the cancellation penalty, how a discount is applied across a basket, and who
cancelled — were implicit in legacy, hold real money, and one broke outright on
the Carbon version the rework runs.

### What was built

| | |
|---|---|
| Schema | `checkouts`, `bookmarks`, `bookings.checkout_id`, `bookings.cancellation_reason`, `discount_codes.usage_limit`, `events.participant_threshold` |
| Enums | `BookingCancellationReason`, `ParticipantThreshold`, `CancellationReason::Waived` |
| Support | `CancellationPenalty`, `Basket` + `BasketItem`, `BookingNumber`, `SequentialNumber` |
| Actions | `PriceBasket`, `CompleteCheckout`, `CancelBooking`, `CancelBookingsForEvent`, `SetRental`, `CreateBookingForUser`, `RaiseCancellationPenalty`, `CancelInvoice` |
| Events | `BookingMade`, `BookingCancelled`, `ParticipantThresholdCrossed` |
| HTTP | `BasketController`, `BookingController`, `BookmarkController`, four FormRequests, three Resources, `BookingPolicy` |
| Port | `usage_limit` reconstructed — no dates becomes 1, dates become unlimited |

`InvoiceNumber` was refactored onto the shared `SequentialNumber` rather than
copied, because legacy had that bug three times over and fixing it once per
caller is how it comes back.

### Deferred, and why

- **The Alpine checkout UI.** Parity frontend work, and it belongs to the phase
  the 2026-09-18 decision put after the backend (`00-foundation.md`). The
  server-driven shape is settled: a POST per step with the state in the session,
  which falls out of the server being the pricing authority.
- **Notification copy.** `ParticipantThresholdCrossed` carries the direction and
  fires correctly; the mail templates land with the notifications chunk. The
  *detection* — the part legacy got wrong — is built and tested.
- **The admin booking-on-behalf route.** The Action exists and is tested;
  the dashboard surface it belongs on is chunk 08.

## What legacy does

`Booking::create($basket)` walks the session basket and, per item, creates a
booking with the fee and discount **frozen onto the row**, clears the bookmark,
marks single-use codes used, fires `BookingCompleted`, recounts participants and
mails any messages already posted to that event.

Cancellation has **two separate paths that share no code**:

| Who cancels | Path | Penalty? |
|---|---|---|
| The student | `Booking::cancel()` | Yes, if inside the window |
| VIAK, by calling off the course | `EventCancelledHandler` | No — it flags the bookings directly and never calls `Booking::cancel()` |

That distinction is worth more than it looks, and it exists only because the two
paths happen not to share a function. See below.

## What the data says

Measured against `viak_legacy` at the 2026-09-11 dump.

| | |
|---|---:|
| Bookings | 710 |
| Cancelled | 183 (25.8 %) |
| — of those, on a course **VIAK** cancelled | 143 |
| — genuine student cancellations | 40 |
| With a laptop rental (`has_rental`) | 40 |
| With a discount code | 79 (11 %) |
| With a non-default invoice address | 126 (18 %) |
| Free of charge | 3 |
| Bookmarks, lifetime | **17** |
| Discount codes | 107 (5 soft-deleted) |

Cancellation is not an edge case at a quarter of all bookings — but **78 % of it
is VIAK calling off a course**, not a customer walking away. The 40 real
cancellations fall out like this:

| Window | Student cancellations | Of those, chargeable |
|---|---:|---:|
| 20+ days before — free | 25 | — |
| 11–19 days — 50 % due | 5 | 5 |
| 0–10 days — 100 % due | 9 | 8 |
| After the course ran | 1 | 1 |

*Chargeable* applies legacy's own second test, `PenaltyHelper::applies()`: the
fee must exceed the discount. One of the nine late cancellations was fully
discounted and so exempt. That leaves **14** cancellations the rule says should
have produced a penalty invoice.

## Decisions this forces

### The penalty rule does not survive the Carbon upgrade

`PenaltyHelper` computes

```php
$days = Carbon::parse($eventDate)->diffInDays(Carbon::now());
if ($days == 0 || $days < config('invoice.days_penalty_full')) // 11 → 100 %
else if ($days < config('invoice.days_penalty_half'))          // 20 → 50 %
```

Legacy locks **Carbon 2.73**, where `diffInDays()` returns an **absolute** value,
so `$days` is days-until-the-event and the rule reads correctly. The rework is on
Laravel 13 and **Carbon 3.13**, where `diffInDays()` is **signed**. Verified in
both trees with the same two dates:

```
Carbon 2 (legacy):  $event->diffInDays($now)  =  33
Carbon 3 (rework):  $event->diffInDays($now)  = -33
```

Ported verbatim, `-33 < 11` is true, and **every cancellation of a future event
becomes a 100 % penalty.** The rework computes the number of days it actually
means — `$now->startOfDay()->diffInDays($event->date)` — and pins both the 11-day
and 20-day boundaries with a test, since the rule is worth real money and the
failure is silent.

### Who cancelled decides whether there is a penalty

If the penalty rule ran over the *whole* cancelled set, **115 of the 143 bookings
on VIAK-cancelled courses fall inside the 0–10 day window** — VIAK decides late
whether a course runs — and every one of those students would be invoiced the
full fee for a course VIAK itself called off.

Legacy avoids this by accident of structure: `EventCancelledHandler` flags the
bookings `isCancelled` + `isCancelledByAdministrator` and never goes near
`Booking::cancel()`. Nothing states the rule; it is an emergent property of two
code paths.

In the rework, **cancellation carries its reason as data** — the same shape as
chunk 03's `CancellationReason` enum — and the penalty Action reads it. One path,
an explicit rule, and the expensive case is a branch rather than a coincidence.

### The penalty is raised automatically, and waiving it is a human act — answered 2026-09-17

**Marcel, 2026-09-17: the rule keeps firing automatically. If VIAK then decides
to cancel the invoice, that is their decision.** Which is exactly what legacy
does, and the data confirms it fired every single time.

Fourteen student cancellations qualified under the rule (inside 20 days, fee
greater than discount, event not free). All fourteen were handled correctly:

| | Bookings | What happened |
|---|---:|---|
| Penalty invoice raised at cancellation | 10 | At the right rate every time — 50 % at 17, 17, 13, 12 and 12 days; 100 % at 8, 7, 5, 1 and 0 days |
| Already paid in full, left alone | 4 | All four were in the 100 % window (7, 6, 5 and −1 days), so the full fee already on the paid invoice *was* the penalty |
| — of the ten raised, later cancelled by a human | 2 | 000148 three days after raising, 000286 five days after |

So there is no gap to close. The rework raises the penalty invoice on
cancellation, at 100 % inside 11 days and 50 % inside 20, and an admin may
cancel that invoice afterwards like any other.

> **Correction, same day.** This section first read that the rule was "enforced
> by hand, roughly half the time — six of fourteen". That was wrong, and the
> cause is worth recording: penalties were detected by
> `cancel_reason LIKE 'Replaced by%'`, which only finds a penalty that *replaced*
> an existing invoice. Ten of the fourteen had no invoice yet — the course had
> not been invoiced when the student cancelled — so their penalty was raised
> fresh and carried no such reason. Detect a penalty invoice by its **amount and
> its date**, not by the cancellation reason on some other row.

**One thing the rework has to add: record why an invoice was waived.** Legacy
cancels a penalty by setting `status = CANCELLED` and leaving `cancel_reason`
**NULL** — the only two null-reason cancellations in 569 invoices are precisely
these two waivers. A waived penalty is therefore indistinguishable from a
cancellation nobody explained.

Chunk 03's `CancellationReason` has `Replaced` and `BookingCancelled`. This chunk
adds a third — the deliberate, admin-initiated waiver — so the invoice says who
decided and why. The port maps the two legacy NULLs onto it; every other null
stays null, because `fromLegacyText()` reports rather than guesses.

That also makes an admin requirement concrete: cancelling an invoice is a
first-class action with a reason attached, which belongs with the invoice
worklist chunk 03 deferred.

### A discount is priced once, server-side, and cannot exceed the fee

Three separate problems, all in the same seam.

**The basket and the booking disagree.** `BasketController::getTotals()` applies
the code to the **basket total**; `Booking::create()` applies it again per event,
against each `courseFee`. For a percentage code the two agree, which is why this
went unnoticed for three years. For a fixed-amount code they do not.

A real basket, user 123 on 2023-12-23, code `VIAK-2GDV-2HUE` — **fixed CHF 50**:

| | Interior Design mit SketchUp | Visualisieren mit SketchUp | Total |
|---|---:|---:|---:|
| Course fee | 949.00 | 499.00 | 1448.00 |
| What the basket screen showed | | | **−50.00** → pay 1398.00 |
| What `Booking::create()` stored | −50.00 | −50.00 | **−100.00** → pay 1348.00 |

The customer agreed to 1398 and was billed 1348. Same shape for user 67 with a
CHF 30 code across two courses — shown 30, given 60. With a **10 %** code (user
216) both readings give 150, because 10 % of each fee sums to 10 % of the total.
Three baskets, CHF 80 given away; the point is the undefined rule, not the money.

#### One code discounts the order — decided 2026-09-17

**Marcel, 2026-09-17: it should be the order.** The screen was right and the code
was wrong. A CHF 50 code takes CHF 50 off the checkout, once, however many
courses are in it.

That is the harder of the two readings, because chunk 03 raises an invoice **per
confirmed course, weeks apart**. Two things follow.

**1. A checkout has to become a record.** Legacy's basket lives in the session
and evaporates; each booking keeps its own copy of the code and amount, and
nothing knows they were one purchase. An order-level discount needs the thing it
is level with. So: **one row per completed checkout**, carrying the buyer, the
code, and the discount computed against the whole basket. Bookings and licence
lines point at it.

This does **not** reopen chunk 03's rejection of Order/OrderItem. That decision
was about what invoices hang off, and it stands — invoices are still raised by
the confirmation trigger, never from a checkout. This row is a record of what was
agreed at the till. Nothing is invoiced *from* it; it is read *by* whatever is
being invoiced.

**2. The discount is drawn down, not split.** The obvious move is a proportional
split frozen at checkout — 949/1448 × 50 = 32.77 and 499/1448 × 50 = 17.23 — and
it is the wrong one. It needs largest-remainder rounding to sum back to 50, and
if the second course is never confirmed its 17.23 is stranded on an invoice that
does not exist, so the customer gets 32.77 of the 50 they were promised.

Instead, **each invoice consumes what is left of the checkout's discount, capped
at that invoice's net**, and the remainder carries to the next invoice raised
from the same checkout:

| Raised | Net before | Discount available | Applied | Invoice | Left |
|---|---:|---:|---:|---:|---:|
| Interior Design confirmed | 949.00 | 50.00 | 50.00 | **899.00** | 0.00 |
| Visualisieren confirmed later | 499.00 | 0.00 | 0.00 | **499.00** | 0.00 |

Total paid 1398.00 — exactly what the basket promised. The properties that matter:

- The customer receives the full amount as soon as **anything** in the checkout
  is invoiced, so nothing is stranded on a course that never runs.
- **No raised invoice is ever edited.** Each one asks how much is left at the
  moment it is raised, which is chunk 03's doctrine that an invoice is a document
  rather than a mutable row.
- No split means no rounding rule to get wrong.
- A mixed basket behaves sensibly: a licence bills at purchase and a course at
  confirmation, so the licence invoice draws the discount first and the customer
  sees it immediately.

**Percentage codes need none of this.** A rate applies to each line and the lines
sum correctly by construction, which is exactly why the bug was invisible for
three years. Only fixed-amount codes draw down.

**Nothing clamps the discount to the fee.** Booking 000512 took a fixed CHF 648
code against a CHF 499 course and produced **invoice 000419 with a grand total of
−149.00, status OPEN** — a negative invoice sitting in the live books today. The
same booking also carries `has_rental = 1` with no rental invoice, so that CHF 80
laptop was never billed either. It is the only booking of the 710 where the
discount exceeds the fee, and the only unbilled rental on a course that ran.

The draw-down rule above makes this **unrepresentable** rather than merely
guarded against: an invoice consumes at most its own net, so a CHF 648 code
against a CHF 499 course takes 499, the invoice lands at 0.00, and the unusable
149 stays behind in the checkout instead of being printed on a document. A
customer cannot be owed money by a course they bought.

**An expired code fails silently.** `Discount::apply()` returns `FALSE` when
validation fails, and `Booking::create()` writes that straight into
`discount_amount`, where it becomes 0. The customer sees a discounted basket, is
charged full price, and is told nothing.

The rework prices the basket **on the server at checkout**, from the event's own
fee and the code as it is at that instant, clamps the discount at the fee, and
refuses the checkout with a message if the price moved between basket and
confirm. The client never supplies a price — `00-foundation.md` already says so
for `Stores/`; this is where it gets enforced.

### `discount_amount` is `decimal(8,0)` — the port copies, it does not recompute

Whole francs. A 10 % code on a CHF 499 course stores **50** where the code means
49.90. Nine bookings are affected, each by 10 Rappen:

```
000179  499.00 × 10 %  = 49.90  stored as 50
000223  549.00 × 10 %  = 54.90  stored as 55   (and 000224, 000232, 000239, 000241)
000240  949.00 × 10 %  = 94.90  stored as 95
000422  499.00 × 10 %  = 49.90  stored as 50   (and 000423)
```

`discount_codes.amount` is `decimal(8,0)` too, so a percentage can only ever be a
whole number — fine — but the *computed* discount must not be. The rework stores
`decimal(10,2)`, and **the port copies the legacy value rather than recomputing
it**, exactly as chunk 03 does with `vat`. Any reconciliation that recomputes
will flag these nine; that is expected, not a port bug.

### Discount codes need a type and a limit, not two flags and a coincidence

`discount_codes` carries `fix tinyint` and `percent tinyint` as separate columns
— nothing prevents both or neither, though in 107 rows neither has happened.
Chunk 03 already built the `DiscountType` enum and a test that the two cannot be
true at once; this chunk uses it.

The stranger rule is single use. `DiscountCode::isSingle()` returns true **if and
only if the code has no validity window**:

```php
public function isSingle() { return $this->valid_from && $this->valid_to ? FALSE : TRUE; }
```

So "how many times may this be used" and "when is this valid" are the same
column, and neither can be set independently. The 107 codes split exactly along
that line — 35 with no dates (single-use, 27 already flagged `isUsed`), 72 with
both dates (unlimited), **0 with only one of the two**. Usage: 48 never used, 49
used once, 10 used between two and six times.

The rework separates them: a validity window that may be open at either end, and
a usage limit that is a number (1, *n*, or unlimited). Reconstructing the legacy
rows is mechanical — no dates becomes limit 1, dates become unlimited.

Also worth fixing: `isValid()` only checks the window when **both** dates are
set, so a code with one date would be valid forever. No row has one date today,
which is luck, not design.

### Booking numbers get the `InvoiceNumber` treatment

`Booking::getNumber()` is the same racy full-table load chunk 03 already
documented and fixed for invoices:

```php
$bookings = BookingModel::withTrashed()->get();
$number = (int) $bookings->last()->number + 1;
```

710 rows loaded to read one of them, trusting primary-key order to mean "highest
number", with a window in which two checkouts in the same second mint the same
number. `Support/InvoiceNumber` solves this with `max()` + `lockForUpdate()` and
a guard that refuses to mint outside a transaction. Booking numbers get the same
treatment, and the two should share the mechanism rather than the bug.

### The rental is a line, not a second invoice

Chunk 03 settled the shape: one invoice with lines, and `bookings.rental_fee`
frozen the way `course_fee` already is. This chunk owns the other half — adding
and removing a rental **before** the invoice is raised, which is the only window
in which it is free to change. Legacy's `addRental` / `cancelRental` mutate a
`has_rental` tinyint and then reach into the invoice layer to delete a document;
once invoices are raised at confirmation, that reach disappears.

Of the 40 rental bookings, 29 have a live rental invoice and 10 have none: six
are cancelled bookings on cancelled courses and two are on a course that has not
been confirmed yet — all correct — one (000496) was cancelled 22 days out, which
is the free window, and one (000512, above) is the genuine miss.

### Bookmarks stay — decided 2026-09-17

**Marcel, 2026-09-17: keep them.** So the 17 rows port and the feature is built.

That is 17 rows across the life of the site, which sets the budget rather than
the question: no facade, no Action layer, no place in the SPA's primary
navigation. Two model methods on `User` over the existing pivot, two routes, and
the star on a course card. `Booking::create()` already clears the bookmark when
the course is booked — that behaviour carries across, because a saved course you
have since booked is noise.

### Participant thresholds are notifications, so they are a listener

`ParticipantsChange::handle()` is pure notification — min reached, max reached,
dropped below min — which makes it the case the event/listener rule in
`00-foundation.md` was written for. It also fires only on `==`, so two bookings
in one cycle step over `max` and the notice is lost; see **Notify on crossing,
not on equality** there.

## The basket is polymorphic from day one — decided 2026-09-18

A consequence of the phasing decision in `00-foundation.md`, recorded here
because this chunk is where it gets built.

The public checkout is 797 LOC in legacy and the most expensive component on the
site. `05-licences.md` already establishes that a basket holds **courses and
licences**, billed on different triggers and producing two invoices on different
days. So even though licence products do not exist yet, the basket line is
polymorphic when it is first written — one extra column now against rewriting the
checkout when chunk 05 lands.

The same applies to the checkout record this chunk introduces for the order-level
discount: bookings *and* licence lines point at it, so it is not a bookings-only
row even while bookings are the only thing pointing at it.

**The checkout is Blade + Alpine, server-driven** — a POST per step with the state
in the session, not legacy's four-view client wizard. That falls out of the rule
this chunk already sets: the server prices the basket and refuses a checkout whose
price moved. Reasoning in `00-foundation.md`.

## Open questions

1. ~~Is the cancellation penalty actually enforced?~~ — **answered 2026-09-17:
   yes, automatically.** It keeps firing on cancellation; if VIAK then cancels
   the invoice, that is their call. See above — and note that the rework owes a
   third `CancellationReason` so a waiver is recorded rather than left null.
2. ~~Is a fixed-amount code per basket or per booking?~~ — **answered 2026-09-17:
   the order.** A CHF 50 code takes CHF 50 off the checkout, once. It needs a
   checkout record for the discount to be level with, and a draw-down rather than
   a proportional split; see above. Percentage codes are unaffected.
3. ~~What happens when a student who has already paid cancels inside the 50 %
   window?~~ — **answered 2026-09-17: VIAK handles it by hand.** No credit-note
   flow is built. It has never occurred in 710 bookings — all four already-paid
   late cancellations sat in the 100 % window, where the full fee was owed anyway
   — so building for it would be building for nothing.

   One consequence worth stating rather than discovering: if the correction
   happens **entirely outside** the rework, the invoice here still reads PAID at
   the full amount and the rework disagrees with Run My Accounts about that row.
   The cheap avoidance is the affordance question 1 already owes — cancel an
   invoice with a reason, and raise a replacement — used on a paid invoice. That
   costs nothing extra to allow and keeps the two books saying the same thing.
4. **Invoice 000419 (booking 000512) is open at −149.00, and its CHF 80 rental
   was never billed.** Ours to raise with the client before the port carries it
   across verbatim — which, per chunk 03's doctrine, is what the port will
   otherwise do. The draw-down rule means the rework cannot *produce* another one,
   but it does not retro-fix this row.
5. ~~Are bookmarks worth porting?~~ — **answered 2026-09-17: yes, keep them.**
   17 rows, built small. See above.
6. ~~Does an admin cancel a booking on a student's behalf, and through which
   path?~~ — **answered 2026-09-18 by reading the legacy dashboard, in
   `08-accounts.md`: yes, through the student's own path.**
   `PUT /api/booking/cancel/{booking}` carries `role:admin,student`, calls the
   same `BookingFacade::cancel()`, and `BookingPolicy::cancel` allows
   `$user->id === $booking->user_id || isAdmin()`. So an admin cancellation is
   indistinguishable from a student's and **the penalty fires**.

   Two things follow. **The reason enum needs a fourth case** — cancelled by an
   admin on the student's behalf — distinct from a student cancelling and from
   VIAK calling off the course, because today the rework cannot tell them apart
   either. **Whether that case charges the penalty is Marcel's call**, not
   something to infer from legacy, where it fires only because the two paths
   happen to be one.

   Creating is where the asymmetry is: admins have a **separate** booking path
   (`Api/Dashboard/BookingController`) that resurrects cancelled and soft-deleted
   bookings — keeping the old number and a `course_fee` frozen years earlier, and
   leaving the penalty invoice from the cancellation untouched. The rework does
   not resurrect; see `08-accounts.md`.
