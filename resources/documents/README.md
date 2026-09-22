# Document assets

The page furniture for the generated PDFs — the invoice, the participation
confirmation, the participant list. See `.rework/03-invoices.md` under *The
invoice PDF and its QR bill*.

| | |
|---|---|
| `letterhead.svg` | the header block, 168 × 16mm — legacy's `public/assets/img/pdf-header.svg`, unchanged |
| `fonts/EffraRegular.ttf`, `fonts/EffraBold.ttf` | legacy's `public/assets/fonts/`, unchanged |

## Why they are files here and not URLs

Legacy's templates load all three over HTTP from the application's own public
URL — `url('/') . '/assets/fonts/EffraRegular.ttf'` and `asset('assets/img/…')`.
So rendering a PDF makes three HTTP requests to itself, and on a queue worker
with no outbound route, or with `APP_URL` pointing at the wrong host, dompdf
falls back to Helvetica and drops the logo. The invoice still renders; it simply
looks like a different company's, and nothing errors.

Read off the filesystem, a missing file is an exception at the moment it
matters. `config/documents.php` holds the paths and `isRemoteEnabled` is off.

## Effra

A commercial typeface (Dalton Maag), licensed to VIAK and used on the live site
since 2022 — the same two files, from the same place, for the same purpose.
Carrying them across is a port rather than a new decision, but it **is** a font
embedded in a distributed PDF, so it belongs in whatever licence review the
cutover gets rather than being assumed.

The public site loads Effra from Adobe Fonts instead, which a PDF renderer
cannot use; that is why these two files exist separately from
`resources/css/partials/fonts.css`.

## Adding one

dompdf compiles a TTF into its own format on first use and caches it in
`storage/app/dompdf`, which is disposable and gitignored. Delete it if a font
file changes and the old one seems to persist.
