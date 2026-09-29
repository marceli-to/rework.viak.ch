# 05 — Software licences (not yet built)

The second thing VIAK sells.

## Status

Not built. Shape settled 2026-09-17 against the client's answers and the mockups
in `history/mockup/`, then **revised 2026-09-29** against the client's product
list: this replaces an existing shop, the catalogue has three levels rather than
two, and an order line has a quantity. See *The real catalogue* below. The
questions that list raised went to the client on 2026-09-29 and are under *Open
questions*; prices and article numbers are the one that blocks an import.

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
- **A basket can hold both.** A course and a licence in one checkout — billed on
  two different triggers, so two invoices on two different days. See below.

## A licence bills on a different trigger — belongs to chunk 03

A course invoice is raised when the **event is confirmed**, not at checkout —
mean lag 27.7 days in the live data. A booking is a commitment; until the course
has the numbers there may be nothing to charge for.

A licence has no such step. No headcount, nothing to call off, available the
moment it is paid for — so it is invoiced **at purchase**.

That means a basket holding a course and a licence necessarily produces two
invoices, on different days. Not a compromise; it falls out of the domain. It
also means `invoices.booking_id` goes (a licence invoice has no booking) while
the scalar `vat` column can survive, since each invoice still covers one billing
event. See `03-invoices.md` for the schema call and for the one question it
raises: whether VIAK orders from the reseller before or after the money arrives.

## What a licence is — answered 2026-09-17

The first answer was "the software as a shop item, that's it". Put next to the
mockups, it resolved into three decisions:

| | Decision |
|---|---|
| **Variants** | **Yes.** A product has purchasable variants — Einzelplatz / Netzwerk / Studierende — at their own prices. The "ab CHF 590.–" on the hub is the cheapest of them. |
| **Preis auf Anfrage** | **Real.** Some software cannot be bought directly at all. ~~Seven of the nine products on `Software.html` are in this state, so it is the common case, not the exception.~~ **2026-09-29:** that count came from the mockup. In the real catalogue nearly everything is sold at a price, so this is the exception. |
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

## The real catalogue — 2026-09-29

The client went through their existing shop with Claude and sent the result as a
spreadsheet (`Software_Lizenztypen_claude.xlsx`, kept outside the repo on
Marcel's desktop): one row per product, with Hersteller, Produktart (Software /
Plugin), Lizenztyp (Perpetual / Subscription), Lizenzzugriff (named / floating),
Plattform, Frontend-Sichtbarkeit, a remark and the shop URL.

### This replaces 3d-software.ch — answered 2026-09-29

VIAK already sells software, at **3d-software.ch**. Nothing above knew that; it
was all derived from the mockups. **The rework replaces that shop.** So the
catalogue is not a blank page to be designed, it is an existing one to be moved,
and the mockup's nine products are not the scale.

The spreadsheet has **no prices**, and the article numbers (RHN-1002, VRY-1001…)
survive only inside the shop URLs. Both were asked for on 2026-09-29, open
question 2 below. We do not scrape them from the old shop: the client owns the
numbers and should hand them over.

### What is in it

| | |
|---|---|
| Products | **107**, in 17 groups (Rhino, V-Ray, Twinmotion, Cinema 4D, Maxwell…) |
| Manufacturers | 15 — Chaos alone has 35 products, Next Limit 19 |
| Listed in the shop | 94; the **13 hidden** ones are all EDU, lab and university seat-band licences |
| Licence type | 58 subscription, 40 perpetual, 9 none (demos) |
| Demos | 10 |
| Updates and upgrades | 14 |

### Three levels, not two

The 2026-09-17 shape was `software` → variants. The list does not fit it. A
spreadsheet group is **not a product**: the Rhino group holds plugins by five
different makers (Bongo, VisualARQ, Lands Design, Karamba3D, Drakon), and the
client's own dropdown example splits V-Ray from V-Ray Rendernode, both in the
V-Ray group. So:

