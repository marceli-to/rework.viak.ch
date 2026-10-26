# 15 — Full-text search (not yet built)

A search field in the header, *Kurs oder Software*, as the screenshots of
`14-angebote.md` show it. Asked for in the meeting of 2026-10-26.

## Status

**Scoped 2026-10-26**, nothing built. Comes after `14-angebote.md`, because
what a hit links to is the Angebot. Questions 56 and 57 in `Open-Questions.md`.

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
