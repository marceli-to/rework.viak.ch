# Deploy

The runbook: what every deploy runs, and what the cutover adds once. Started
2026-10-21 with the attachments step. Every entry points to the chunk doc
that holds the reasoning. Add a step here when you build something a
server has to do. A step that is only written in a chunk doc gets forgotten.

## Every deploy

In this order, from the release directory:

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
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

Still open: which PHP production runs (#11).

## The cutover (once)

1. **A fresh production dump and storage snapshot**, taken at the same
   moment (the local copy is from 2026-09-11). Point `LEGACY_DB_*` and
   `LEGACY_STORAGE_PATH` at them.
2. The ports, in dependency order: `port:courses`, `port:users`,
   `port:invoices`, `port:documents`, `port:media`, `port:messages`. Each
   has `--dry-run`. Each one reports findings, and every finding is a decision.
   `port:messages` writes attachments to the private disk itself.
3. **Step 4 of *Every deploy* again** (`media:move-attachments`), in case
   the storage was copied rather than ported.
4. The data checks: rentals against laptop counts, invoice 000419 at
   CHF −149.00, every pivot and `mediable_type` the ports fill
   (`Open-Questions.md`, *pending actions*).
5. The redirect map (`09-public-site.md`), and the new GTM container
   version published together with the DNS switch (`11-tracking.md`, step 5).
