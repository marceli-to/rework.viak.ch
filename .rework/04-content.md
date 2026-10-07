# 04 — Content (not yet built)

The marketing site the mockups describe, and the admin surface that edits it.

## Status

Scoped 2026-09-16 against the 24 mockups in `history/mockup/`. **The first new
page is built: *Über uns*, 2026-10-06** (see *Über uns*, below). The rest is not.

**Split by the 2026-09-18 phasing decision** (`00-foundation.md`): the **field
kit** is parity work — it is what the admin screens replacing the legacy
dashboard are built with, and deferring it behind a hand-rolled parity admin is
the failure mode this chunk exists to prevent. The **new pages and templates** —
Vorhaben, the software templates, Aktuelles, the homepage — are phase two, and
wait on designs and copy. The two halves of this chunk no longer land together.

**The client went through all 24 mockups on 2026-09-23** — see *The mockup
review*, below. It approved five screens, dropped three and left fifteen open.
It does **not** move the phasing: the parity versions of Experten, Kontakt,
Individualschulungen and the homepage exist on the live site and are rebuilt
first, as `09-public-site.md` has them; the approved mockups are what those pages
become afterwards.

## The mockup review — 2026-09-23

A call with the client over the 24 mockups, captured in an artifact and kept in
`history/mockup/review-2026-09-23/` — `screen-review.md` for reading,
`screen-review.json` for the marker coordinates (1280px design width),
`marker/` for the annotated screenshots. What follows is what it decided for the
build; the review itself is the record of what was said.

### Decided

| Decision | Screens |
|---|---|
| **build** | Alle Angebote, Kurse, Software, Team, Kontakt |
| **build in part** | Aktuelles — as a way into a blog, and the blog itself is still open |
| **drop** | Unsere Methode; the Rhino and After Effects *Einstiegskurs* mockups (the course detail page already exists, chunk 02) |
| **open** | Homepage, all six Vorhaben, the five software screens, Firmenschulung, Meine Lizenzen, Twinmotion Lizenzen |

Nine of the fifteen open screens were **not discussed at all** — the six
Vorhaben other than Räume, and the software screens. Open means *not yet
talked about*, not *disputed*.

### What the markers say, per screen

- **Team** — three parts: an *Über uns* text, the **experts listing**, and the
  team. So the mockup's Team page is legacy's Experten page and the *Über uns*
  and *Team* collapsibles from legacy's Kontakt page, merged. The parity build
  keeps them where legacy has them; the merge is phase two.
- **Kontakt** — link it to Firmenschulung (built 2026-10-06, under *Nachricht
  senden*), and **add the form**. **The form is
  built, 2026-10-06**: an open collapsible, *Kontaktformular*, under the address,
  with the mockup's three fields; #24 is answered in [[ContactController]]. Legacy's Kontakt
  has no form at all (address, map, and four collapsibles), so the form is new,
  and it is the first public form that sends a mail to VIAK rather than to the
  customer. It waits on mail, which does not exist yet. **Neither form
  prefills from the account** (Marcel, 2026-10-06): Kontakt and Firmenschulung
  start empty for a signed-in visitor too; a refused send keeps what was typed.
