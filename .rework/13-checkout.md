# 13 — The checkout, rethought

The checkout chunk 06 built is legacy's: courses only, an account before the
basket, no payment. With licences for sale (chunk 05) that no longer fits.

## Status

**Scoped 2026-09-30** (Marcel), with three answers the same day, below.
Designed before anything is built, because it replaces the flow in
`06-bookings.md` rather than adding to it. ~~Waits on the licence answers
(#33–39)~~ **answered 2026-10-08** (`05-licences.md`): one select per product,
a quantity with a minimum on four variants, and demos at CHF 0.

**Built 2026-10-20**: paying an invoice by card (#28, below), then the
checkout with licences and the account's orders (*The checkout as built*,
below). Marcel confirmed the two open points the same day: orders listed in
the profile and paid from there; card only for licences at checkout.

**Reopened 2026-10-26** by the client: licences paid by invoice as well as by
card (*Invoice only, as well as split*, at the end). **The path is built and
switched off** until #58 says who may take it; #59 is open.

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

## The checkout as built — 2026-10-20

**The basket is open to everyone.** `/de/checkout/basket` and
`/api/basket/price` no longer need a login; *Weiter* goes to the address step,
whose guard sends a guest to the login (or registration) and back. The layout's
`authenticated` meta, which only existed to stop guests asking for a price, is
gone.

**Licences in the basket** sit beside the courses (`basket.licences`:
variant, quantity, host), one line per licence and host. *In den Warenkorb* on
the software page checks the host and the minimum, and the server checks them
again with the licence still in the shop ([[PriceBasketRequest]]); a line that
fails is dropped from the basket with the message as a toast, so one stale
licence cannot block the whole basket. Each line is priced with **its VAT per
line, as the invoice will be** ([[BasketLicence]]): a summed VAT could differ
from the invoice by a centime, and Stripe's amount check would refuse it. A
discount code touches courses only (#3), so the payment step asks for one
only when there is a course.

**The steps**: the address step asks *Lizenzen senden an* when there is a
licence (kept as `users.licence_email`, #46; empty, or the account's own
address, stores nothing) and says *Adresse* / *entspricht obiger Adresse*
without the RAV passage when there is no course. The payment step says
licences are paid by card after the summary. The summary lists the licence
rows, *Lizenzen an*, one MWST row for laptops and licences, and, when the
basket holds both, which part is paid now by card and which by invoice later.
The button is *Buchen* for courses, *Bestellen und bezahlen* with a priced
licence, *Bestellen* for demos only.

**Bestellen**: the total shown is checked against the whole basket; the
courses are booked first (the part that can still be refused), then the
licences are placed ([[PlaceLicenceOrder]]) and invoiced, and a priced order
goes straight to Stripe. Its return lands on the confirmation, which settles
the payment as the invoice page does; the webhook covers the rest. Cancelled
there, the confirmation says the order is waiting and offers *Jetzt bezahlen*;
the courses stay booked (#45). A free order skips Stripe.

**Mails** ([[LicenceOrderPlaced]], shop orders only, not the dashboard's):
*Bestellbestätigung – Bestellung …* to the customer with the invoice attached,
worded when it is sent (paid, or the card button), and *Neue Softwarebestellung
…* to the office with article numbers, hosts and a link to the dashboard order.

**The profile** lists *Bestellungen* (shut, with the count, only for a
customer who has one): number and date, the lines, the amount with
*Bezahlt* / *Rechnung offen* / *Kostenlos* and *Versendet* / *Nicht versendet*,
and *Bezahlen* while the invoice is open.

Still open: dispatch is not held back for an unpaid order (#2, the client);
orders cannot be cancelled; VIAK gets no reminder of unpaid orders (Marcel,
2026-10-20: none wanted, the dashboard shows *Rechnung offen*).

## Das könnte dich auch interessieren — built 2026-10-20

The client (2026-10-20): the shop's fixed bundles, software and software plus
course, give way to recommendations VIAK maintains, so customers put their own
bundle together ("beim Kauf von Rhino unten beliebte Plugins oder unsere
Kurse").

- **Picked** in the software form and the course form: *Empfohlene Kurse* and
  *Empfohlene Software*, the Vorhaben's ordered pickers (`Field::offers`),
  kept in one `recommendations` table (owner and item both morphs, `order`)
  ([[Recommendations]]). A software never recommends itself.
- **Shown** under a software's page, a collapsible after *Kurse*, without the
  courses the page already lists there; and **under the basket** (step 1
  only), what was picked for its courses and software, without what is in
  it, at most six cards, courses first. *Beliebte Angebote*'s cards and grid,
  tagged *Kurs* / *Software* (`x-recommendations`). The basket asks
  `/api/basket/recommendations`, which answers rendered cards, since they are
  Blade components and the basket is drawn in the browser.
- **Not on course pages yet**, though a course can carry picks: whether they
  show there is the client's question (*Projektstand*), as is whether a
  bundle is cheaper. No price rule is built; if it comes, it touches #3.
- Dev data: Rhinoceros recommends its three courses and V-Ray, Twinmotion,
  Enscape, Maxwell.

## Invoice only, as well as split — asked 2026-10-26

The client, in the meeting of 2026-10-26: two payment options, **invoice only**
(courses and licences) or **split**, as built (licences by card now, courses
invoiced on confirmation). This reverses *card only for licences at checkout*
(Marcel, 2026-10-20, *Status*).

**Most of it exists.** A licence order whose card payment is abandoned is
already an order with an open invoice, payable later from the profile or the
invoice page (#45, *The checkout as built*). *Invoice only* is that order
placed on purpose: [[PlaceLicenceOrder]] and its invoice as now, no Stripe,
and the order mail worded for an invoice (with the QR bill and the card
button, as a course invoice has).

**What changes:**

- The payment step offers the choice when the basket holds a priced licence:
  *Per Rechnung* or *Per Karte* (courses are invoiced either way). The summary
  and its button follow it (*Bestellen* rather than *Bestellen und bezahlen*).
- The confirmation and the mails say the invoice follows, for both parts.
- Dispatch: today nothing holds a licence back for an unpaid order (#2). With
  invoice as a chosen option rather than an abandoned card, #2 stops being
  theoretical: VIAK will see paid and unpaid orders on the worklist and must
  decide what it sends.

**Questions** (in `Open-Questions.md`):

- **58.** Who chooses: the customer at checkout, or VIAK per customer (say,
  invoice only for known companies, card for everyone else)? Or a site-wide
  setting?
- **59.** The due date of a licence invoice: the 10 days every invoice has
  today (`invoice.payment_period`), or another period? A course invoice is
  also due 10 days before the course; a licence has no such date.

### The path, built and off — 2026-10-26

- **[[LicencePayment]]**, `card` or `invoice`. `LicencePayment::offeredTo()`
  is the one place #58's answer goes: today it reads
  `config('invoice.licence_by_invoice')`, **false**, a code setting rather
  than an env key because the answer may be per customer (a flag on the
  account, checked there) or invoice-only for everyone.
- **The summary's POST** takes `licence_payment`, only a value the customer
  is offered; none means card, so nothing that posts today changes. With
  `invoice`: courses booked, the order placed and invoiced as before, the
  mails as before, **no Stripe**, straight to the confirmation.
- **The mail needed nothing**: it already attaches the invoice and, for an
  unpaid one, offers the QR bill or the card button and says the licences
  follow payment (`mail/licence/order`). That sentence is #2's answer
  written down early; if VIAK sends before payment, it changes here and on
  the confirmation.
- **The confirmation** says the invoice is on its way, QR or card, licences
  after payment, with *Jetzt bezahlen* under it as for a waiting order.
- **Not built, waiting on #58**: the choice on the payment step or summary
  (two options, and *Bestellen* rather than *Bestellen und bezahlen* when
  invoice is chosen). Switching it on is that UI and the line in
  `offeredTo()`.
- Tests in `LicenceCheckoutTest`: by invoice when offered; refused while it
  is not; card still the default when it is.