```
software            the group — Rhino, V-Ray, Maxwell…  (what courses hang off)
licence_products    V-Ray, V-Ray Render Node, Bongo 2…  manufacturer_id, slug, copy
licence_variants    Solo named / Premium floating / 5 Stück / Update…  price, term
```

- **`software` stays the group**, so courses and licences still meet on one row,
  which was the point for the Vorhaben pages. Eight of the 17 groups already
  exist as course software (Rhinoceros, V-Ray, CINEMA 4D, Twinmotion, Corona
  Renderer, ZBrush, Enscape, Lumion); Unreal Engine, Veras, formZ, KeyShot,
  Maxwell, Anima, RealFlow, HDR Light Studio and MyArchitectAI would be new rows.
  Drakon is a course software row *and* a product in the Rhino group — fine, the
  two levels are independent.
- **The manufacturer moves to the product.** The proposal below put
  `manufacturer_id` on `software`; the Rhino group alone has five makers, so it
  cannot live there.
- **The Hersteller filter** on the hub then filters products, not software.

### Variants carry a term

The client wants the edition in one dropdown and the term (1 / 3 years) in a
second. The cheapest shape that gives that: a variant is a flat, priced row, and
has an **optional `term_years`**. The page shows the distinct editions in the
first select and the terms that edition has in the second. No general
option/combination system — a product with no terms (every perpetual licence)
simply shows one select.

16 products say "3-Jahreslizenz verfügbar" but none has a 3-year product in the
shop today. Whether its price is fixed or 3× annual is open question 3.

The render-node packs (1 / 5 / 20 Stück) are **variants**, not a quantity: each is
its own SKU at its own price.

### Things that are not variants

- **Host software.** Maxwell V5 and the RealFlow Plugin come for Rhino, Archicad,
  Cinema 4D, SketchUp… at one price. Seven hosts × node-locked / floating as
  variants would be fourteen rows with identical prices. Instead the product
  declares a list of choices and the chosen one is **frozen on the order line**,
  like the price. VIAK needs it to order the right thing from the reseller.
- **Quantity.** "Mindestens 3 Lizenzen" on the Cinema 4D and Maxon One Teams
  licences is a quantity with a minimum. `licence_orders` below had no quantity;
  it gains `quantity`, and the variant gains `min_quantity` (nullable).
- **Platform, named/floating on products that allow both, "max. 2 Computer",
  "Studio + alle Plugins".** Display information on the product. Nothing
  computes with them.

### Hidden products

The 13 EDU and seat-band licences are in the shop and sold, but not listed. That
is not `publish = false` — an unpublished product is one nobody can buy. It needs
a separate *not listed* state. Whether a customer can reach one on the site at all
(a direct link) or VIAK only ever invoices it is open question 5; if the latter,
a hidden product is just a preset for the blank one below.

This answers half of the Bildung question: education licences are real. They
are hidden and sold on request, which is the cheap middle option that question
already proposed.

### The blank product — asked 2026-09-29

The client's example: someone wants an annual licence for only a few months, VIAK
works out the price by hand and still sends a normal invoice. That is **a licence
order an admin creates**, with a free title and a free price instead of a
variant: `licence_variant_id` becomes nullable, and the order freezes a title as
it already freezes the price. It should still land on the dispatch worklist,
because VIAK still has to order it from the reseller.

Chunk 03 already allows the invoice side: `invoice_items.itemable` is nullable and
the line carries its own description.

### Catalogue admin

The client wants to create, edit and delete products themselves. Expected — the
catalogue is theirs and changes, EDU products are added on demand. A dashboard
screen, not a seeder.

### Data to fix before an import

- "TWinmotion" in three rows (Excel autocorrect).
- MyArchitectAI's demo is typed Subscription; every other demo has no type.
- MyArchitectAI (4 products) and V-Ray Render Node 20 Stück are marked visible
  but have no shop URL — new, presumably.

All three are in the 2026-09-29 questions.

## The shape that falls out — proposed 2026-09-17

**2026-09-29:** partly superseded by *The real catalogue* above — three levels,
manufacturer on the product, a quantity and a host choice on the order. Kept as
written for the reasoning.

