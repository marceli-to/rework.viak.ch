# 17 — An MCP server (not yet built)

The client wants an MCP server, so that an AI assistant can read (and perhaps
act on) VIAK's data. Raised in the meeting of 2026-10-26; **what it should do
is still to come from the client** (#64).

## Status

**Scoped 2026-10-26.** The plumbing can be set up before the tools are known;
the tools wait on #64 and #65.

## Proposed shape

- **`laravel/mcp`**, the official package: a server class, tools as classes,
  served over HTTP from the app at `/mcp`. Checked against Laravel 13 when
  installed.
- **Auth: a Sanctum token per person**, issued by an admin from the dashboard
  and revocable there, only to users with the admin role. Every tool call is
  logged with the token's owner. No anonymous access, no token for customers
  or experts.
- **Read-only to start**, tools that answer the office's everyday questions:
  - Kurse and their upcoming Veranstaltungen, with seats taken and free;
  - the bookings of one Veranstaltung;
  - open and overdue invoices;
  - licence orders waiting for dispatch.
- **Writes only when asked for, one at a time**, each going through the same
  actions the dashboard uses (so the same rules and mails apply), never
  straight to the database. Nothing that posts to Run My Accounts runs outside
  production, as everywhere else.
- **Deploy**: a token's scope and the endpoint go into `Deploy.md` when built.

## What to keep in mind

Every tool exposes production customer data (names, addresses, invoices) to
whatever AI client VIAK connects, and through it to that provider. Which client
that is, and on whose account, is the client's call, but it should be made
knowingly (#65).

## Questions (in `Open-Questions.md`)

- **64.** What should it do? Which questions should the assistant answer, and
  is anything meant to be changed through it?
- **65.** Which AI client connects to it (Claude, ChatGPT, another), and who at
  VIAK uses it?
