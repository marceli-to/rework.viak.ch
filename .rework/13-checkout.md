# 13 — The checkout, rethought (being built)

The checkout chunk 06 built is legacy's: courses only, an account before the
basket, no payment. With licences for sale (chunk 05) that no longer fits.

## Status

**Scoped 2026-09-30** (Marcel), with three answers the same day, below.
Designed before anything is built, because it replaces the flow in
`06-bookings.md` rather than adding to it. ~~Waits on the licence answers
(#33–39)~~ **answered 2026-10-08** (`05-licences.md`): one select per product,
a quantity with a minimum on four variants, and demos at CHF 0.

**Built 2026-10-20, first slice: paying an invoice by card** (#28, below).
The checkout itself and the account's orders come next.

## Paying an invoice by card — built 2026-10-20

Legacy's flow, with Marcel's word for it: **a course is paid only after it is
confirmed**, from the *Zahlung per Kreditkarte* button in the confirmation
mail; legacy's checkout takes no payment. A licence order's invoice exists the
moment it is placed, so the same page settles it, at checkout and later.

**How legacy does it** (`../viak.ch/app/Http/Controllers/PaymentController.php`):
`/de/zahlung/rechnung/{uuid}` shows the booking and the total; *Weiter zur
Zahlung* puts the invoice uuid in the session and opens a Stripe Checkout
session (card only, one line); `/de/zahlung/erfolgreich` reads the uuid back,
marks the invoice PAID, posts the payment to Run My Accounts (account 1107,
*Zahlung via Kreditkarte*) and fires `InvoicePaid`, whose handler mails the
student and the office. `/de/zahlung/abbrechen` clears the session.

**Its weak spots, read from the code** (Marcel: fix them or make sure they are
covered):

1. **No webhook, nothing asked of Stripe.** The success URL alone settles the
   invoice, so an abandoned payment followed by a visit to it marks the invoice
   paid and sends both mails; a customer who pays and closes the tab leaves it
   open. *Fixed*: the browser hands back only `session_id`; the return page
   asks Stripe whether that session is paid, and a webhook
   (`checkout.session.completed`, `/stripe/webhook`, signature checked) settles
   the ones who never return. [[RecordCardPayment]] is safe to call from both:
   it locks the row and keys on Stripe's payment id (`invoices.card_payment`).
2. **Course invoices only.** The Stripe line was built from
   `$invoice->booking->event->course`; a licence invoice has no booking.
   *Fixed*: the line is *Rechnung {number}* with the invoice's lines as its
   description, and the page lists the invoice's lines.
3. **`create()` did not check the invoice was the user's** (`index()` did).
   *Fixed*: every step 404s on somebody else's invoice.
4. **The books were posted inline**, after the invoice was already marked
   paid: a Run My Accounts error left the books open for good. *Now* a queued,
   retried job ([[RecordPaymentInBooks]]).

Also refused, and logged for a refund by hand: an amount or currency that is
not the invoice's, and a payment for an invoice already settled (paid by QR,
paid in a second tab, cancelled). Paid or cancelled, the page says so instead
of offering *Weiter zur Zahlung* (legacy's `info` page).

**No real money from a prototype**, as with Run My Accounts
([[AppServiceProvider]]): without `STRIPE_SECRET`, every environment but
production gets a fake Stripe page at `/dev/zahlung/{session}` (*Bezahlen* /
*Abbrechen*), and the rest of the flow is the real one. A test key
(`sk_test_…`) uses Stripe's test mode; a live key outside production, and
production without a key, throw. Legacy's `.env` holds a test key for VIAK's
Stripe account, not copied here.

Mails: *Zahlungsbestätigung Rechnung …* to the customer and the office
([[10-mail]]), legacy's text. Cancel on Stripe's page returns to the invoice
with legacy's cancel sentence above it, rather than a page of its own.

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
