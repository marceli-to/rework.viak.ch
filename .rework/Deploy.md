# Deploy

The runbook: what every deploy runs, and what the cutover adds once. Started
2026-10-21 with the attachments step. Every entry points to the chunk doc
that holds the reasoning. Add a step here when you build something a
server has to do. A step that is only written in a chunk doc gets forgotten.

## Every deploy

In this order, from the release directory:

1. `composer install --no-dev --optimize-autoloader`
2. ~~`npm ci && npm run build`~~ Nothing: **`public/build` is committed**
   (2026-10-30). The staging host has no Node, so the bundles are built locally
   and come with `git pull`. Build before committing any CSS or JS change.
3. `php artisan migrate --force`
4. **`php artisan media:move-attachments`**: course documents and message
   attachments onto the private `attachments` disk (`08-accounts.md`,
   [[MoveAttachments]]). Run it **after anything that copies storage onto the
   server**, such as a legacy snapshot, an rsync from another copy, or a restored
   backup. Files copied that way land in the public `uploads/`, where anyone
   can open them without logging in. The migration of 2026-10-21 runs it once
   per database. This step covers every copy after that. It moves only what is
   in the old place and not yet in the new one, so it is safe on every
   deploy. It names any row whose file is in neither place. Three of the dev
   seed rows always show up there, because they never had a file.
5. `php artisan storage:link` (first deploy only; the images are served
   from `storage/app/public`).
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
   Safe because `env()` is read only in `config/` (`00-foundation.md`).
7. **`php artisan search:rebuild`**: the site search's index from scratch
   (`15-search.md`, [[RebuildSearchIndex]]). Saving a record keeps its row
   current, but a deploy may change *what* is indexed, and a database
   import or the ports write rows without saving them through Eloquent. A
   second on a few hundred rows, safe at any time.

**The MCP server** (`17-mcp.md`) needs nothing at deploy, but two things on
the server: the web server must pass the `Authorization` header through
(`public/.htaccess` does it for Apache; nginx passes it by default), and
`storage/logs/mcp.log-*` is the record of every call, a year of files, so it
belongs in whatever backs up the server. `route:cache` picks up
`routes/ai.php`, which the package loads itself.

**Once per server:** one crontab line, `* * * * * php artisan schedule:run`.
The schedule starts the queue worker every minute (`00-foundation.md`,
*Queue and schedule*). Without it no mail goes out.

## Environment

Beyond Laravel's own keys:

| key | what | doc |
|---|---|---|
| `APP_ENV=production` | GTM loads only here; Run My Accounts posts only here | `11-tracking.md`, `03-invoices.md` |
| `SITE_CANONICAL_HOST` | defaults to `visualisierungs-akademie.ch`, which is right | `config/site.php` |
| `GTM_ID` | `GTM-M3L7WVP`, unless #29 says otherwise | `11-tracking.md` |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, `TURNSTILE_HOSTNAMES` | the local pair; hostnames = the live one only, also registered on the widget | `Open-Questions.md`, *pending actions* |
| `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | live key, and the webhook at `/stripe/webhook` registered with Stripe. Production without a key throws | `13-checkout.md` |
| `RMA_API_*`, `RMA_INVOICE_PREFIX` | the client's live accounting. **Production only** | `03-invoices.md` |
| `MAIL_*`, `MAIL_ADMIN_ADDRESS` | real SMTP; the office address. `MAIL_CATCH_ALL` is ignored in production, and on any other server it is where every mail goes | `10-mail.md` |
| `DOCUMENTS_QR_IBAN`, `INVOICE_VAT_RATE` | the invoice's QR bill and rate | `03-invoices.md` |
| `GOOGLEMAPS_APIKEY` | the Kontakt map; the client's key, restricted to the live site | `config/services.php` |
| `MAILCHIMP_APIKEY`, `MAILCHIMP_LIST_ID`, `MAILCHIMP_TAGS` | the client's newsletter list: legacy's three values (list `c19300bbec`, tag `VA Newsletter`). **Production only**; elsewhere signups are logged | `08-accounts.md` |

Still open: which PHP production runs (#11).

## Staging (since 2026-10-30)

`https://staging.viak.ch`, on the Plesk host that serves `3d-software.ch`:
app in `/httpdocs/staging.viak.ch` (absolute
`/home/httpd/vhosts/3d-software.ch/httpdocs/staging.viak.ch`), document root
its `public/`. Database `viak_rework_staging`; the old legacy staging's
`viak_staging` is left alone.

