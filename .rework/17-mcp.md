# 17 — An MCP server (plumbing built 2026-10-26)

The client wants an MCP server, so that an AI assistant can read (and perhaps
act on) VIAK's data. Raised in the meeting of 2026-10-26; **what it should do
is still to come from the client** (#64).

## Status

**Plumbing built 2026-10-26** (*As built*, below): the server, the tokens, the
call log and four read-only tools. What it should do beyond that waits on #64
and #65.

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

## As built — 2026-10-26

- **`laravel/mcp` ^1.1** (checked against Laravel 13.31), the server
  [[ViakServer]] at `POST /mcp` (`routes/ai.php`), named `mcp`. Its
  instructions explain Kurs, Veranstaltung, Buchung, Rechnung and
  Lizenzbestellung to the assistant.
- **Four read-only tools**, each annotated `readOnlyHint`:
  - `upcoming-events`: Veranstaltungen ahead (default 90 days, a course-title
    filter), seats taken against min and max, state, fee; cancelled left out,
    unpublished included and marked;
  - `event-bookings`: one Veranstaltung by its number (*05-161126*) or uuid,
    who is booked (name, company, e-mail, rental, attendance), cancelled ones
    counted;
  - `open-invoices`: open and overdue, oldest due first, days overdue, the
    total owed; `overdue_only`;
  - `outstanding-licence-orders`: the dispatch worklist, paid or not (#2).
- **Tokens on *Mein Profil***, a shut *MCP-Zugang* collapsible
  ([[McpTokens]], [[McpTokenController]]): an admin creates their own, named
  per AI client, sees it once with the server's address, and revokes it;
  each row says when it was last used.
  Under the new token, *Befehl für Claude Code kopieren* puts the whole
  `claude mcp add --scope user --transport http viak <address> --header
  "Authorization: Bearer <token>"` on the clipboard, to paste into a
  terminal (Marcel, 2026-10-26).
- **A token opens `/mcp` and nothing else** ([[McpAccess]]). Sanctum takes
  any bearer token on every `auth:sanctum` route, the dashboard's writes
  included, so without this an MCP token would have been a full admin API
  key. Sanctum now asks [[McpAccess]] first: the `mcp` route, the `mcp`
  ability, an admin not deactivated. A token stops working the moment its
  owner loses the role; the dashboard's session is untouched (it never uses
  a token). Tested both ways.
- **Every call logged** to `storage/logs/mcp.log` ([[LogMcpCall]]): owner,
  token name, method, tool, arguments, status. Not the answer, which is
  customer data and would be a second copy to protect.
- **60 calls a minute per account** (`throttle:60,1`, keyed on the user).
- Tests: `tests/Feature/Mcp/McpServerTest.php`. Walked through in a browser
  the same day: a token made on the profile, then a real `tools/call` over
  HTTPS against the dev data.

**Connecting a client** (for whoever at VIAK, once #65 is answered): the
address is `https://visualisierungs-akademie.ch/mcp`, HTTP transport, the
token as `Authorization: Bearer …`. For Claude Code, for instance:
`claude mcp add --transport http viak https://…/mcp --header "Authorization: Bearer <token>"`.
Clients that only speak OAuth (claude.ai's connectors) cannot use a bearer
token; `laravel/mcp` can serve OAuth through Passport if that is the client
VIAK picks, which is a second step, not a rebuild.
