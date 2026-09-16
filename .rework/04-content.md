# 04 — Content (not yet built)

The marketing site the mockups describe, and the admin surface that edits it.

## Status

Not built. Scoped 2026-09-16 against the 24 mockups in `history/mockup/`.

## The mockups are 24 files but about nine templates

Counting the mockup directory as pages badly overstates the work, and counting it
as templates understates the page count. Both matter:

| Template | Mockup files | What drives the page count |
|---|---:|---|
| Course index / detail | 3 | 41 courses — **built, chunk 02** |
| Software hub / "Was ist X?" / Lizenzen | 6 | ~10–15 products → **30–45 pages, 3 templates** |
| Vorhaben (Räume, Bilder, Objekte, Bewegtbild, KI, Teams) | 6 | fixed set of 6 |
| Homepage, Alle Angebote | 2 | queries |
| Aktuelles | 1 | a real news stream |
| Team, Methode, Kontakt, Firmenschulung | 4 | genuine static pages |
| Meine Lizenzen | 1 | account — transactional, not content |

**The page count grows with entities, not with editorial.** Three software
templates produce forty-odd pages. That is the single most important fact about
this chunk, because it rules out the tool most people would reach for.

### How much of a "content page" is actually content

`Vorhaben-Raeume.html` is the worst case, and it decomposes to:

- a headline and three paragraphs of copy,
- six tool chips — relations to Software,
- a curated list of 7 courses + 6 licences with live prices, next dates and a
  working Anmelden button,
- links to the other five Vorhaben.

Roughly 15 % prose, 85 % domain data. What looks like a page builder is a
relation picker and a query rendered by a designed template.

## Decision: no Statamic

Considered and declined on 2026-09-16.

Strip Statamic down and the part we would actually use is its **blueprint
system**: declare fields, get a form with locale handling, validation and
persistence. Everything else it offers — CP chrome, revisions, live preview —
is built on that. The field kit below buys the same thing in our own
conventions, and the three costs of bringing it in are real:

1. **Two admin UIs.** Statamic CP for copy, the Vue dashboard for bookings and
   invoices. Two logins, two mental models, for a client with a handful of
   editors.
2. **A second translation paradigm.** Chunk 02 settled on
   `spatie/laravel-translatable` with `{de, en}` maps in the Resources.
   Statamic does entry-per-locale multi-site. Both in one app is a mess.
3. **The pages need Eloquent anyway.** Every Vorhaben and software page is
   mostly live courses and licences with prices, dates and a basket button.
   Statamic reaches that only through custom tags calling back into our code —
   which is the work we were trying to avoid.

**What would reverse this:** if VIAK wants to create new marketing pages, with
layouts nobody has designed, without calling us. Then bucket 3 below goes to
Statamic and buckets 1–2 stay ours. Nothing in the mockups or in
`history/Client-Requirements.md` suggests they want that — but it has not been
asked directly, and it should be before this chunk starts.

Filament was the other candidate, for the same CRUD savings without a second
frontend paradigm. Declined for the same reason 1: it still means two admin UIs,
and the transactional dashboard from chunk 02 already exists in Vue.

## Three buckets

Everything in the mockups sorts into one of three, and the sorting is the plan:

1. **Entities** — Course, Event, Software/Licence, Expert, Location. Eloquent,
   Vue dashboard, never a CMS. This is where 45 of the pages come from.
2. **Landing templates** — Vorhaben × 6, the three software templates, Homepage.
   Designed Blade, with editable copy fields, relation pickers, and a
   curated-or-queried offer list. The offer list is one component reused
   everywhere; it is worth building well once.
3. **Editorial** — Aktuelles, Team, Methode, Kontakt, Firmenschulung, Impressum.
   A generic `Page` plus a small block set.

## The field kit

This is the decision the chunk turns on.

### Why it is the decision

There is **not a single `<input>`, `<form>` or `v-model` anywhere in
`resources/js/app/`** today. Both SPA views are read-only index screens. So how
forms get built in this app is genuinely open, and chunk 03 will settle it by
default if nobody settles it deliberately.

