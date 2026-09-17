# 05 — Software licences (not yet built)

The second thing VIAK sells.

## Status

Not built. Shape settled 2026-09-17 against the client's answers and the mockups
in `history/mockup/`. One question left open — the Bildung tier's Nachweis — and
it gates a corner of the chunk rather than the chunk itself.

The short version: **there is no integration, and a licence is not an entity.**
Fulfilment is a human forwarding an email, and *Meine Lizenzen* is purchase
history. What is left is a catalogue with variants, an order line, and an admin
worklist. Chunk 03 owns the money and is where the real weight sits.

## Fulfilment is manual, and there is no reseller API — answered 2026-09-17

The whole loop, as the client described it:

1. The customer orders the licence on the site and pays.
2. VIAK receives an email.
3. A human at VIAK orders the licence from the reseller.
4. A human at VIAK sends the licence to the customer.

**No integration. No vendor SDK, no API credentials, no sandbox, no third-party
failure modes.** That is the single largest thing this answer removes, and it
takes most of the imagined weight of this chunk with it. What remains on our side
is small and entirely ours:

- a purchasable product,
- a basket line and an order that is not a course booking,
- a notification to VIAK when one is bought,
- an admin surface where a human marks it dispatched.

### What it costs instead

Delivery is at human speed. Nothing can promise a key on the payment
confirmation screen, no queued job can deliver one, and the gap between "paid"
and "has licence" is however long it takes someone at VIAK to work through their
inbox. That gap has to be modelled — an order item that is paid and not yet
fulfilled is a normal state here, not an error — and it has to be visible to the
customer, because they have paid for something they do not yet have.

It also means the dispatch step is the only thing standing between a paying
customer and silence. An email to `info@` is not a work queue. The admin needs a
list of outstanding licence orders that a person can work through and tick off,
and that list is the actual deliverable of this chunk.

## Anyone may buy — answered 2026-09-17

Students and non-students both. A licence sale does not require a course
booking and does not require ever having been a student.

This is the structural half of the answer, and it lands on chunk 03, not here:

- **`invoices.booking_id` cannot survive.** A licence-only order has no booking.
  That alone does *not* force Order/OrderItem — a polymorphic `invoiceable`
  solves it, and more cheaply. The open question is whether one checkout produces
  one invoice or several; see `03-invoices.md`.
- **A buyer is a user with no booking.** Chunk 01 ported 578 users who are all
  students. The rework now has account holders who have never attended anything —
  which touches the role pivot from chunk 02, the account pages, and whatever
  the checkout does about guests.
- **A basket can hold both.** A course (VAT-exempt) and a licence (8.1 %) in one
  checkout. Whether that becomes one invoice with two VAT treatments or two
  invoices with one each is the granularity question in `03-invoices.md`.

## Consequence: an invoice granularity question — belongs to chunk 03

Legacy carries **one** `vat` column for the whole invoice and gets away with it
because it has never issued a mixed invoice: the only VAT it charges is the CHF 80
laptop rental, and every rental is billed separately.

A basket holding a course (exempt) and a licence (8.1 %) only breaks that **if
one checkout produces one invoice**. Legacy's rule is one invoice per item — 35
multi-booking baskets in the data, every booking invoiced on its own — so the
scalar column keeps working if that rule is kept, and a polymorphic `invoiceable`
is then enough to carry licences.

**That is a live decision, not a foregone one**, and it is chunk 03's. The
trade-off table and the recommendation (invoice per order, so a customer buying a
course and a licence gets one bill rather than two) are in `03-invoices.md`.
Whichever way it goes, the 561 historical invoices keep their stored `vat`
verbatim on the port.

## What a licence is — answered 2026-09-17

The first answer was "the software as a shop item, that's it". Put next to the
mockups, it resolved into three decisions:

| | Decision |
|---|---|
| **Variants** | **Yes.** A product has purchasable variants — Einzelplatz / Netzwerk / Studierende — at their own prices. The "ab CHF 590.–" on the hub is the cheapest of them. |
| **Preis auf Anfrage** | **Real.** Some software cannot be bought directly at all. Seven of the nine products on `Software.html` are in this state, so it is the common case, not the exception. |
| **Meine Lizenzen** | **Purchase history only.** Not a licence with a life. |

### Meine Lizenzen is history — and that is the decision that shrinks this chunk

The mockup shows validity ("Gültig unbefristet", "Gültig bis 03.02.2027"), a
renewal ("Verlängert am 03.02.2026") and a per-licence "Verwalten →". **None of
that is being built.** The page lists what the customer bought and when.

This is worth stating loudly because it removes an entity. A licence is **not** a
record with an owner, a state and a lifespan — it is a line on an order that has
been fulfilled. Everything that would have followed from the other reading goes
away with it:

- no `Licence` model holding a key, a seat count and a validity window,
- nothing that has to notice an expiry, and no scheduled job to notice it with,
- no renewal flow — a renewal is just buying again,
- no "Verwalten", which would have meant either a vendor portal we do not
  integrate with or an account management surface nobody has designed.

It also means **we never hold a licence key.** VIAK forwards it from their own
mailbox; it does not pass through the system. That is one less secret to store,
and it is why the history page can only ever show a product, a variant and a date.

The cost is the customer's: a year after buying an annual licence, *Meine
Lizenzen* will still say they bought it and will not say whether it still works.
That is a fair v1 trade — the licence's real state lives with the vendor anyway —
but it is a deliberate departure from the mockup, not an oversight. **Do not
"fix" it later without asking.**

## The shape that falls out

Small, and entirely ours:

- **Product** — the software. Already has a marketing page in chunk 04.
- **Variant** — belongs to a product; a name, and a price that is **nullable**.
  No price *is* "Preis auf Anfrage": it renders as an enquiry, never as a basket
  button. That keeps the two cases one model rather than two, and makes the
  hub's "ab CHF" a `min(price)` over the purchasable variants.
- **OrderItem** — points at a variant instead of a booking. Carries its own VAT,
  per `03-invoices.md`.
- **Fulfilment** — a status and a timestamp on the licence order item: *paid,
  awaiting dispatch* → *dispatched*, set by a human.
- **The admin worklist** — outstanding licence orders, worked through and ticked
  off. This is the actual deliverable of the chunk; an email to `info@` is not a
  work queue.
- **Meine Lizenzen** — a query over fulfilled licence order items. No new model.

## Open questions

Blocking the scoping of this chunk:

1. **Is the Bildung tier in scope, with its Nachweis?** `Twinmotion-Lizenzen.html`
   prices it at CHF 145/Jahr behind "Nachweis nötig", and the CTA is "Nachweis
   einreichen" rather than a buy button. That is an upload, a human review and an
   approval standing between the customer and the basket — a different flow from
   both "buy" and "enquire", and the only place where *who* is buying matters.
   Three ways out, and it needs the client: build the proof flow, treat Bildung as
   an enquiry variant like Preis auf Anfrage (cheap, and probably right for v1),
   or drop the tier.

Not blocking:

- **Pricing rules** — none yet (asked 2026-09-17). Whether discount codes apply
  to licences, and whether students pay differently, can be decided once the
  catalogue exists.
- **Payment methods** — both software mockups advertise TWINT, credit card and
  invoice. Belongs to chunk 03.
- **Copy at launch.** The mockups are wireframes and their words are filler —
  `Twinmotion-Lizenzen.html` currently promises "Lieferung sofort per E-Mail",
  which a human-in-the-loop process cannot honour, and *Meine Lizenzen* shows
  validity we do not track. Nothing to decide, but the real copy has to say that
  a licence arrives by email once VIAK has ordered it.
