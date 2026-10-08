# 13 — The checkout, rethought (not yet built)

The checkout chunk 06 built is legacy's: courses only, an account before the
basket, no payment. With licences for sale (chunk 05) that no longer fits.

## Status

**Scoped 2026-09-30** (Marcel), with three answers the same day, below.
Designed before anything is built, because it replaces the flow in
`06-bookings.md` rather than adding to it. ~~Waits on the licence answers
(#33–39)~~ **answered 2026-10-08** (`05-licences.md`): one select per product,
a quantity with a minimum on four variants, and demos at CHF 0.

## What there is today

- **The basket needs an account.** `/de/checkout/basket` and every step after
  it sit behind `auth`, `verified` and `role:student`, as in legacy. The basket
  itself has no server state (its contents are in the browser, its prices come
  from `/api/basket/price`), so the login guard protects nothing there.
- **Four steps after it**: address, payment, summary, confirmation. A POST per
  step, the answers in the session ([[CheckoutSession]]).
- **Payment takes no payment.** The step is a paragraph: an invoice follows
  once the course is confirmed, payable by QR bill or card. The card link in
  the mail is a placeholder (#28).

## Answered 2026-09-30 (Marcel)

- **No guest checkout.** Buying needs an account, courses and licences alike.
- **Stripe** for card payment, as legacy used.
- **A licence can go to another address.** A company buying licences names an
  e-mail the licences are sent to, besides the invoice address (on the account,
  below).

## Why courses and licences pay differently

Already settled in `05-licences.md`, and the reason for this chunk:

- **A course is invoiced when its event is confirmed**, a mean 27.7 days after
  the booking. Until then there may be nothing to charge for, so nothing is
  paid at checkout.
- **A licence is invoiced when it is bought.** Nothing can call it off, so it
  is paid then.
- **A basket holding both makes two invoices on two days.**

## The proposed flow

1. **The basket is open to everyone.** Courses and licences, priced by the
   server as now. No login to look at it.
2. **Login or registration at *Weiter*,** where the invoice address is needed,
   and back into the flow afterwards, basket intact.
3. **Address**: the invoice address, as now. **For licences, the delivery
   e-mail**, defaulting to the account's own.
4. **Payment**, only for what is paid now:
   - *courses only*: no payment step; the text that says the invoice follows
     confirmation, as today;
   - *licences only*: Stripe, card, now;
   - *both*: the licence part by card now, the course part as a booking whose
     invoice follows confirmation. The summary says which is which.
5. **Summary**, then **confirmation**, listing what was booked and what was
   paid.

## Answered 2026-09-30, second round (Marcel)

- **The card is taken on Stripe's page** (Stripe Checkout, #44): the least to
  build, and card data never touches this app. The summary's *Bezahlen* sends
  the customer there; Stripe sends them back to the confirmation, and a
  webhook, not the return, marks the licence order paid.
- **A failed or abandoned payment does not undo the course** (#45). In a mixed
  basket the course is booked at *Bestellen* as today, and its mails go out;
  the licence order waits unpaid, and the customer can pay it later from their
  account. VIAK orders nothing from the reseller for an unpaid order.
- **The licence delivery e-mail lives on the account** (#46): a field on the
  customer's profile, empty meaning the account's own address, shown and
  editable at the address step when the basket holds a licence.

## Still open for the design

- **The card link on a course invoice** (#28): the same Stripe integration, so
  it is designed here and built with it.
- **Paying a waiting licence order later**: from where on the account, and
  whether VIAK is told about orders left unpaid.
- **A zero total** (a basket of demos, 2026-10-08): Stripe Checkout cannot
  charge 0. Proposed in `05-licences.md`, *A free order*: skip payment, the
  order is paid, no invoice, onto the dispatch worklist.