Small, and entirely ours. **Proposals, not decisions** — written down so the
build starts from something concrete rather than re-deriving it.

### `licence_orders` — the missing parallel to `Booking`

For a course, `Booking` is the thing that exists between checkout and invoicing:
it freezes what was sold, and the invoice arrives later when the event confirms.
A licence has no equivalent, and it needs one. The fulfilment state has to live
somewhere, and the admin worklist needs a table to query.

```
licence_orders   uuid, number, user_id
                 software_variant_id
                 price            frozen at purchase, as bookings.course_fee is
                 invoice_address  json, frozen — same reason as on bookings
                 state            AWAITING_DISPATCH | DISPATCHED
                 ordered_at, dispatched_at, dispatched_by
```

`invoice_items.itemable` then points at `Booking | LicenceOrder`, which is the
same morph pattern `course_taxonomy` already uses.

`dispatched_by` is worth having from the start: fulfilment is a person doing
something manual, and when a customer says the licence never arrived, the
question is who sent it and when.

Whether `state` gains a third value — dispatch before or after payment — is open
question 2 in `Open-Questions.md`.

### `software` outgrows being a taxonomy

`software` is one of five identically-shaped taxonomy tables today: uuid, `json
title`, order, publish. The licence catalogue needs it to be a content entity —
slug, descriptions, SEO, a marketing page — plus **variants**:

```
software              + slug, summary, description, seo_*, manufacturer_id
software_variants     software_id, title, price NULLABLE, order, publish
```

**The nullable price is what makes "Preis auf Anfrage" work** without a second
model: a variant with a price is purchasable, a variant without one renders as an
enquiry. The hub's "ab CHF 590.–" is then `min(price)` over the priced variants.

The mockups also filter by **Hersteller** — Robert McNeel, Chaos, Epic Games,
Maxon — which is just another taxonomy, so the cheapest route is adding
`manufacturers` to the existing taxonomy migration's `TABLES` array. Identical
shape, no new pattern.

**This breaks a symmetry on purpose.** `2026_09_11_000002_create_taxonomy_tables`
deliberately built all five taxonomies from one loop because they differ only in
meaning. `software` now stops being one of the five and becomes a model with a
taxonomy-shaped past. That is the right call — it is the only one of the five
that is a thing customers buy rather than a label — but it should be a decision
rather than a drift.

The payoff is that courses and licences hang off the same `software` row, which
is exactly what the Vorhaben pages need: a curated list of courses *and* licences
for one tool, from one relation.

### The account area gates on being logged in, not on `Role::Student`

`Role::Student` is documented as "books courses". A licence-only buyer books
nothing and may well be a company. Nothing gates on the role yet, so this is a
note for when the SPA routes are built rather than a fix: *has an account* is not
one of the three capabilities, and should not be made into one.

The roles stay as they are — the pivot decision in `02-courses-events.md` holds.

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
   **2026-09-29:** half answered by the product list: EDU licences are real, and
   today they are hidden products sold on request. What stays open is whether
   the site ever sells one itself — that is question 5.

Asked 2026-09-29, with the product list (`Fragen-Software.txt`, sent by Marcel):

2. (OQ 33) **Prices and article numbers** for all 107 products, hidden ones included,
   and whether prices are gross or net. **Blocks the import.**
3. (OQ 34) **The 3-year term:** a fixed price per product, or 3× annual?
4. (OQ 35) **Demos:** what "buying" one means — a free order, an enquiry, or a link.
5. (OQ 36) **Hidden products:** can a customer order one on the site (a direct link), or
   does VIAK only invoice them?
6. (OQ 37) **Updates and upgrades:** an edition in the product's dropdown, or their own
   product as today? The three-level shape takes either.
7. (OQ 38) **"Nur zusammen mit Neulizenz"** (Maxwell V5 Rendernodes Bundle): enforced by
   the basket, or a note on the product? A note is the proposal.
8. (OQ 39) **Minimum and maximum quantities** beyond the Teams licences' 3.

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
