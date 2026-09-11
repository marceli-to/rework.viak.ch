# 00 — Foundation

Scaffold, tooling, environment and the conventions every later chunk depends on.
Nothing product-specific lives here.

## Status

Built. `rework.viak.ch.test` serves, `php artisan test` green, `npm run build` clean,
`composer format:check` clean.

## Goals

- Clean checkout runs with `composer setup` + `composer dev`.
- Laravel 13 + Vue 3 + Tailwind 4 + Vite, on the conventions below.
- MySQL 8 rather than the legacy 5.7.
- Queue on the database driver, cron-driven — replacing the hand-rolled mail queue.
- The legacy app keeps running untouched alongside this one.

## Stack & versions

| | Legacy (viak.ch) | Rework |
|---|---|---|
| PHP | 8.2 | 8.3 (platform-pinned) |
| Laravel | 11 | 13.31 |
| MySQL | 5.7 (:3306) | 8.0.40 (:3307) |
| Frontend | Vue 2.7, Vuex 3, vue-router 3 | Vue 3.5, Pinia 4, vue-router 5 |
| Build | Laravel Mix 5 (webpack) | Vite 8 + laravel-vite-plugin 3 |
| Styling | SCSS, 106 files / 6.8k LOC | Tailwind 4 |
| Tests | PHPUnit/Pest, 24 files | Pest 4 |
| Auth | laravel/ui | Fortify |

Installed: `laravel/fortify`, `spatie/laravel-translatable`, `spatie/laravel-sluggable`,
`spatie/image`. Everything else gets added by the chunk that needs it — dompdf,
swiss-qr-bill, stripe and the excel exporter land with chunks 03/04.

### Why PHP is pinned to 8.3

`composer config platform.php 8.3.31`. The CLI here is 8.4 but Herd serves the site
with 8.3, and without the pin Composer locks a `>= 8.4.1` platform requirement that
the webserver then fails on. Pinning also makes the lockfile reproducible against a
declared target rather than against whatever CLI happens to be installed.

**Open:** what PHP does production actually run? If it is 8.4, raise the pin. This is
the kind of thing that only bites at deploy time.

## Databases

Three, all on MySQL 8 (`127.0.0.1:3307`):

| Database | Purpose |
|---|---|
| `viak_rework` | the new app |
| `viak_rework_test` | test runs |
| `viak_legacy` | copy of live data, imported from the 5.7 `viakch` database |

`viak_legacy` is what chunk 01 migrates *from* and what the port is verified against.
Its copy is current to **2025-09-18** — roughly a year stale. Fine for building;
a fresh dump is required before the cutover rehearsal.

### Live data volumes (measured, not estimated)

| | rows |
|---|---:|
| users | 468 |
| bookings | 543 |
| invoices | 448 (CHF 313k lifetime; 6 open, 9 overdue) |
| events | 240 |
| courses | 35 |
| rental invoices | 24 |
| `jobs` (legacy mail queue, never pruned) | 4,523 |

This is small, and it changes the risk profile of chunk 01 substantially: 448 invoices
can be migrated and then reconciled **row by row**, not sampled. See `01-schema.md`.

## Code style conventions

Carried over from `rework.projects.nightnurse.ch`, which these apply to unchanged:

- **Slim controllers.** A method type-hints a FormRequest, calls an Action, returns a
  Resource. No business logic.
- **Action classes** (`app/Actions/`) for write operations. One class per business
  operation, single `execute()`. Verbs: `CreateEvent`, `ConfirmEvent`.
- **FormRequest** for every write endpoint, and for index endpoints that take filters.
- **Resource** classes for every API response. Models are never returned directly.
- **Policies** for authorisation.
- **Enums** for every fixed set. The legacy app has none — states live in
  `spatie/laravel-model-flags` rows and loose strings.
- **No fat models.** Relations, casts, scopes, trivial accessors. Nothing else.
  (Legacy `Event` is 448 LOC with 14 `$appends`; that is the anti-pattern.)
- `declare(strict_types=1);` at the top of every PHP file.
- Tabs, per `.editorconfig`. PHP-CS-Fixer rather than Pint, which hardcodes spaces.

## Directory shape

```
app/
  Actions/            # one class per business operation
  Enums/
  Http/
    Controllers/Api/  # SPA + public API
    Requests/
    Resources/
  Models/
  Policies/
  Jobs/
  Notifications/
  Support/

resources/
  css/                # app.css + partials/{fonts,colors}.css
  js/
    app/              # the dashboard SPA (Vue 3 + Pinia + router)
    site/             # public-site Vue islands (basket, filter, checkout)
  views/
    components/layout/{app,site}.blade.php
    site/             # public marketing pages, Blade + Tailwind
```

### One SPA, not three

Legacy ships three separate Mix bundles — `dashboard` (72 components), `expert` (5),
`student` (7) — over 62 shared components. These collapse into one Vue 3 SPA under
`resources/js/app/` with role-gated routes. The public site stays Blade with Vue
islands, which is effectively what it already is.

## Design tokens

Brand teal is unchanged from the legacy SCSS: `#46baba`. The mockup's derived tones
(`--color-teal-dark: #2d8f8f`, `--color-line`, `--color-paper`) are added in
`resources/css/partials/colors.css`.

**Open:** the mockup moves type from **Effra** (Adobe Fonts) to **Poppins**. Currently
scaffolded as Poppins. Needs brand sign-off — it is a visible change and the brief says
the design should only shift slightly.

## Commands

| | |
|---|---|
| `composer setup` | install, key, migrate, build |
| `composer dev` | serve + queue listener + pail + vite |
| `composer test` | Pest |
| `composer format` / `format:check` | PHP-CS-Fixer |

## Open questions carried forward

1. Production PHP version (pin above).
2. Poppins vs Effra.
3. Fresh production dump before cutover rehearsal.
4. VAT treatment for software licences — blocks chunk 03. Needs the client's bookkeeper.
5. Licence fulfilment: manual dispatch or reseller API? Blocks chunk 05 scoping.

## Working data (updated 2026-09-11)

`viak_legacy` now holds the **2026-09-11 production dump**, scrubbed in place by
`php artisan db:scrub` — 578 users, 710 bookings, 569 invoices, 360 events.

The scrub replaces customer identities and leaves everything reconciliation
compares. Verified by comparing untruncated MD5 fingerprints of the
non-identity columns against a pristine import: identical for invoices,
bookings, events and the non-PII user columns.

Two things that verification caught, worth remembering:

- `GROUP_CONCAT` silently truncates at 1024 bytes. A fingerprint built without
  raising `group_concat_max_len` compares the first few rows and nothing else,
  and will happily report two different databases as identical.
- `invoices.due_at` rewrites itself on any UPDATE — see `03-invoices.md`.