`StoreCourseRequest` already shows what one entity's form costs: 9 translatable
fields, a repeater capped at 3, 5 taxonomy pickers, a money field, two booleans,
an order integer and an SEO group. Hand-rolled that is 400+ lines of Vue
template, and `Course` is the *simplest* of them. `Event` adds the date builder,
`Article` adds tiptap and an image, `Vorhaben` adds the offer curator.

Ten or so such forms, each re-implementing locale tabs and error display
slightly differently, is the failure mode. Break-even on a kit is around form
four.

### The inventory is closed

Across every model in chunks 01–02 and every content type in the mockups, the
field list is about twelve components:

`text` · `textarea` · `richtext` (tiptap) · `number/money` · `boolean` ·
`select` (enum-backed) · `relation` (single/multi) · `repeater` · `image/media` ·
`date` · `group` · `order`

Plus a `FormRenderer` that walks a schema, a server-error mapper and a
dirty-state leave guard. This is not an open-ended framework — the list is
closed, because the migrations already exist.

### The schema lives in PHP, next to the FormRequest

```php
final class CourseSchema
{
	public static function fields(): array
	{
		return [
			Field::text('title')->translatable()->required(),
			Field::text('subtitle')->translatable(),
			Field::richtext('full_description')->translatable(),
			Field::repeater('facts')->max(3),
			Field::money('fee')->required(),
			Field::relation('software')->multiple()->options(Software::class),
			Field::group('seo', [
				Field::textarea('seo_description')->translatable(),
				Field::text('seo_tags')->translatable(),
			]),
			Field::boolean('publish'),
		];
	}
}
```

Served as JSON, consumed by the SPA:

```
GET /api/schema/courses  →  { fields: [...] }
```

```vue
<FormRenderer :schema="schema" v-model="course" :errors="errors" />
```

**Why PHP and not JS.** The locale list, the `max:3` on facts and the taxonomy
sources are currently written out three times by hand — migration,
`StoreCourseRequest`, and soon a Vue template. Colocating the schema with the
request gives them one home. `Field::` then sits at the same altitude as the
Action / Request / Resource conventions in `00-foundation.md` — a fourth
convention, not a foreign object.

**Blade is not involved.** The public site renders saved *data*, not schemas —
`resources/views/site/…` reads `$course->title` exactly as it does today. The
field kit lives entirely in the admin.

### The escape hatch, designed on day one

Schema-driven forms fail at the last 20 %: conditional fields, cross-field
validation, genuinely bespoke widgets. Two are already known — the **event date
builder** and the **Vorhaben offer curator**. So the renderer needs
`Field::custom('dates', 'EventDateBuilder')` and a slot for whole custom
sections from the start. Without it we will bend the kit instead, and it will
rot.

### What the kit does not buy

Named plainly, because these are the things Statamic has and this will not:
live preview, revisions, and an asset *manager*. Media needs
`spatie/laravel-medialibrary` behind the image field — that is a separate
decision, and the one place a package is clearly worth it. Note that
"image handling (frontend output)" is on the client's own requirements list.

### Build it from two forms, not zero

Build the Course form concretely in chunk 03, build one content form, then
extract the kit from the two. Two rather than the usual three is affordable here
because the field list is already knowable from the migrations.

## Worked example: the homepage

The most CMS-looking page in the set, and it owns four editable fields.

| Section | What it really is | Edited where |
|---|---|---|
| Hero (video + headline) | **page content** | Startseite |
| "Was möchtest du machen?" — 6 tiles | query: `Vorhaben` ordered | on each Vorhaben |
| Callback band (20 Min., kostenlos) | **global** — also on every Vorhaben page | Settings |
| Nächste Kurstermine (6) | query: `Event::upcoming()->limit(6)` | nowhere — live data |
| Firmenschulung teaser | teaser pulled from the Firmenschulung page | Firmenschulung |
| Beliebte Angebote (6 software) | **curated relation**, prices computed | Startseite (picker) |
| Warum bei der VIAK (2 paragraphs) | **page content** | Startseite |
| Expert avatar row (8) | query: `User` with role Expert + bio | on each profile |
| 3 testimonials | query: `Testimonial` featured | Testimonials |
| Aktuelles (2 × 3) | query: `Article` by category | on each article |
| Newsletter, footer, address | **globals** | Settings |

