# 16 — Firmenkurse (built 2026-10-26)

A dashboard module to set up a course for one company and invoice it. Asked
for in the meeting of 2026-10-26.

## Status

**Scoped 2026-10-26.** **60–63 answered the same day by
Marcel, all four the small way**: the participants are a number, the invoice
is free lines, one invoice per Firmenkurs, the enquiry stays a mail. So this
is the small module. **Built the same day** (*As built*, at the end).

## What there is today

- **The Firmenschulung page** (`/de/firmenschulung`, `04-content.md`) is an
  enquiry form that mails VIAK; nothing is stored ([[TrainingController]],
  #24). What happens after the enquiry is done by hand, outside the app.
- **Invoices are raised per confirmed event, per booking** (`03-invoices.md`),
  and a licence order raises its own. There is no invoice that is neither, and
  no way to invoice a flat amount to a company.
- **The dashboard's licence order** already takes a free line, title and price
  (#36), which is the shape a Firmenkurs invoice needs too.

## Proposed shape

A Firmenkurs is **an `Event` that is not public**, belonging to a customer
(the company's contact, a `User` with its invoice address), with an agreed
price:

- **The event**: course (from the catalogue, or a free title for a tailored
  programme), dates, location or *bei der Firma*, experts, as any event. Never
  on the site, never bookable, never in *Nächste Kurstermine*; a flag on
  `events` or its own `company_id`, to decide when building.
- **The participants**: a number on the event, nothing more (#60). No
  accounts, no documents, no messages, no participation confirmations.
- **The invoice**: one per Firmenkurs (#62), of free lines VIAK types
  (*Firmenschulung Rhino, 2 Tage, pauschal*, *Spesen*), so per day, per
  participant and flat are all just lines and no pricing rule is built (#61), VAT as for a course (exempt, `viak-vat-rules`) or 8.1% on
  anything else, raised when VIAK presses *Rechnung stellen*, not on
  confirmation. Numbered, posted to Run My Accounts and cancelled exactly as
  every other invoice ([[CancelInvoice]], mocked outside production), with
  the PDF and QR bill as now.
- **The dashboard**: a *Firmenkurse* list (upcoming, past), a form for the
  event, the invoice beside it with its status.

## The building answers — 2026-10-26 (Marcel)

Asked after 60–63, since they decide what a Firmenkurs is made of:

- **No experts on it.** So **not an `Event`**: a record of its own
  (`company_courses`), which nothing public, no expert portal, no reminder,
  no search and no MCP tool ever sees. The *Proposed shape* above, an event
  that is not public, would have had to be left out of every one of those.
- **A course from the catalogue, or a free title** for a tailored programme.
- **The company is an address, not an account.** No `User`, no login, so the
  invoice is not in anybody's profile and **cannot be paid by card** (the card
  page is for the signed-in owner of the invoice): QR bill only.
- **Each Firmenkurs is a one-off** (Marcel, 2026-10-26, reversing his first
  answer of *one record per company*): the company's address is typed on
  the Firmenkurs, no company list is kept. Run My Accounts files each
  Firmenkurs as its own customer (`VIAK_F{Firmenkurs id}`, beside
  `VIAK_{user id}`), so a company that comes back is retyped and is a new
  customer in the books. Built first with a `companies` table, a picker and
  a lightbox; taken out the same day.
- **An e-mail on the Firmenkurs**: *Rechnung stellen* sends the PDF there.
- **The lines are typed on the Firmenkurs and frozen by *Rechnung stellen*.**
  Each is a text and a net amount, exempt like a course unless ticked
  *MWST* (8.1 %, `viak-vat-rules`). After that the Firmenkurs is locked;
  a change means *Rechnung stornieren* on the Firmenkurs (nothing in the
  dashboard cancels an invoice on its own today), which posts the storno to
  the books and unlocks it for a new one.

## Questions — answered 2026-10-26 (Marcel)

- ~~**60.** Do the participants exist in the app?~~ **No: a number.** The
  event and one invoice. Names or confirmations can be added later if asked.
- ~~**61.** Per day, per participant or flat?~~ **Free lines**, typed per
  invoice; every pricing model fits in them.
- ~~**62.** One invoice or partial invoices?~~ **One.** Partial invoices if
  ever asked for.
- ~~**63.** Store the enquiry and turn it into a Firmenkurs?~~ **No, it stays
  a mail** (#24 unchanged). VIAK creates the Firmenkurs by hand.

## As built — 2026-10-26

- **Tables** (`2026_10_26_000003_create_company_courses`): `company_courses`
  (the company's invoice address, the fields of [[UserAddress]]; catalogue
  course or `title`, `location`, `participants`, `email`, `remarks`,
  `invoice_id`); `company_course_dates`; `company_course_lines`
  (`description`, `net`, `taxed`). `invoices.user_id` is nullable now and
  `invoices.company_course_id` sits beside it; one of the two is set.
- **Invoicing** goes through [[IssueInvoice]] like every other invoice: it
  takes `User|CompanyCourse`. A line is `InvoiceItemType::Course` (exempt) or,
  ticked *MWST*, the new `InvoiceItemType::Other` (8.1 %). Run My Accounts
  files a Firmenkurs as `VIAK_F{id}` ([[RunMyAccounts::customerNumber]]).
- **The PDF** has no [[UserDocument]] (no owner): [[RenderInvoice::forCompany]]
  writes it to `documents/company-courses/{uuid}/` and the dashboard serves
  it from `/dokumente/firmenrechnung/{invoice}`, admins only. On the PDF the
  company's address stands alone, without the *Kursteilnehmer:in* block.
- **The mail** [[CompanyCourseInvoice]] (*Sie*, QR bill only, the PDF
  attached) goes to the Firmenkurs's e-mail.
- **Withdrawing** ([[WithdrawCompanyCourseInvoice]]) cancels with the new
  reason `withdrawn` and reverses in the books ([[CancelInvoice]]). Only an
  open or overdue invoice; a paid one is a refund, done by hand.
- **The dashboard**: *Firmenkurse* under *Backoffice*, after *Bestellungen*. The list has
  *Bevorstehende* (open) and *Vergangene* (shut) Firmenkurse with the invoice
  as a badge. The form is [[CompanyCourseSchema]]: the company's address,
  the days as a Veranstaltung's, the lines as a repeater; under it *Rechnung stellen* (waits while the form has unsaved
  changes, since it bills what is saved), then the invoice box and
  *Rechnung stornieren*. An invoiced Firmenkurs is locked as a past
  Veranstaltung is. The invoice also shows under *Rechnungen*, by the
  company's name, and is found by it.
- **Nothing public sees it**: its own tables, so no list of Veranstaltungen,
  no search, no MCP tool, no reminder, no expert portal.
- Tests: `tests/Feature/Admin/CompanyCourseTest.php`. Walked through in a
  browser the same day (with the company table, before it went): a
  Firmenkurs with two lines saved, invoiced (CHF 2'400 exempt, CHF 150 + 12.15 VAT), the PDF
  checked, withdrawn, unlocked.
