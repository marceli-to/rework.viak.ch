# 06 — Bookings, basket and cancellation (not yet built)

How a seat is sold, discounted, cancelled and charged for.

## Status

Not built, and until 2026-09-17 not scoped. The chunk was found by mapping the
legacy facade layer onto the rework in `00-foundation.md`: five of the eight
facades — `Booking` (243 LOC), `Discount` (165), `Bookmark` (82),
`ParticipantsChange` (59), `Message` (32) — had no chunk to land in. 581 lines of
behaviour with nowhere to go is not a refactoring question, it is a missing
chunk.

`Models/Booking` exists, chunk 01's port fills it and chunk 03's
`RaiseInvoiceForBooking` reads it. Nothing in the rework has ever *created* one:
no route, no controller, no Action.

**The short version:** the money rules are the work, not the CRUD. Three of them
— the cancellation penalty, how a discount is applied across a basket, and who
cancelled — are implicit in legacy, hold real money, and one of them breaks
outright on the Carbon version the rework runs.

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

### The penalty is a rule on paper — confirm it is a rule in practice

Fourteen student cancellations qualified under legacy's own rule (inside 20 days,
fee greater than discount, event not free). **Six produced a penalty invoice:**

| Booking | Days before | Original | Penalty | Outcome |
|---|---:|---:|---:|---|
| 000200 | 12 | 499.00 | 249.50 | PAID |
| 000466 | 12 | 1295.00 | 647.50 | PAID |
| 000281 | 7 | 549.00 | 549.00 | PAID |
| 000349 | 0 | 549.00 | 549.00 | PAID |
| 000626 | 5 | 499.00 | 499.00 | OVERDUE |
| 000344 | 1 | 549.00 | 549.00 | raised, then **cancelled** |

The other eight were not charged, and the dates rule out "the feature came
later": the first penalty is 2024-02-29, and 000225 (2024-03-19), 000303
(2024-08-23), 000392 and 000396 (both 2024-11-08) and 000680 (2026-09-02) all
came after it and went uncharged. 000344 shows the penalty being raised and then
withdrawn by cancelling the replacement.

So the honest reading is that the penalty is **enforced by hand, roughly half the
time**, and the code is what makes the invoice when someone decides to. That is a
client question before it is a build question — see below.

### A discount is priced once, server-side, and cannot exceed the fee

Three separate problems, all in the same seam.

**The basket and the booking disagree.** `BasketController::getTotals()` applies
the code to the **basket total**; `Booking::create()` applies it again per event,
against each `courseFee`. For a percentage code the two agree. For a fixed-amount
code the customer is given the discount once per booking. It has happened:

| Customer | Code | Type | Bookings | Shown | Given |
|---|---|---|---:|---:|---:|
| 67 | `VIAK-VNTV-RJD6` | fixed 30 | 2 | 30 | **60** |
| 123 | `VIAK-2GDV-2HUE` | fixed 50 | 2 | 50 | **100** |
| 216 | `VIAK-27C9-MMP7` | 10 % | 2 | 150 | 150 ✓ |

**Nothing clamps the discount to the fee.** Booking 000512 took a fixed CHF 648
code against a CHF 499 course and produced **invoice 000419 with a grand total of
−149.00, status OPEN** — a negative invoice sitting in the live books today. The
same booking also carries `has_rental = 1` with no rental invoice, so that CHF 80
laptop was never billed either. It is the only booking of the 710 where the
discount exceeds the fee, and the only unbilled rental on a course that ran.

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

### Bookmarks: 17 rows in three years

The whole feature — a facade, a controller, two routes, a model, a table — has
17 rows across the life of the site. It is five one-liners over a pivot, so it is
cheap either way, but it does not deserve a facade, an Action layer or a place in
the SPA's navigation. Build it as model methods on `User`, or ask the client
whether it is worth carrying across at all.

### Participant thresholds are notifications, so they are a listener

`ParticipantsChange::handle()` is pure notification — min reached, max reached,
dropped below min — which makes it the case the event/listener rule in
`00-foundation.md` was written for. It also fires only on `==`, so two bookings
in one cycle step over `max` and the notice is lost; see **Notify on crossing,
not on equality** there.

## Open questions

1. **Is the cancellation penalty actually enforced?** Six of fourteen qualifying
   cancellations were charged, one of those was then waived, and the dates rule
   out the rule having arrived late. Ask the client whether the rework should
   raise the penalty invoice automatically, propose it for a human to approve, or
   record the entitlement and leave the charging to an admin. *Client question,
   and it decides how much of this chunk is automatic.*
2. **Is a fixed-amount code per basket or per booking?** Legacy displays one and
   charges the other. Two customers were given double. *Client question, cheap to
   answer, and the answer belongs in a test.*
3. **What happens when a student who has already paid cancels late?**
   `createFromBookingWithPenalty()` returns the paid invoice untouched: no credit
   note, no refund, no record. A 50 % penalty on a paid booking currently means
   the customer keeps paying 100 %. *Client question.*
4. **Invoice 000419 (booking 000512) is open at −149.00, and its CHF 80 rental
   was never billed.** Ours to raise with the client before the port carries it
   across verbatim — which, per chunk 03's doctrine, is what the port will
   otherwise do.
5. **Are bookmarks worth porting?** 17 rows. *Client question.*
6. **Does an admin cancel on a student's behalf, and through which path?** It
   would explain some of the eight uncharged cancellations, and it decides
   whether `CancellationReason` needs a third case.
