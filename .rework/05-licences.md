# 05 — Software licences (not yet scoped)

The second thing VIAK sells. Placeholder: it holds the client answers of
2026-09-17 and the questions they opened, so the scoping pass has somewhere to
start.

## Status

Not built, not scoped. The fulfilment answer below removes what was the only
question blocking the build (`00-foundation.md`), but the mockups and the answers
disagree about what a licence *is* — see *Where the answers and the mockups
disagree*. Scoping waits on that.

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
  The Order/OrderItem design in `03-invoices.md` stops being a preference and
  becomes required.
- **A buyer is a user with no booking.** Chunk 01 ported 578 users who are all
  students. The rework now has account holders who have never attended anything —
  which touches the role pivot from chunk 02, the account pages, and whatever
  the checkout does about guests.
- **A basket can hold both.** A course (VAT-exempt) and a licence (8.1 %) in one
  order — see the VAT consequence below.

## Consequence: VAT moves to the line — decided here, belongs to chunk 03

The legacy `invoices` table carries **one** `vat` column for the whole invoice,
and it gets away with it because the legacy site has never issued a mixed
invoice: the only VAT it charges is the CHF 80 laptop rental, and that rental is
billed on its own separate invoice — all 29 are exactly `80.00 + 6.50 = 86.50`,
with nothing else on them.

Once anyone can put a course and a licence in the same basket, that trick stops
working. One order, one invoice, two lines, two different VAT treatments.

**VAT is computed and stored per line item, and the invoice total is the sum.**
A single invoice-level `vat` column cannot represent a mixed order, and splitting
every mixed basket into two invoices to preserve it would be the legacy
workaround carried forward for no reason. The historical rows keep their
invoice-level `vat` verbatim on the port — see `03-invoices.md`, which also owns
the rounding rule (to the centime, never to 0.05).

## Where the answers and the mockups disagree

The client's description of the product is **"the software as a shop item, that's
it"**. The 24 signed-off mockups in `history/mockup/` describe something with
considerably more structure. This is not a small gap and it should be closed
before anything is modelled.

What the mockups actually show:

| Mockup | What it shows | What a flat shop item gives you |
|---|---|---|
| `Twinmotion-Lizenzen.html` | Two tiers — Einzelplatz **CHF 590/Jahr**, Bildung **CHF 145/Jahr** | One price |
| `Software.html` | Filter *Lizenztyp*: **Einzelplatz / Netzwerk / Studierende** | No types |
| `Software.html` | "**ab** CHF 590.–", "**ab** CHF 995.–" | A from-price implies variants to be cheapest of |
| `Software.html` | 7 of 9 products are "**Preis auf Anfrage**" | Not purchasable at all — an enquiry, not a basket |
| `Twinmotion-Lizenzen.html` | Bildung tier: "**Nachweis nötig**", CTA is "Nachweis einreichen" | No gate, no proof, no approval step |
| `Meine-Lizenzen.html` | "Gültig **unbefristet**" vs "Gültig **bis** 03.02.2027" | No validity period |
| `Meine-Lizenzen.html` | "**Verlängert am** 03.02.2026" | No renewal |
| `Meine-Lizenzen.html` | "**Verwalten** →" per licence | Nothing to manage |

Three of these are not modelling details, they are separate features:

1. **Preis auf Anfrage.** Seven of the nine products on the software hub have no
   price. Either they are enquiry forms that never enter a basket, or the
   catalogue is genuinely two-thirds unfinished. Those are very different builds.
2. **The Bildung tier's proof.** "Nachweis einreichen" is an upload, a review, an
   approval and only then a purchase — with a human decision in the middle. It is
   its own flow, and it is the one place where *who* is buying actually matters,
   which sits oddly next to "anyone may buy".
3. **Meine Lizenzen.** The page is built entirely out of things a flat shop item
   does not have: validity, expiry, renewal, management. Without them it degrades
   to a list of past orders — which may be fine for a first release, but it is
   not the page in the mockup.

### And one outright contradiction

`Twinmotion-Lizenzen.html` promises, in body copy:

> Offizieller Schweizer Reseller von Epic Games. Zahlung per TWINT, Kreditkarte
> oder Rechnung, **Lieferung sofort per E-Mail**.

Delivery is *not* immediate. It is a human at VIAK reading an email, placing an
order with the reseller, waiting for it, and forwarding it on. Either the copy
changes before launch or the promise is broken on every single sale. This is a
one-line fix on a mockup and a serious problem if it ships — worth raising with
the client on its own, ahead of any scoping.

## Open questions

Blocking the scoping of this chunk:

1. **Is a licence one price or a set of variants?** The mockups say Einzelplatz /
   Netzwerk / Studierende at different prices; the client says one shop item.
2. **What happens to "Preis auf Anfrage"?** Enquiry form, or a price to be filled
   in later? Seven of nine products depend on the answer.
3. **Is the Bildung tier in scope?** If yes it brings proof upload and manual
   approval with it. If no, the tier comes off the mockup.
4. **Does the licence key enter the system?** If VIAK forwards it from their own
   mailbox, we never hold it, and "Meine Lizenzen" can show an order and a date
   and nothing more. If it is pasted into the admin at dispatch, the page in the
   mockup becomes possible — and we are then storing a licence key, which needs a
   deliberate decision about where and how.
5. **Do licences expire?** "pro Jahr" and "Gültig bis" say yes. If they do,
   something has to notice an expiry and something has to sell a renewal, and
   neither is a shop item.

Not blocking:

- **Pricing rules** — the client has none yet (asked 2026-09-17). Whether
  discount codes apply to licences, and whether students pay differently, are
  open but can be decided after the shape is settled.
- **Payment methods** — both software mockups advertise TWINT, credit card and
  invoice. That belongs to chunk 03.
