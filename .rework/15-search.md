# 15 — Full-text search (built 2026-10-26)

A search field in the header, *Kurs oder Software*, as the screenshots of
`14-angebote.md` show it. Asked for in the meeting of 2026-10-26.

## Status

**Backend built 2026-10-26** (*As built*, below): the index, the query, the
rebuild command and a JSON endpoint; then the header's field and the result
page, proposed and merged the same day (*The field and the result page*).
Once `14-angebote.md` lands, what a hit links to may change. Questions 56 and 57 in `Open-Questions.md`.

## What there is today

Nothing. Legacy has no site search (its `POST /api/course/search` route
points at a `FilterController::search` that does not exist), and the rework's
only search is the dashboard's list filters. The Kurse page filters by category, level, language
and software ([[CourseFilter]]), which is a filter, not a search.

## Why not Scout with an external engine

The catalogue is small: 41 courses (32 published), 39 licence products, 33
software, 6 Vorhaben (counted 2026-10-26). Meilisearch, Algolia or Typesense would add a service to run and
pay for, for a few hundred rows. MySQL does it.

MySQL FULLTEXT does not index the translatable columns well, though: `title`,
`short_description` and the rest are JSON (`spatie/laravel-translatable`), and
a FULLTEXT index over JSON would match keys and markup as well as words.

## Proposed shape

- **A `search_entries` table**: one row per searchable record and locale, with
  the record (`searchable_type`, `searchable_id`), `locale`, a plain-text
  `title` and `body` (HTML stripped), and a FULLTEXT index over both. The
  search runs `MATCH … AGAINST` in boolean mode, with a prefix wildcard on the
  last word so typing *rhin* finds Rhino, and weights a title hit over a body
  hit.
- **Kept current on save**: an observer on each searchable model rewrites its
  rows; an unpublished record has none. A `search:rebuild` command fills it
  from scratch, which goes into `Deploy.md` (after the port at cutover, and
  safe to rerun).
- **What is searched** (#56): Angebote (software), courses, licence products
  (hit links to the Angebot's *Lizenzen*), Vorhaben.
- **The header field** shows a short list of suggestions as you type (Alpine,
  a JSON endpoint, throttled); Enter opens `/de/suche?q=…` with the hits
  grouped by type. Kept out of search engines (`noindex`).
- **Short and odd queries**: MySQL's minimum token length (`innodb_ft_min_token_size`,
  3 by default) drops *3D* and *KI*. Either the server setting is lowered
  (production's MySQL, #11 territory), or queries under three characters fall
  back to `LIKE` on the title. The fallback needs nothing from the server.

## Questions (in `Open-Questions.md`)

- **56.** What does search cover: the catalogue only, or also experts, pages
  (Firmenschulung, Über uns) and the blog if there is one (#22)?
- **57.** Does the result page show prices and next dates, or titles and leads
  only?

## As built — 2026-10-26

- **`search_entries`** as proposed, plus `type`, `lead` and `url` so a
  result list needs no second query. FULLTEXT on `title` and on
  `title, body`.
- **What is in it** ([[SearchDocument]]), by the rule each page uses, so the
  search never finds a page that 404s:
  - courses, published: title; subtitle, summary, descriptions, SEO tags, and
    the titles of their software and categories;
  - software on the site (`Software::onSite()`, a listed licence);
  - licence products that are listed, of a software on the site: the
    description, the software's and maker's names, the listed variants'
    titles. A hit links to the software's page;
  - Vorhaben, published: teaser, lead, text.
  The dev data: 32 courses, 17 software, 36 licences, 6 Vorhaben.
- **Kept current** by [[IndexedForSearch]] on the four models and on
  `LicenceVariant`: saved, deleted, restored. A variant re-indexes its
  product and software, a product its software, a software its products,
  because each decides whether the other is on the site
  ([[SearchIndex::syncRelated]]). A course's software or categories changed
  without the course itself being saved are picked up by the next rebuild.
- **The query** ([[SearchIndex::search]]): only letters and digits reach
  MySQL, at most eight words, every one required.
  - Three letters or more: boolean `+word*`.
  - Shorter (*3D*, *KI*, the *V* of *V-Ray*): `REGEXP '\bword'`, at the
    start of a word in the title or body. So *KI* finds *KI-Einstieg* and
    not *Skizze*. Nothing to set on the server.
  - **Order**: how many of the words start a word in the title; then
    software, course, Vorhaben, licence; then relevance, a title hit three
    times a body hit. *Lumion Pro* puts the licence above the Lumion course,
    however often the course says Lumion; *Rhino* puts the Angebot first.
- **`GET /api/search?q=…&limit=…`** ([[SearchController]]), public,
  `throttle:120,1`: `type`, `label` (*Kurs*, *Software*, *Lizenz*,
  *Vorhaben*), `title`, `lead`, `url`. Under two characters, an empty list.
- **`php artisan search:rebuild`**, step 7 of every deploy and after the
  ports (`Deploy.md`).
- **Tests** in `tests/Search`, their own suite: InnoDB's FULLTEXT does not
  see rows a transaction has not committed, so `RefreshDatabase` would make
  every search find nothing. Those tests truncate instead (`tests/Pest.php`).

Seen on the dev data: a licence and its software both appear (*V-Ray*:
the software, then two licences), linking to the same page. Whether the
result list groups them is for the design.

## The field and the result page — built 2026-10-26

Proposed on a branch and merged the same day (Marcel);
screenshots in `history/search-proposal-2026-10-26/`.

**Why not the mockup's input.** The mockup's grey box sits in a header of
14px links. Ours is the live header rebuilt 1:1: four links at 24px bold,
already filling their columns at desktop. A field there means shrinking the
nav. So the proposal uses the site's own pieces:

- **A magnifier** beside the basket and the profile, drawn at their weight
  and sized as the profile icon is ([[icon.search]]).
- **It opens a panel under the header's rule**: white, its own black rule
  below, laid out as Kontakt is (Marcel, 2026-10-26): *Kurs oder Software
  suchen* bold teal in the aside, the field in the copy column, a form field
  as everywhere on the site (bold teal on a black rule). Six hits as the site's list rows (title bold, the type as
  a badge on the right, the lead in grey), then *Alle Ergebnisse für «…»*
  with the site's arrow. *Nichts gefunden für «…»* when nothing is.
  Arrows move through the hits, Enter opens the marked one or the result
  page, Escape or a click outside shuts it ([[siteSearch]]).
- **`/de/suche?q=…`**: the article with *Suche* in the aside and the field at
  the top of the column, every hit under it as **one ranked list** with its
  badge, as the panel draws them, the field's rule the first row's (Marcel,
  2026-10-26; a first pass grouped them in collapsibles). `noindex`.
- **On a phone** the field is the first row of the teal menu panel
  (*Suchen*, as a placeholder), a plain form to the result page: a live
  list would sit under the keyboard.
