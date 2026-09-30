# 12 — Customers, not students (not yet built)

The next version sells software licences as well as courses. Some people will
only book courses, some will do both, and some will only ever order a licence.
*Student* stops describing who has an account.

## Status

**Scoped 2026-09-30** (Marcel). Two parts:

- **The student role goes; every account is a customer** — the bigger block,
  planned below and not started.
- **Switching between the areas one person may use** — legacy's role picker,
  missing here. Built first, because it needs nothing else and the first part
  only changes its labels.

## Why the role goes rather than being renamed

Admin and Expert are *capabilities*: running the backoffice, teaching and
appearing on the Experten page ([[Role]]). *Student* never was one. It meant
"has an account and books things", which every account does, and
`05-licences.md` already recorded that *has an account* should not be made into
a role. Renaming it to *Customer* would keep a role that says nothing.

So:

- **Every account is a customer.** No role row for it.
- **The portal shows what the person has**: booked and past courses, licences,
  documents, the Merkliste. A section with nothing in it is not shown, or shows
  the empty-list line, as today.
- **Admin and Expert stay roles**, unchanged.

## What it touches

Measured 2026-09-30: `student` appears in 188 files. The shape of it:

| Where | What changes |
|---|---|
| `Role::Student` (9 uses) | Goes. The guards (`role:student` on the portal and the checkout) become `auth` + `verified` |
| `SiteUrl::profileFor()` | Anyone signed in has the customer portal |
| `RegisterUser`, `StudentSchema`, `Stage` | Stop writing the role |
| Routes and URLs | `/de/student/profil` → a customer URL, **with a 301 from the old one**: legacy's mails and people's bookmarks point at it |
| Dashboard | *Studenten* → the customers' section (list, page, form), `StudentController`, `StudentPageController`, the Vue views and routes |
| Texts | *Student*, *Studenten* in UI copy, mails and PDFs, as the client names it |
| Tests | `->student()` in 42 places, and the factory state |
| Port | Stops copying `role_user` rows for legacy's role 3 |

**No production migration.** The rework is not live: at cutover the port reads
legacy afresh, so the change is "the port no longer writes the student role",
not a migration against live data.

## Decisions it needs

1. **The word.** *Kunde* / *Kunden* is the obvious one; it is the client's to
   confirm (#42).
2. **The URL** of the portal, e.g. `/de/konto`, with the 301 from
   `/de/student/profil` and its sub-pages.

## Staging

Too big for one commit, and each stage leaves the suite green:

1. **Stop depending on the role**: the guards, `profileFor()`, and
   registration read "signed in", not "holds Student". Nothing visible changes.
2. **The words and the URL**: UI texts, mails, the portal's URL and its 301s.
3. **The dashboard section**: *Studenten* to the customers' section, back end
   and Vue.
4. **The role itself**: `Role::Student`, the factory state, the port, the
   `role_user` rows.

## Switching areas — the role picker, built 2026-09-30

Legacy asks an account with several roles which one to use after login and
keeps the answer in the session (`09-public-site.md`, item 6). Five accounts
hold more than one role today (four all three, one admin + student).

The rework does not copy the extra screen: the three areas already have their
own URLs, so there is nothing to remember. **The header's profile icon opens a
menu of the areas the person may use** (*Mein Konto*, *Expertenbereich*,
*Dashboard*), and an account with one area keeps the plain link to it.