- **Firmenschulung** — show reviews. That is the `Testimonial` model below.
  **Built 2026-10-06** (Marcel's calls): `/de/firmenschulung`, 301 from
  `/de/individualschulungen`; **legacy's copy**, not the mockup's filler, drawn
  as *Über uns* opens; then open collapsibles *Anfrage* (Firma,
  Ansprechperson, E-Mail, Nachricht, to the office as Kontakt's is) and
  *Kundenmeinungen*, the testimonials **picked for it** in *Seiteninhalte →
  Firmenschulung*. That picker is the first: a fixed page is a `Page` row
  holding placements ([[Page]]), so the homepage takes the same route. Kontakt
  links here from its form (marker 1). Not in the nav.

  **Reshaped the same day** (Marcel): no collapsibles. The enquiry form
  follows the copy in its column under an `h2` *Anfragen* (the copy's opening
  line is an `h2` too), and the testimonials are cards below it in *Über
  uns*'s grid, the team card's teal frame, the author bold and black. Still
  not in the nav (see *Über uns* below).

  **Collapsibles back, 2026-10-07** (Marcel, "a tad more structure"): the
  enquiry is an open collapsible *Anfrage* under the copy, drawn as Kontakt's
  *Kontaktformular* (a card, *Firmenschulung anfragen* in the aside, the form
  in the column, `#anfrage`), and the testimonials an open *Kundenmeinungen*
  collapsible as on a course page. The dashboard picker adds through a
  `+` at the top right that opens a lightbox, as *Teilnehmer hinzufügen*
  does, instead of a select.

  **Built from the mockup, not rebuilt at parity** (Marcel, 2026-09-24). Legacy's
  page is `/de/individualschulungen`: a six-image slider and five paragraphs
  of *Individualschulungen für Firmen und Einzelpersonen*, no form. It is
  **not** carried across. The new page is `Firmenschulung.html`: title, a lead
  paragraph, an *Anfrage senden* box (Firma, Ansprechperson, E-Mail, a message
  prompted *Gewünschte Software, Teamgrösse, Zeitrahmen…*), and reviews under
  it (marker 1).

  What it hangs on:
  - **mail**, for the enquiry, which is the same form question as Kontakt's
    (`Open-Questions.md` #24);
  - **`Testimonial`**, for the reviews;
  - **copy** — the mockup's lead is filler until VIAK says otherwise;
  - **the old URL.** `/de/individualschulungen` is indexed and must 301 to the
    new page once it exists ([[00-foundation]], *Public URLs*). The new page's
    own path is not decided; `/de/firmenschulung` is the obvious one.

  It also feeds two other screens: Kontakt links to it (Kontakt marker 1), and
  the homepage carries its teaser with a form (homepage marker 4). It is not a
  menu item (homepage marker 8), which matches the live nav today, where
  Individualschulungen is not in the menu either.
- **Räume visualisieren** — the Vorhaben are **pages with a title and text**; the
  tools box beside the headline can go, and so can *Andere Vorhaben* at the
  foot. Which makes the template cheaper than the plan above: the offer list is
  what is left of it.
- **Homepage**, eleven markers, the most decided of the open screens:
  1. an element with a **Vorhaben picker**,
  2. the copy **static** — not an editable field,
  3. an automatic widget (the next course dates),
  4. the Firmenschulung teaser **with a form**,
  5. a **flag on a course** to feature it — shown on the detail page too,
  6. a widget on the homepage for the flagged courses,
  7. courses and licences **together** in one offer list,
  8. **nav: *Angebot* instead of *Kurse* / *Software*, and Firmenschulung is not
     a menu item**,
  9. an *About* module, the experts in it optional, linking to *Über uns*,
  10. **testimonials as a backend module, replacing the Google reviews**,
  11. a blog — with a question mark.

### What it changes in this document

- **`Testimonial` is in.** Marker 10 answers `Open-Questions.md` #18 for the
  homepage: the Elfsight Google reviews are replaced by quotes VIAK enters, not
  carried across. Whether the course page's *Kundenmeinungen* column moves to
  the same model is the part still open (#18, narrowed).
- **The homepage worked example below needs revisiting** before it is built:
  the hero copy is static (marker 2), a course flag replaces or joins the
  curated Software picker (5, 6), and the Firmenschulung teaser carries a form
  (4). Left as written until the homepage is decided, because it is still
  marked open.
- **Aktuelles depends on the blog question.** `Article` stays in the cost table
  but is not started until #22 is answered.

## Über uns — built 2026-10-06

The Team mockup (*build*, markers 1–3), at `/de/ueber-uns`. Marcel's calls, the
same day:

- **The mockup's content in the site's look.** No Poppins, no centred heading,
  no outlined cards of its own. The text opens the page as Kontakt's address
  does (the title teal bold in the aside, the copy in the column, its old
  heading the bold first line), then the experts and the team as collapsibles. **This is the rule for every new page**: the
  mockups say what a page holds, the current site says how it looks.
- **One page, and `/de/experten` 301s to it.** *Über uns* is the text that was
  Kontakt's shut block (moved, so Kontakt now has Anreise and Impressum), the
  experts exactly as the Experten page listed them, and the team. An expert's
  own page keeps its URL and lights *Über uns* in the nav.
- **The nav renames Experten to *Über uns*, and nothing else.** *Angebot*
  (homepage marker 8) waits for the homepage. **Amended the same day**:
  *Software* sits after Kurse with a `#` link until its page is built. Four
  links do not fit legacy's `span-6`, so they take `span-10` (`span-9` from
  lg) and stay on one line; the bar still starts at sm. **The container went
  from 1100 to 1200px** (Marcel), site and dashboard alike
  (`layout/header.blade.php`, `layout/site.blade.php`). Firmenschulung was a
  menu item for a few hours and came out again (Marcel): marker 8 stands, the
  homepage, the course pages (the Kurse filter's teal box) and Kontakt link
  to it.
- **The team is a dashboard module**, *Seiteninhalte → Team*: name, *Funktion*,
  a portrait on the shared image section, publish, dragged into order.
  `TeamMember`, not an account. Legacy's table is empty, so nothing is ported,
  and the block stays off the page until someone is published. **Who is on the
  team is the client's to fill in.**

## Vorhaben — built 2026-10-07

The Räume mockup as the review cut it (markers 1–3), and its tiles on the
homepage (homepage marker 1). First pass, built to be reshaped:

- **`Project` in code, *Vorhaben* everywhere a person reads**, as a
  Veranstaltung is an `Event`. Table `projects`, offer list `course_project`
  with its own `order`.
- **A row is its copy and its courses**: title, *Kurzbeschrieb (Startseite)*
  (the tile's second line), *Einleitung* (the page's bold `h2`), *Text*
  (richtext), the courses picked, publish. The tools box, the phone box,
  *Andere Vorhaben* and the breadcrumb are not built (markers 1, 2).
- **The page**, `/de/vorhaben/{slug}`, is drawn as Firmenschulung's top
  ([[training._intro]]): title teal in the aside, lead and text in the column.
  The offer list is the Kurse page's own `x-card.course`, `span-6`, `span-4`
  from sm, which is their width in the Kurse grid. Published courses only, in
  the dashboard's order. The mockup's *Alle / Kurse / Software* chips and the
  licence cards wait for chunk 05. No list page; the homepage is the way in.
- **The slug** is made from the title on create and never again, numbered if
  taken; the site's slug rule turns ä into a, as legacy's course slugs do
  (`/de/vorhaben/raume-visualisieren`).
- **The homepage** (`HomeController`) shows *Was möchtest du machen?* under
  the stub's intro: every published Vorhaben in its order, a tile each with
  the course card's teal frame and heading sizes, filling teal on hover.
  "Vorhaben-Picker" is read as **the tiles are the visitor's picker**; which
  ones show and in what order is the dashboard list's publish and drag. The
  rest of the homepage is still to come.
- **Markers 2 and 3, built the same day.** The call band (*Nicht sicher, was
  du brauchst?*) is the mockup's copy fixed in `site/landing/_call.blade.php`, drawn as
  the Kurse filter's teal box: the copy in span-8, the number and *Rückruf
  vereinbaren* (Kontakt's form) in span-4. *Nächste Kurstermine* is an open
  collapsible with the next six bookable Veranstaltungen (published, not
  cancelled, of a published course) as the portal's `x-row.event`, without the
  course number and the state badge (new props `numbered`, `showState`).
  *Anmelden* links to the course page, which already handles full courses,
  existing seats and the laptop question, rather than copying the basket
  button onto the homepage.
- **The call band reworked** (Marcel: it read flat): the question as a bold
  headline (20/24/28px), the sentence below in regular weight, the phone
  number bold at the headline's size, *Rückruf vereinbaren* as the site's
  white `outline` button, the Vorhaben tiles' padding (8, 16 from lg), both
  halves centred.
- **Legacy's intro and footer back** (Marcel, 2026-10-07), rebuilt 1:1 and
  above and below the new sections:
  - *Ihre Zukunft ist visuell* and legacy's copy, then the home hero as a
    slider: **Swiper, as legacy** (Marcel's call, after a first pass in plain
    Alpine), with legacy's setup (loop, 5s autoplay, teal chevrons, 300ms),
    started from Alpine (`components/slider.js`, `x-media.slider`). Only its
    core CSS; the arrows are the component's own SVG buttons. The images live on
    `Page::for('home')` (`HasMedia` now), edited in *Seiteninhalte →
    Startseite* with the course form's image section; the *OpenGraph* one is
    the page's `og:image` and stays out of the slider. `port:media` brings
    legacy's 20 published hero images across and is now rerunnable (rows
    already ported are skipped).
  - The footer (homepage only, as in legacy): *Newsletter* with the reveal
    form, *Kontakt* with address, phone, mail, Instagram, Facebook. **The
    signup reaches no Mailchimp list**: `NewsletterController` validates,
    guards (Turnstile, honeypot, 5/h) and logs, until `Open-Questions.md` #13
    says whether the integration stays.
- **Marker 4, the Firmenschulung teaser** (2026-10-07), after *Nächste
  Kurstermine*, a second call to action beside the call band, which stays:
  the band is for whoever does not know yet, the teaser for a company that
  does. The mockup's copy fixed in `site/landing/_training.blade.php`, with
  **no form on the homepage**: *Firmenschulung anfragen* goes to the page's
  form (`#anfrage`), as the mockup's button does, so there is one form to
  keep. Marcel picked a **grey panel** from four layouts (after a framed card
  and an image beside the copy): `gray-200` at half strength, the pitch in
  six columns, the four facts in the other six as label and value rows with
  dividers between, centred against the pitch. No image. The mockup's
  *Zuletzt geschult* line is left out and the price is the mockup's, both
  `Open-Questions.md` #40.
- **Markers 5 and 6, *Beliebte Angebote*** (2026-10-07), after the
  Firmenschulung teaser: a **`featured` flag on the course**, the checkbox
  *Beliebt* in the course form's *Einstellungen*, and the homepage lists the
  published flagged courses in the catalogue's order as the Kurse page's
  `x-card.course`, three across from sm. Hidden when none is flagged. The
  mockup's tiles pair a course with its licence (marker 7); software is only
  a filter term until chunk 05, so the flag on software and the licence half
  wait for it, and the link beside the heading is *Alle Kurse* in place of
  *Alle Angebote von A bis Z*. Marker 5's *Anzeige auf Detailseite* is read
  as where the flag is set (the course's form), not as a badge on the public
  course page.
- **Marker 9, the About teaser** (2026-10-07): *Warum bei der VIAK*, drawn
  as the Firmenschulung teaser's grey panel, and *Mehr über uns* to *Über
  uns*. **No experts** (Marcel). **Editable, with an image** (Marcel, the
  same day): *Seiteninhalte → Startseite*, collapsible *Über uns* with
  *Titel*, *Text* (rich text) and the image section, kept on `Page::for('home-about')`
  (copy in the new `pages.content`, image in its media). The mockup's copy is
  the form's default until someone saves. The image (the first, or the one
  marked *Vorschau*) stands beside the copy at 16:9, the cropper's shape, the
  two in six columns each; without one the copy keeps eight.
- **Marker 10, the testimonials** (2026-10-07): *Kundenmeinungen* at the
  end, picked and ordered in *Seiteninhalte → Startseite* on
  `Page::for('home')`, hidden when none is picked. That screen is three
  collapsibles (Marcel, the same day): *Slideshow Bilder*, *Über uns*,
  *Rezensionen*; images save at once, *Über uns* and *Rezensionen* each with
  their own *Speichern* inside.
- **The homepage view is `site/landing/index`** (2026-10-07), each section a
  partial as Kontakt's are: `_intro`, `_projects`, `_call`, `_events`,
  `_training`, `_featured`, `_about`, `_footer`.
- **No collapsibles on the homepage** (Marcel, 2026-10-07): *Nächste
  Kurstermine* and *Kundenmeinungen* are plain sections under a bold `h2`, as
  *Was möchtest du machen?* and *Beliebte Angebote* are. *Alle Kurse und
  Termine* moved up beside the heading, as the mockup's section heads carry
  their link.
- **The testimonials are a slider** (Marcel, 2026-10-07), on the homepage and
  Firmenschulung, whose *Kundenmeinungen* also lost its collapsible for a
  bold `h2`: `x-testimonial.slider`, Swiper as the intro's, three cards from
  lg, two from sm, one on a phone, a page at a time, autoplay every 6s
  (paused under the pointer, stopped by a dot or a swipe, off for reduced
  motion), no arrows, round dots under it, hidden when every card fits. A
  course page's *Kundenmeinungen* stays a collapsible grid, as all its
  sections are collapsibles.
- **Dashboard**: *Seiteninhalte → Vorhaben*, a list dragged into the tiles'
  order with the course count as a badge, and a form whose course picker is a
  new field-kit type, `Field::offers` ([[OfferPicker]]): the testimonial
  picker's rows, bin and `+` lightbox, with a search.
- **Metatags + SEO** (Marcel, 2026-10-07): the Vorhaben form has the course
  form's section, *SEO - Beschreibung* and *SEO - Keywords*
  (`seo_description`, `seo_tags`), for the page's meta tags. Without a
  description the lead stands in, as it did before.
- **Dev data**: `php artisan db:seed --class=ProjectSeeder` makes the six with
  the mockups' filler copy and their offer lists matched to real courses by
  number. Not for production; which of the six are real is still
  `Open-Questions.md` #4.

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

**Confirmed 2026-09-16.** The one thing that would have reversed this — VIAK
wanting to create marketing pages with layouts nobody has designed — is not a
requirement. Asked and answered: no. Bucket 3 below stays ours, and the decision
is settled rather than assumed.

Filament was the other candidate, for the same CRUD savings without a second
frontend paradigm. Declined for the same reason 1: it still means two admin UIs,
and the transactional dashboard from chunk 02 already exists in Vue.

## Decision: the admin edits DE only, the data stays translatable

**Decided 2026-09-16.** EN does not ship publicly today — legacy gates `/en`
behind `role:admin` — and it is not in scope for the rework.

What does **not** change: `spatie/laravel-translatable` on the models, the
`{de, en}` maps the Resources return (chunk 02), the `Locale` enum, and the
port, which keeps carrying whatever EN content exists. The data model is
untouched.

What changes is only the **admin UI**. `Field::text('title')->translatable()`
still declares the field translatable; the `FormRenderer` decides whether to
draw locale tabs from the configured locale list, and with one locale it draws a
plain input. The public site already picks the locale it needs.

So this is not a one-way door. Turning EN on later is a config change plus
content entry — not a rebuild. That matters, because **whether EN ever ships is
still an open client question** (see *Open questions*), and the answer arriving
after this chunk is built must not be expensive.

Worth being accurate about the saving: this removes the tab chrome, per-locale
error display, a copy-DE→EN affordance, and testing every field twice. That is
roughly 15–20 % off the field kit, not the halving an earlier estimate
suggested.

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

**Built 2026-09-24**, extracted from the course and testimonial forms as planned
below — `app/Forms/` and `07-dashboard.md`, *Step 5*. What changed from the plan:
the schema serves the dashboard *and* validates the request (the plan colocated
them; now they are one declaration), `hidden` joined the inventory, and the
media field is a `custom` part with hooks rather than a `Field::image()`,
because images save on their own.

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
live preview and revisions. **An asset manager it does get** — decided
2026-09-18, the media subsystem is ported from `forrerzimmermann.ch` rather
than taken from a package, and it brings a grid, an uploader and a crop UI with
it. So `Field::image()` is genuinely the thin part: it picks from media that
already exists, and the pipeline behind it is settled. See `08-accounts.md`.

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
stack of Blade components — every one of which is needed by another page
anyway:

- `x-vorhaben-tiles` → also "Andere Vorhaben" on all six Vorhaben pages
- `x-event-list` → also Kurse, Vorhaben, software pages
- `x-offer-grid` → the Vorhaben offer list, Alle Angebote
- `x-article-list` → Aktuelles
- `x-callback-band`, `x-newsletter`, `x-footer` → everywhere

Alpine only in the header (basket, search) and on the filter chips — not Vue; see
*The public site is Blade + Alpine* in `00-foundation.md`. The homepage body needs
neither.

**So build the homepage last.** After Vorhaben, Aktuelles and the software
templates it is five existing partials, one controller and a five-field schema.
Built first, every one of those components gets designed against a single caller
and guessed props.

### Two entities the homepage forces into existence

Neither is homepage-specific, and both are needed elsewhere:

- **`Testimonial`** — quote, name, role, featured. Also `Rhino.html`'s "Was
  unsere Kund:innen sagen".

  **It cannot be filled from `courses.reviews`, which was the plan.** Checked on
  2026-09-21: all 32 non-empty rows are an **Elfsight widget embed**, 23
  distinct widget ids, so the *Kundenmeinungen* cards on the live course page
  are Google reviews drawn by a third party in the browser. VIAK owns none of
  that text. Either this model is filled by hand from real quotes, or the widget
  is carried across and the model is not built at all — `Open-Questions.md` #18,
  which replaces the old #12. **The client chose the first on 2026-09-23**
  (homepage marker 10): a backend module, filled by hand, replacing the Google
  reviews. Firmenschulung shows them too.
- **`Settings` / globals** — phone, address, footer nav, newsletter copy. One
  schema, one screen. Could also be a config file if the client never edits it;
  see the levers below.

## What this costs

A shape, not an estimate. Recorded because the working figure from the week of
2026-09-07 predates the mockups and is now clearly low.

Revised 2026-09-16 for the DE-only admin decision above.

| | h |
|---|---:|
| Field kit (12 components, renderer, errors; DE only) | 28–34 |
| Media field + `spatie/laravel-medialibrary` behind it | 8 |
| Globals / Settings | 4 |
| `Page` + `Article` (Aktuelles, with its filters) | 10 |
| `Vorhaben` × 6 (one template, one schema) | 8 |
| `Testimonial` | 3 |
| Software hub / Überblick / Lizenzen templates | 12 |
| Homepage (built last, from existing partials) | 8–12 |
| | **~81–91** |

Priced at conventional hand-coding rates, which sits awkwardly against the
observed pace of chunks 00–02 (three chunks across three working days, 7.3k LOC,
two reconciled data ports). The reason it is not simply scaled down: **the field
kit is UI work**, and UI work compresses least. Ports, Actions and Resources are
pattern work and they fly; twelve polished form components with locale tabs,
tiptap and an image cropper do not.

### Levers, if the number has to come down

The EN lever is spent — DE-only is decided above and already priced into the
table. Three left, all smaller:

1. **Fold `*-Ueberblick` into the software hub page.** Two templates doing one
   job; the mockup splits them, nothing requires it.
2. **Globals in a config file**, not a DB screen. The phone number never changes.
3. **Testimonials hardcoded in Blade** until they actually change. Saves the
   module, costs a deploy when a quote changes.

None of these touch the field kit, which is the one line not worth cutting:
cutting it does not save 40h, it spends 60 elsewhere and leaves ten inconsistent
forms.

## The software pages need `software` to stop being a taxonomy — 2026-09-17

Three of the nine templates here are software pages — the hub, "Was ist X?", and
Lizenzen — and between them they drive 30 to 45 of the pages in this chunk. They
all read from `software`, which is currently one of five identically-shaped
taxonomy tables: uuid, `json title`, order, publish. Enough to tag a course,
nowhere near enough to render a product page.

It grows a slug, descriptions, SEO fields and a `manufacturer_id`, and gains
`software_variants` for the licences themselves. The Hersteller filter on the hub
is just another taxonomy and can join the existing migration's `TABLES` loop.

Proposed shape and the reasoning — including why breaking the five-identical-
taxonomies symmetry is the right call — are in `05-licences.md`. It matters here
because the field kit has to edit these, and because a software entity is what
lets a Vorhaben page pull courses *and* licences for one tool from one relation.

## Open questions

1. ~~Does the client want to build pages we have not designed?~~ —
   **answered 2026-09-16: no.** The Statamic decision is settled.
2. ~~Does EN ship publicly?~~ — **answered 2026-09-16: not in this rework.**
   Replaced by the question below, which is narrower and for the client.
3. **Will EN ever be implemented?** The admin is DE-only and the data model
   stays translatable precisely so the answer can arrive late without costing
   anything. Still worth asking, because if the answer is a firm never, the
   translatable columns and the `{de, en}` Resource maps become dead weight
   that a later chunk could simplify away.
4. ~~`courses.reviews` → `Testimonial`~~ — **answered 2026-09-21**: those rows
   are Elfsight embeds, not data, so there is nothing to reshape. What is left
   is a decision about the widget, which is `Open-Questions.md` #18.
5. **Which of the six Vorhaben are real**, and are there more coming? The
   template is cheap; six is assumed from the mockups. **Narrowed 2026-09-23**:
   the review settled the template (title, text, offer list) on Räume, and
   did not discuss the other five. The count is still open —
   `Open-Questions.md` #4.
6. ~~Media: `spatie/laravel-medialibrary` assumed.~~ — **answered 2026-09-18:
   no package. Port the media subsystem from
   `github.com/marceli-to/forrerzimmermann.ch`** — `league/glide` on Imagick,
   one `media` table with a `crop` JSON column and a mobile `variant`, an
   `<x-media.image>` `<picture>` component with AVIF/WebP/JPEG and an 8-step
   srcset, and a Vue crop/upload UI already written in these conventions. It is
   the client's "image handling (frontend output)" requirement answered
   directly. **Built 2026-09-18** in chunk 08, along with the port — so
   `Field::image()` picks from media that already exists. The legacy crop coordinates port across unchanged — they are
   already pixels in Glide's `w,h,x,y` order. Details, and the one trap that
   would silently move 28 crops, are in `08-accounts.md`.
