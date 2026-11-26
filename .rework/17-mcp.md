# 17 — An MCP server (plumbing built 2026-10-26)

The client wants an MCP server, so that an AI assistant can read (and perhaps
act on) VIAK's data. Raised in the meeting of 2026-10-26; **what it should do
is still to come from the client** (#64).

## Status

**Plumbing built 2026-10-26** (*As built*, below): the server, the tokens, the
call log and four read-only tools. What it should do beyond that waits on #64.
**2026-11-25:** to go company wide in claude.ai through OAuth, admins only.
**OAuth built 2026-11-26** (*OAuth as built*, below): the claude.ai connector
works against the same four tools. #65 is answered by it: claude.ai, VIAK's
admins.

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

## Company wide in claude.ai — meeting of 2026-11-25 (recorded, not built)

Christoph would set the server up **company wide** with Project Room's latest
implementation (`../projects.nightnurse.ch/.rework/15-mcp.md`, *OAuth for
claude.ai connectors*, 2026-11-06). That is a claude.ai organisation
connector: an org admin adds `https://visualisierungs-akademie.ch/mcp` once
under *Admin settings → Connectors* (no client id or secret, dynamic
registration); everyone in that claude.ai organisation then sees it, clicks
*Connect* once and signs in with their own VIAK account. Who *sees* it is
Christoph's org; who can *use* it is decided here, at sign-in.

claude.ai speaks only OAuth, so this needs Project Room's step copied:
Passport next to Sanctum (`auth:sanctum,api` on `/mcp`), `Mcp::oauthRoutes()`,
redirect URIs limited to claude.ai / claude.com (and ChatGPT's, if wanted), a
consent screen after the normal login, *Verbundene Apps* on *Mein Profil*, and
`php artisan passport:keys` once on the server (into `Deploy.md` when built).
The personal bearer tokens for Claude Code stay. About a day.

**Proposed (Marcel, 2026-11-25): admins only, no 2FA.** VIAK has no 2FA and
none is planned; the gate is an active admin account, the same as the
dashboard's. **What the server should be capable of is still to be defined
with VIAK (#64)**; asked on *Projektstand* for everyday examples from the
office. OAuth was built ahead of that (Marcel, 2026-11-26), so the connector
can be set up and tried with the four tools while the answer is out.

## OAuth as built — 2026-11-26

Project Room's step (`../projects.nightnurse.ch/.rework/15-mcp.md`, commit
0411089) copied, with VIAK's gate instead of Nightnurse's:

- **`laravel/passport` ^13.10** next to Sanctum. Guard `api` (driver
  `passport`) in `config/auth.php`; `/mcp` runs `auth:sanctum,api`, and no
  other route names `api`, so an OAuth token opens `/mcp` and nothing else
  (tested with a real token against `/api/admin/courses` and `/api/profile`).
  `User` keeps Sanctum's `HasApiTokens`; Passport's guard only needs
  `withAccessToken()`, which that trait has.
- **`Mcp::oauthRoutes()`** in `routes/ai.php`:
  `/.well-known/oauth-protected-resource[/mcp]`,
  `/.well-known/oauth-authorization-server`, `POST /oauth/register` (dynamic
  client registration). A 401 on `/mcp` carries `resource_metadata`.
  `config/mcp.php` accepts **claude.ai and claude.com redirect addresses
  only**; ChatGPT's two would be one line each if VIAK ever wants it.
- Authorization code + PKCE (S256), public clients. **Access tokens 1 hour,
  refresh tokens 90 days** ([[AppServiceProvider]]); device-code grant off and
  its migration not published.
- **The gate** ([[AdmitOAuthToMcp]], [[McpAccess::admitsOAuth]]): the
  `mcp:use` scope, an admin, not deactivated. The same rule as the personal
  tokens, so losing the role or the account ends both at once. **No 2FA**
  (Marcel, 2026-11-25).
- **Signing in**: `/oauth/authorize` is on the `web` guard, so a signed-out
  browser goes through the site's normal login and comes back via
  `intended()` ([[LoginResponse]]). Then **the consent screen**
  `site/auth/oauth-authorize.blade.php`, on the login page's layout: what the
  client can read and that it changes nothing, *Zugriff erlauben*,
  *Abbrechen*. Anyone but an active admin sees *Nicht verfügbar* and only
  *Abbrechen*.
- **The call log** writes `token: oauth` and the client's id for an OAuth
  call ([[LogMcpCall]]).
- **Mein Profil → *Verbundene Apps*** ([[ConnectedApps]],
  [[ConnectedAppController]], `GET|DELETE /api/admin/profile/connected-apps`):
  the clients the admin still holds a live token for (unexpired access token,
  or unexpired refresh token), *verbunden* and *zuletzt* (the newest access
  token, renewed hourly while in use), and *Trennen*, which revokes access
  and refresh tokens; reconnecting asks for consent again. *MCP-Zugang*
  above it now says the personal tokens are for Claude Code in a terminal,
  and Claude in the browser needs none.
- Personal tokens for Claude Code keep working unchanged.
- Tests: `tests/Feature/Mcp/McpOAuthTest.php` (metadata and the 401 header,
  registration allowlist, the full code/PKCE/token/refresh round trip with a
  tool call, the gate both ways, the consent screen, *Verbundene Apps*). They
  generate Passport keys if missing.
- **Deploy**: `passport:keys` once per server, kept across deploys
  (`Deploy.md`, *Every deploy* and *After the pull of 2026-11-26*).

**Connecting claude.ai** (Christoph, as admin of VIAK's claude.ai
organisation): *Admin settings → Connectors → Add custom connector*, address
`https://<host>/mcp`, no client id or secret (dynamic registration). Each
admin then clicks *Connect* once, signs in with their VIAK account and allows
access. On staging that is `https://staging.viak.ch/mcp` (staging's data, for
trying it); production's address after the cutover.