Backend: one `HomeSchema` — hero headline (DE/EN), hero media, "Warum" copy
(DE/EN), and a sortable Software picker. Rendered by the field kit. No bespoke
Vue.

Frontend: a `HomeController` assembling the queries, and `home.blade.php` as a
stack of `<x-site.*>` partials — every one of which is needed by another page
anyway:

- `x-site.vorhaben-tiles` → also "Andere Vorhaben" on all six Vorhaben pages
- `x-site.event-list` → also Kurse, Vorhaben, software pages
- `x-site.offer-grid` → the Vorhaben offer list, Alle Angebote
- `x-site.article-list` → Aktuelles
- `x-site.callback-band`, `x-site.newsletter`, `x-site.footer` → everywhere

Vue islands only in the header (basket, search) and on the filter chips. The
homepage body needs none.

**So build the homepage last.** After Vorhaben, Aktuelles and the software
templates it is five existing partials, one controller and a five-field schema.
Built first, every one of those components gets designed against a single caller
and guessed props.

### Two entities the homepage forces into existence

Neither is homepage-specific, and both are needed elsewhere:

- **`Testimonial`** — quote, name, role, featured. Also `Rhino.html`'s "Was
  unsere Kund:innen sagen". This is where chunk 02's open question about
  `courses.reviews` lands: a `text` column holding what looks like structured
  data, ported as-is, still needing a shape. This is the shape.
- **`Settings` / globals** — phone, address, footer nav, newsletter copy. One
  schema, one screen. Could also be a config file if the client never edits it;
  see the levers below.

## What this costs

A shape, not an estimate. Recorded because the working figure from the week of
2026-09-07 predates the mockups and is now clearly low.

| | h |
|---|---:|
| Field kit (12 components, renderer, locale tabs, errors) | 32–40 |
| Media field + `spatie/laravel-medialibrary` behind it | 8 |
| Globals / Settings | 4 |
| `Page` + `Article` (Aktuelles, with its filters) | 10 |
| `Vorhaben` × 6 (one template, one schema) | 8 |
| `Testimonial` | 3 |
| Software hub / Überblick / Lizenzen templates | 12 |
| Homepage (built last, from existing partials) | 8–12 |
| | **~85–97** |

Priced at conventional hand-coding rates, which sits awkwardly against the
observed pace of chunks 00–02 (three chunks across three working days, 7.3k LOC,
two reconciled data ports). The reason it is not simply scaled down: **the field
kit is UI work**, and UI work compresses least. Ports, Actions and Resources are
pattern work and they fly; twelve polished form components with locale tabs,
tiptap and an image cropper do not.

### Levers, if the number has to come down

1. **Does EN ship publicly?** Already open in `Todo.md` — legacy gates `/en`
   behind `role:admin`. If the answer is no, every translatable field loses its
   locale tab and the form UI gets materially cheaper. The largest single lever,
   and it costs nothing to ask.
2. **Fold `*-Ueberblick` into the software hub page.** Two templates doing one
   job; the mockup splits them, nothing requires it.
3. **Globals in a config file**, not a DB screen. The phone number never changes.
4. **Testimonials hardcoded in Blade** until they actually change. Saves the
   module, costs a deploy when a quote changes.

None of these touch the field kit, which is the one line not worth cutting:
cutting it does not save 40h, it spends 60 elsewhere and leaves ten inconsistent
forms.

## Open questions

1. **Does the client want to build pages we have not designed?** The one
   question that would reverse the Statamic decision. Not yet asked.
2. **Does EN ship publicly?** Carried from `Todo.md` and `02-courses-events.md`.
   Now also the biggest driver of this chunk's cost.
3. **`courses.reviews` → `Testimonial`** — the shape proposed above needs
   confirming against what those 35 rows actually hold.
4. **Which of the six Vorhaben are real**, and are there more coming? The
   template is cheap; six is assumed from the mockups.
5. **Media**: `spatie/laravel-medialibrary` assumed. Confirm against the
   "image handling (frontend output)" requirement before building the image
   field, since the field is the thin part and the pipeline is the thick one.
