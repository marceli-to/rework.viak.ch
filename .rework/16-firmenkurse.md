# 16 — Firmenkurse (not yet built)

A dashboard module to set up a course for one company and invoice it. Asked
for in the meeting of 2026-10-26.

## Status

**Scoped 2026-10-26**, nothing built. **60–63 answered the same day by
Marcel, all four the small way**: the participants are a number, the invoice
is free lines, one invoice per Firmenkurs, the enquiry stays a mail. So this
is the small module: an event that is not public, and one invoice beside it.

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

## Questions — answered 2026-10-26 (Marcel)

- ~~**60.** Do the participants exist in the app?~~ **No: a number.** The
  event and one invoice. Names or confirmations can be added later if asked.
- ~~**61.** Per day, per participant or flat?~~ **Free lines**, typed per
  invoice; every pricing model fits in them.
- ~~**62.** One invoice or partial invoices?~~ **One.** Partial invoices if
  ever asked for.
- ~~**63.** Store the enquiry and turn it into a Firmenkurs?~~ **No, it stays
  a mail** (#24 unchanged). VIAK creates the Firmenkurs by hand.