- **Jailed SSH shell**, `$HOME` is `/` (the vhost root). The default `php` is
  7.4; `~/.bashrc` puts `/opt/plesk/php/8.3/bin` first and `~/.bash_profile`
  sources it. Cron and the domain's PHP setting are separate: the crontab line
  calls `/opt/plesk/php/8.3/bin/php artisan schedule:run`, and the domain is on
  8.3 in Plesk.
- **No Node**: `public/build` is committed and arrives with `git pull`.
- **`APP_ENV=staging`**: Stripe, Run My Accounts and Mailchimp are fakes
  whatever the .env holds, GTM is off, robots say `Disallow: /`, and every mail
  goes to `MAIL_CATCH_ALL` (Marcel). Mailgun SMTP as legacy's staging had it.
- **Data**: the local database dumped 2026-10-30, Telescope, `jobs` and
  `failed_jobs` emptied (the first dump carried 35 queued dev jobs, and the
  first cron minute mailed them all to the catch-all). `storage/app/public`
  only; never `documents` or `attachments`, which hold real names.
- **Logins for the client**: the dev accounts (`Test-Users.md`), which came
  with the dump.

Update: `git pull && composer install --no-dev -o && php artisan migrate --force
&& php artisan search:rebuild && php artisan config:cache && php artisan
route:cache && php artisan view:cache`.

## The cutover (once)

1. **A fresh production dump and storage snapshot**, taken at the same
   moment (the local copy is from 2026-09-11). Point `LEGACY_DB_*` and
   `LEGACY_STORAGE_PATH` at them.
2. The ports, in dependency order: `port:courses`, `port:users`,
   `port:invoices`, `port:documents`, `port:media`, `port:messages`. Each
   has `--dry-run`. Each one reports findings, and every finding is a decision.
   `port:messages` writes attachments to the private disk itself.
   **Run them on an empty database, and not again once VIAK has edited
   anything.** `port:courses` clears and rebuilds `events`, `courses` and
   `software` with new ids: software pages, recommendations, testimonials and
   media attached since would point at nothing, and nothing would say so.
   **Then `php artisan catalogue:categories`** (`--dry-run` first):
   `port:courses` brings legacy's three categories back, and this puts the
   client's five in their place with a first assignment to software, licence
   products and courses (`database/data/categories.json`, `14-angebote.md`).
   It lists what is on the site with no category; VIAK sets those in the
   dashboard. Rerunnable: what has a category is left alone.
3. **Step 4 of *Every deploy* again** (`media:move-attachments`), in case
   the storage was copied rather than ported.
   And step 7 (`search:rebuild`): the ports insert without Eloquent's
   events, so until it runs the search finds nothing.
4. **`php artisan port:verify`**: every polymorphic row has an existing
   class and owner, and the relation that reads it is named. Fails on a row
   that points at nothing ([[VerifyPort]]).
5. **`php artisan port:check-rentals`**: upcoming Veranstaltungen with more
   laptops rented than the room has, and the bookings holding them. Settle
   each with VIAK before its rental is invoiced: raise the count, or drop a
   laptop ([[CheckRentals]]). On the 2026-09-11 copy: one, event `eac2d8b5…`
   on 15.12.2026.
   Then invoice 000419 at CHF −149.00 (`Open-Questions.md`, *pending
   actions*).
6. **`php artisan port:check-urls --base=https://<the new server>`**, before
   the DNS moves. Every URL legacy answers, followed on the rework. It fails
   when one does not land on a page, or needs more than one redirect to get
   there (the courses live under `/de/angebote` since 2026-10-29) ([[CheckLegacyUrls]], `Todo.md`, *The
   redirect map*). Under Herd, `/robots.txt` and `/favicon.ico` fail falsely.
7. The new GTM container version, published together with the DNS switch
   (`11-tracking.md`, step 5).
8. **Before the first Firmenkurs is invoiced in production**: confirm with
   VIAK that Run My Accounts takes the Firmenkurs customer numbers
   (`VIAK_F{Firmenkurs id}`, beside the accounts' `VIAK_{user id}`) the way it
   takes a new student's, and that the `F` suits their bookkeeping
   (`16-firmenkurse.md`).
