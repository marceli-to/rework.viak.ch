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
- **Cache headers for `/build/assets/`** come from `public/.htaccess`
  (the plan allows no nginx directives). If a server serves static files from
  nginx directly, Apache never sees them: set a year's `Cache-Control` there.
- **`APP_ENV=staging`**: Stripe, Run My Accounts and Mailchimp are fakes
  whatever the .env holds, GTM is off, robots say `Disallow: /`, and every mail
  goes to `MAIL_CATCH_ALL` (Marcel). Mailgun SMTP as legacy's staging had it.
- **Feedback widget**: Roxy (`roxy.kimiyu.ch`) loads on every page of the site
  and the dashboard on staging only (`components/layout/feedback.blade.php`).
  Nothing to remove at cutover.
- **Data**: the local database dumped 2026-10-30, Telescope, `jobs` and
  `failed_jobs` emptied (the first dump carried 35 queued dev jobs, and the
  first cron minute mailed them all to the catch-all). `storage/app/public`
  only; never `documents` or `attachments`, which hold real names.
- **Logins for the client**: the dev accounts (`Test-Users.md`), which came
  with the dump.

Update: `git pull && composer install --no-dev -o && php artisan migrate --force
&& php artisan search:rebuild && php artisan config:cache && php artisan
route:cache && php artisan view:cache`.

**Once, after the pull that brings the website texts (2026-11-09):**
`php artisan catalogue:categories --sync` (`--dry-run` first). The client's
texts changed the tags of eight Angebote; `--sync` lets the file win over what
staging holds and gives courses their software's again. Not needed when the
next dump replaces the database (it already carries them), and **not at the
cutover**, where the plain run assigns from nothing.

**Then `php artisan catalogue:texts`** (`--dry-run` first, 2026-11-10): the
client's texts for 22 more Angebot pages and their *Was ist …?* pages
(`database/data/texts.json`). Fills empty fields only, so it is safe to rerun
and keeps what VIAK has typed; `--force` makes the file win.

**After the pull of 2026-11-12:** `php artisan migrate --force` (Maxwell
Render unpublished, the client's ask). The 3d-software.ch redirects need
nothing on staging: its domain does not point there.

The same update's second migration (the software list of 2026-11-12) puts in
Rhino 9, the Rhino 9 Upgrade promo with its Streichpreis, Drakon's demo,
hides EDU and deletes Maxwell. Nothing else to run: `search:rebuild` in the
update line indexes the new licences.

**Then, once:** `php artisan catalogue:offers`, `php artisan
catalogue:categories`, `php artisan catalogue:texts` (each `--dry-run`
first). The first adds ComfyUI, D5 Render and Figma Weave and five courses,
all unpublished, and takes the DaVinci course (and with it the DaVinci
Angebot) off the site; the second gives the three Angebote their
categories; the third their texts and Photoshop's. Photoshop's categories
changed in `categories.json` too, but only `--sync` applies that to a
database that has some, and it overwrites VIAK's choices: skip unless
Oliver asks.

The third migration of that update makes the plugins Angebote of their own
(Bongo, VisualARQ, Lands Design, Karamba3D, MatrixGold, Rhino.io; Drakon
takes its product), nothing to run for it. The `catalogue:texts` above then
also fills Bongo, Karamba3D and VisualARQ.

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
   **Then `php artisan catalogue:offers`** (`--dry-run` first, 2026-11-12):
   the Angebote and courses the client is still working on, unpublished
   (`database/data/offers.json`), and *DaVinci Resolve Einführungskurs*
   unpublished, which the port brings back as legacy has it. Before
   `catalogue:categories`, which gives the new Angebote their categories.
   **Then `php artisan catalogue:categories`** (`--dry-run` first):
   `port:courses` brings legacy's three categories back, and this puts the
   client's five in their place with a first assignment to software, licence
   products and courses (`database/data/categories.json`, `14-angebote.md`).
   It lists what is on the site with no category; VIAK sets those in the
   dashboard. Rerunnable: what has a category is left alone.
   **Then `php artisan catalogue:texts`** (`--dry-run` first): the client's
   website texts onto the Angebot pages (`database/data/texts.json`). Empty
   fields only; it reports what it kept and any Angebot it did not find.
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
   **And `php artisan port:check-shop-urls --base=https://<the new server>`**
   (2026-11-12): the old shop's 202 paths (`database/data/shop-redirects.json`,
   from the client's page tree), each resolved and its target asked for. It
   fails when a target is not a page; it lists the paths whose product is
   missing and that land on their Angebot instead ([[CheckShopUrls]]).
   **3d-software.ch switches with it.** Today its vhost serves the old
   Shopware from its own document root. Production starts on the same
   Metanet host (Marcel, 2026-11-12), so: in Plesk, set the document root of
   `3d-software.ch` (and `www.`) to the production app's `public/`, keeping
   the certificate. The app then answers those hosts, and `routes/web.php`
   sends every path on them to its page on visualisierungs-akademie.ch, one
   301 ([[ShopRedirects]]). Staging is not touched: it only lives in that
   vhost's folder, with a document root of its own. Check after:
   `curl -sI https://www.3d-software.ch/public/Bongo-2/RHN-1121` must answer
   301 to `https://visualisierungs-akademie.ch/de/angebote/software/bongo-2`.
   **When visualisierungs-akademie.ch moves to cyon** (planned, no date):
   either 3d-software.ch's DNS goes with it and the domain becomes an alias
   there (nothing else changes), or it stays on Metanet and its document
   root gets an `.htaccess` with the map's 202 rules written out, each a 301
   to the new host. That file is frozen at writing: a product added later
   only catches its old URL once the file is written again. Not built; a
   command that writes it from `ShopRedirects` is a small job.
7. The new GTM container version, published together with the DNS switch
   (`11-tracking.md`, step 5). If the `purchase` event is built by then
   (`11-tracking.md`, *Step 2 worked out, 2026-11-02*): take the *form
   submit* trigger off the old Ads conversion and set that action to
   secondary. If it is not built, leave the trigger: every form, a booking
   among them, keeps counting as CHF 100.
   **After the switch**, Tag Assistant on the live domain: accept the banner,
   and `ad_storage` and `analytics_storage` must read *Gewährt* under
   *Aktueller Status* on the next *Container geladen* (`11-tracking.md`, *Step
   2 worked out, 2026-11-02*; on staging only `window.dataLayer` can show it).
8. **Before the first Firmenkurs is invoiced in production**: confirm with
   VIAK that Run My Accounts takes the Firmenkurs customer numbers
   (`VIAK_F{Firmenkurs id}`, beside the accounts' `VIAK_{user id}`) the way it
   takes a new student's, and that the `F` suits their bookkeeping
   (`16-firmenkurse.md`).
