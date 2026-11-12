# 14 — Angebote: Kurs and Software as one

One page per tool, with the courses and the licences for it side by side. What
the client asked for in the meeting of 2026-10-26, the biggest of the five
changes it raised (the others: `15-search.md`, `13-checkout.md` *Invoice only*,
`16-firmenkurse.md`, `17-mcp.md`).

## Status

**Planned 2026-10-29** from the client's answer, see *The client's answer,
2026-10-29*: two levels, the nav, the URLs and *Kurs ab* settled, #66–67 open (#66 answered 2026-11-03). **Steps 1 to 4 built the same day** (*As built*, at the end): the plan is done. **Scoped 2026-10-26.** **The data built the same day** (*Built ahead of the
layout*, at the end); the pages are not. The screenshots shown in the meeting are
in `history/meeting-2026-10-26/`: `homepage.png` (*Beliebte Angebote*, markers 6
and 7 of the 2026-09-23 review), `angebote.png` (*Alle Angebote*), `rhino-1.png`
and `rhino-2.png` (one Angebot). They are the 2026-09-23 mockups
(`history/mockup/Homepage.html`, `Alle-Angebote.html`, `Rhino.html`) brought
back, not new designs. Questions 50–55 in `Open-Questions.md` are what the
build needs answered.

**This reverses a decision.** On 2026-10-09 Marcel chose one grid of separate
course and software cards for *Beliebte Angebote* over the mockup's paired
tiles (`04-content.md`, *Marker 7*), and the link *Alle Angebote von A bis Z*
was left out because it had no page. The client wants the paired tiles and the
page.

These are new pages, so the parity rule does not hold them: they take the
mockup's content in the site's look, as the Vorhaben and Firmenschulung did.

## What the screenshots show

**One Angebot** (`rhino-1.png`, `rhino-2.png`), at *Start / Software /
Rhinoceros*:

- A hero on the light teal ground: title, a one-line lead, a paragraph, the
  **Vorhaben the tool belongs to as chips** (*Objekte entwerfen*, *Räume
  visualisieren*), anchor links (*Kurse*, *Lizenzen*, *Mehr über Rhino*,
  *Kundenstimmen*), the image on the right.
- **Kurse and Lizenzen side by side**, each with an intro line. A course row:
  title, price, one line of facts (*2 Tage, Zürich · 16. bis 17. November 2026
  · Anmeldung bis 2. November · keine Vorkenntnisse nötig*) and *Anmelden*. A
  licence row: title, price, three ticked features, *Lizenz kaufen*. Under the
  courses: *Preise exkl. MwSt., inkl. Kursunterlagen*, and *Firmenschulung
  anfragen*.
- **Mehr über Rhino**, on grey: three sub-headed paragraphs and two captioned
  images.
- **Was unsere Kund:innen sagen**: three testimonials.

**Alle Angebote** (`angebote.png`, breadcrumb *Home / Angebot*): an intro, then
**one section per Vorhaben**, a grid of tiles. A tile is a title and a type
line, *Kurs & Software →*, *Software*, *Kurs*, with *neu* or *redaktionell*
where it applies. Mockup tiles for things that do not exist yet are greyed *in
Vorbereitung*; in the build a tile only exists for something published. A tool
appears under every Vorhaben it belongs to (Rhino is in both sections shown).
Some tiles are **courses with no software** (*Geheimnisse der ArchViz*, *ArchViz
mit KI*, *AI Expert Kurs: Figma Weave*).

**Beliebte Angebote** (`homepage.png`): six tiles, image, title, a one-line
lead, then **Kurs ab CHF …** and **Lizenz ab CHF …**. Either side can be *auf
Anfrage*; After Effects' licence side is *Lizenz direkt bei Adobe*. The heading
links to *Alle Angebote von A bis Z*.

The mockup copy has long dashes in it; they become full stops, commas or
colons in the build.

## What exists already

Most of the data is in place; the change is in the pages.

- **`Software` is the Angebot.** It has the title, the descriptions, media,
  `categories`, `featured`, its licences (`products()`, with
  `LicenceProduct::fromPrice()`), and its courses (`courses()`, through the
  `Course::software()` morph).
- **The software page already shows both** ([[SoftwareController::show]]):
  licences and courses, each in a collapsible, then recommendations,
  *Detailbeschrieb*, *Weitere Informationen*, *Kundenmeinungen*. The rebuild
  is a new layout over the same queries.
- **Vorhaben** (`Project`) already pick courses and software
  (`course_project`, `project_software`), which is what *Alle Angebote*
  groups by.
- **Testimonials** about a software already exist (`testimonialsAbout()`).

## What is new

- **The Angebot page layout** above, replacing `site/software/show.blade.php`.
  The course rows are new (the page draws `x-card.course` today); the licence
  rows sit close to `x-card.product`, with features as ticks.
- **Licence features** (*Kauflizenz, unbefristet*, *Windows und Mac*): no
  field holds them today. Likely a short list on `LicenceProduct`.
- **Mehr über …** with sub-heads and captioned images: today
  `full_description` is one rich-text field with no images beside it.
- **The Vorhaben chips** on the page: the reverse of `project_software`.
- **Kurs ab CHF**: the lowest price of the published courses for the software.
  *Kurs auf Anfrage* when there is none, or when none has an upcoming date
  (to settle, #52).
- **Lizenz direkt bei Adobe**: a software with no licence of ours but a
  pointer elsewhere. A text field on `Software`, shown in place of the price.
- **`/de/angebote`**, the index by Vorhaben, and the link to it from the
  homepage.
- **The nav**: marker 8 of the review already said *Angebot* in place of
  *Kurse* and *Software* (`04-content.md`); the screenshots still show *Kurse*
  and *Software* (#53).
- **Redirects** for whatever URLs change, into the redirect map in `Todo.md`,
  *SEO*, and the sitemap.

## The client's answer, 2026-10-29, and the plan from it

**This supersedes *Proposed shape* below** wherever the two differ (the URL,
*Anmelden* going to the booking, the course row).

The client wrote back on #50–53. In short: an Angebot carries a tag
(*Software*, *Kurs*, *Software+Kurs*); *Alle Angebote* shows every Angebot,
*Kurse* and *Software* are the same catalogue filtered; the 5 categories
(*Schmuck- & Produktdesign*, *Architektur & Interior Design*, *Animation &
Interaktion*, *Gestaltung & Bearbeitung*, *AI & Neue Workflows*) on **both**
levels; **booking and buying only ever on a product's own page**, the Angebot
page is an overview. The small tiles of `Alle-Angebote.html` for the Angebote.
Their *Kurse zeigt alle Angebote mit Tag Kurs* contradicts their next lines
(*hier werden direkt die Produkte angezeigt*); read as the latter.

### Two levels

| Level | Example | Page |
|---|---|---|
| **Angebot** | Rhinoceros | Overview: courses, licences, *Mehr über*, testimonials. Nothing is bought here. |
| **Produkt**: a course | Rhino Einstiegskurs | The course page, booked there. Exists. |
| **Produkt**: a licence | Rhinoceros 8 | **New.** Its variants with *Anzahl* and *In den Warenkorb*, today's `x-card.product` moved over. |

### Decided with Marcel, 2026-10-29

- **The Angebot is `Software`** (A). No new table; the dashboard calls it
  *Angebote*. **The tag is derived, never set**: listed licences make it
  *Software*, published courses *Kurs*, both *Kurs & Software*.
  `Software::onSite()` widens to "a listed licence **or** a published
  course", which brings Blender (4 courses), SketchUp (3) and After Effects
  (2) onto the site as *Kurs* Angebote, the mockup's *Nur Kurs*.
- **A licence page per `LicenceProduct`** (B), *Rhinoceros 8* with its 6
  variants, not one per variant (the client's *Rhino 8 Commercial Upgrade*):
  six near-identical pages per tool would be thin.
- **A course without software** (#50): a tile of its own on *Alle Angebote*,
  tag *Kurs*, under its categories, **linking straight to the course page**.
  Courses that belong together (Visual Facilitator 1–5, LEGO Serious Play
  1–2) can instead get an Angebot with no licences, entered in the
  dashboard: one tile, an overview listing the courses. A content choice,
  no code. On dev data 8 published courses have no software.
- **The Angebot page's rows**: *Anmelden* goes to the course page, *Lizenz
  kaufen* to the licence page (#51: the course page stays). Proposed, one
  row per course with its next date; the dates are on the course page.

### The nav, the URLs, the way back (E)

Decided by Marcel 2026-10-29.

**The nav is *Angebote · Über uns · Kontakt*.** *Kurse* and *Software* are
pages under Angebote, not nav items (review marker 8 asked the same in
September). *Angebote* lights on every catalogue page: the three lists, an
Angebot, a course, a licence.

**A switch *Alle Angebote · Kurse · Software*** on top of the three list
pages is the way into Kurse and Software, so it has to read as navigation,
not as a quiet filter. The phone menu may indent Kurse and Software under
Angebote; the desktop bar does not show them.

**The URLs carry the hierarchy, with 301s from legacy's.** Marcel preferred
the move to keeping `/de/kurse` flat, provided it does not cost much ranking.
It does not: a 301 passes the full signal (Google, since 2016); the cost is a
few weeks of fluctuation while the index swaps over. What does lose rankings
is a redirect done badly, hence the rules below.

| Page | URL | From |
|---|---|---|
| Alle Angebote | `/de/angebote` | new |
| One Angebot | `/de/angebote/{slug}` | `/de/software/{slug}` (prototype only, never live) |
| Kurse | `/de/angebote/kurse` | **301** from `/de/kurse` |
| One course | `/de/angebote/kurse/{slug}` | **301** from `/de/kurs/{slug}` and `/de/kurs/{slug}/{uuid}` |
| Software | `/de/angebote/software` | `/de/software` (prototype only) |
| One licence | `/de/angebote/software/{slug}` | new |

- **One hop.** `/de/kurs/{slug}/{uuid}` goes straight to the new URL, not
  through `/de/kurs/{slug}`; `viak.ch` → visualisierungs-akademie.ch should
  not add a second hop on top.
- **Each course to its own page**, never all to the list.
- **Internal links, sitemap and canonicals** name the new URLs only.
- **The redirects stay for good.** Into `Todo.md`'s redirect map and
  `port:check-urls`, and the cutover in `Deploy.md`.
- **`kurse` and `software` are reserved** as Angebot slugs, so no Angebot
  can shadow a list. A check in `Software::freeSlug()`.
- Legacy's `kurs` / `kurse` split ends under Angebote: list and detail both
  `kurse`, as Software has it. The segments come from `config/site.php`.

**No breadcrumbs.** The mockups have them; the site has none anywhere, and
the Vorhaben page left its own out for that reason. What they would have said
is said by the nav's highlight and the switch. **A product page links to its
Angebot** instead (*Alles zu Rhinoceros*, wording to settle), near the title:
the client's path runs Angebot → product, and without the link the way back
is the browser's. A course under two Angebote (*Visualisieren mit SketchUp*:
SketchUp and V-Ray) names both; a course without one names none.
**Taken off course pages, 2026-10-30** (Marcel): a course under three
Angebote (*KI-Einstieg für Video Creator*) stacked three links beside the
title. The licence page keeps its one.

### The categories

The client's 5 replace the 3 there are (*3D-Software* 18 courses,
*Bildgestaltung & Handwerk* 7, *Management & Kommunikation* 8), on courses,
software and, new, licence products; they drive the filters on Kurse and
Software. A migration maps what it can; the rest is listed for VIAK to set.
**Visual Facilitator, LEGO Serious Play and Urban Sketching fit none of the
5** (#66). **Answered 2026-11-03: they do not continue.** The client keeps the
structure (Software / Kurse) and **sets the 8 courses unpublished itself**;
nothing is built for it. [[Angebote]] reads only published courses and drops a
section with no tiles, so *Weitere* goes with them, on the page and in
`/llms.txt`. On dev data 4 of the 8 still had a Veranstaltung ahead (Visual
Facilitator 1–3, LEGO 2): whether those run is VIAK's call when it unpublishes.

### *Kurs ab CHF* (F, #52)

**Decided by Marcel 2026-10-29: the hybrid.** The lowest fee of a bookable
Veranstaltung, as [[Software::courseFromPrice]] has it; **when no course has
a date, the lowest course fee, worded *Kurs ab CHF 499, Daten auf Anfrage***.
*Kurs auf Anfrage* alone only when there is no course with a fee at all.

Why: on dev data, with the course-only Angebote, 14 Angebote have courses.
By bookable date alone 6 read *auf Anfrage*, and a tile jumps whenever a date
fills or passes. By course fee alone all 14 show a price, but Rhino reads 549
where the cheapest bookable course is 949. The hybrid never names a price
nobody can book as if it were bookable. A change to `courseFromPrice()` (or
a second method beside it), to the client for confirmation since it is a
price promise.

### Open

- ~~**#66** the categories of the courses that fit none~~: answered
  2026-11-03, the courses are retired (*The categories*).
- ~~**#67** the Rhino plugins as Angebote of their own, or under
  Rhinoceros~~: answered 2026-11-03, under Rhinoceros, marked *Plugin*
  (*Plugins*, below). **#68**: which other products are plugins.

### Order of the build

1. Categories, the widened `onSite()`, the derived tag.
2. The licence page, so buying keeps working once the Angebot page stops
   selling.
3. The Angebot page after `Rhino.html`.
4. `/de/angebote`, Kurse and Software under it with the switch, the nav, the
   redirects, the homepage's *Beliebte Angebote*, sitemap and search index.

## Proposed shape

*As of 2026-10-26; see the section above for what changed.*

- The Angebot keeps the software's URL, `/de/software/{slug}`, as the
  breadcrumb in the screenshot does. Nothing to redirect for the software
  pages themselves.
- A course **with** software is reached through its Angebot; its own page
  (`/de/kurs/{slug}`) stays for the detail (programme, experts, dates), linked
  from the row. *Anmelden* on the row goes straight to the booking of that
  date.
- A course **without** software is a tile on *Alle Angebote* that links to its
  course page (#50).
- *Beliebte Angebote* is the featured software only; the course `featured`
  flag stops feeding the homepage (#55).

## Questions (in `Open-Questions.md`)

- **50.** Courses with no software: a tile linking to the course page, or an
  Angebot page of their own?
- **51.** Does the course detail page stay, and what does a row show for a
  course with several Veranstaltungen: one row per date, or the next date?
- **52.** *Kurs ab CHF*: from every published course, or only those with an
  upcoming date? And *auf Anfrage* when neither?
- **53.** The nav: *Angebot* (review marker 8), or *Kurse* and *Software* as
  the screenshots have it? Does `/de/kurse` stay as a list next to
  `/de/angebote`?
- **54.** Who writes *Mehr über …*, the licence features and the leads for
  the 39 licence products? The import has none of it.
- **55.** *Beliebte Angebote*: featured software only, or do featured courses
  without software appear too?

## Built ahead of the layout — 2026-10-26

What the pages will draw, whatever #50–55 decide about how. Nothing on the
public site changed.

- **A licence's features**: `licence_products.features`, a textarea
  *Eigenschaften* in the product form, one per line
  ([[LicenceProduct::featureList]]). Empty for all 39 products: the import
  has none (#54).
- **A licence sold elsewhere**: `software.licence_note`, *Lizenz-Hinweis* in
  the software form (*Lizenz direkt bei Adobe*). **It has nowhere to show
  yet**: a software is on the site only with a listed licence of its own
  (`Software::onSite()`), so After Effects has no page and no tile. Which
  software gets an Angebot without a licence is part of #50.
- **The Vorhaben chips**: [[Software::projects]], the published Vorhaben a
  software is picked for, in their order. Edited where it was, on the
  Vorhaben.
- **Kurs ab CHF**: [[Software::courseFromPrice]], the lowest fee of a
  Veranstaltung that can be booked (published, not cancelled, ahead), of a
  published course for the software; a free one does not count. Null means
  *Kurs auf Anfrage*.

  **On the dev data that rule says *auf Anfrage* for 16 of 17 software**:
  only Rhinoceros (CHF 949) has a course with a date ahead. Twinmotion's
  course is published at CHF 499 but has no Veranstaltung. The screenshot's
  *Kurs ab CHF 590* is what the course's own fee would give, so #52 is worth
  settling before the tiles are drawn: by date, the homepage mostly reads
  *auf Anfrage*; by course fee, it shows prices for courses nobody can book.

The textarea in the dashboard learned the `hint` line the text field has.

## As built: steps 1 and 2, 2026-10-29

**Step 1, the data under the two levels.**

- **An Angebot is a software with a listed licence or a published course**
  ([[Software::scopeOnSite]], widened). On dev data 25 Angebote, up from 17:
  Blender, SketchUp, Photoshop, After Effects, DaVinci, pCon, Premiere and
  Godot came on as *Kurs*. Their page has no *Lizenzen* collapsible and
  their card no *0 Produkte*. **The tag is [[Software::offer]]**, derived;
  `withOffer()` loads it for a list in one query.
- **Licence products have categories** (`licence_product_category`, the
  *Kategorien* checkboxes in the product form).
- **`catalogue:categories`** ([[SeedCategories]]) puts the client's five in
  place of legacy's three and seeds a first assignment from
  `database/data/categories.json`: each software as the 2026-09-23 mockup's
  *Alle Angebote* sorts it (the tools it does not show by their kind: Photoshop,
  DaVinci, MyArchitectAI, Archicad, 3ds Max, Maya, ChatGPT, Perplexity,
  Midjourney; Substance 3D left out), a product its software's unless the
  file names it (the Rhino plugins), a course all its software's. Only the
  three named under `retire` go, so a category VIAK adds (#66) survives a
  rerun, and so does whatever already has one. **A cutover step** after the
  ports (`Deploy.md`), because `port:courses` brings the three back.
  On dev data it reports exactly the 8 courses of #66.
- The *Gestaltung & Bearbeitung* of the client's list, not the mockup's
  *Bildgestaltung*.

**Step 2, the licence page**, `/de/angebote/software/{slug}`
([[LicenceController]], `site/licences/show.blade.php`): on its final URL
already, under the segment `offers` (`angebote`). Drawn as the software page:
the software's picture (a product has none), title, *von* maker, *Alles zu
Rhinoceros* (a back-link pointing right) to the Angebot, description and
features as ticks on the right, then *Lizenzen*: `card/product.blade.php`
with `bare`, which drops the card's head. A 404 unless the product is listed
and its software published. On the software page the product's name now
links there; the site search's licence hits and the sitemap point there too.
The Angebot page still sells as well until step 3 redraws it.

## As built: step 3, the Angebot page, 2026-10-29

`site/software/show.blade.php`, still at `/de/software/{slug}` until the URLs
move (step 4). The mockup's content in the course page's look; the reasons
are in the view's comment.

- **The hero** as before, with the **Vorhaben chips** under the text (teal
  outline, filled on hover), each linking to its Vorhaben. The mockup's
  anchor links and breadcrumb are not built.
- **Kurse | Lizenzen side by side from `lg`**, one collapsible each, courses
  first (on a phone courses above licences, as the client wrote). Either
  alone takes the full width (Blender).
  - A **course row** (`_course.blade.php`): title and price, one line of
    facts from its next bookable Veranstaltung (*2 Tage, Visualisierungs-
    Akademie, Zürich · 16. bis 17. November 2026 · Anmeldung bis 2.
    November · 1 weiteres Datum*), *Anmelden* to the course page. The
    deadline only while ahead. **Without a date**: the course fee, *Daten
    auf Anfrage*, and *Zum Kurs* instead of *Anmelden*. One row per course
    (#51).
  - A **licence row** (`_licence.blade.php`): product, *ab* its cheapest
    listed licence, the features as ticks (the maker while there are none,
    which is all 39 today, #54), *Lizenz kaufen* (outline) to the licence
    page.
  - Under the courses *{Angebot} für dein ganzes Team? Firmenschulung
    anfragen*; no *exkl. MWST* there, courses carry none. Under the
    licences *Alle Preise exkl. MWST*.
- **Mehr über {Angebot}** is the former *Detailbeschrieb*, with the further
  images and their captions beside it (the first image is the hero's).
- Then *Weitere Informationen*, *Kundenmeinungen*, and **the recommendations
  moved below them** (they stood above the copy); the browse pair says
  *Weitere Angebote*. The phone header reads *Angebote*.
- **Nothing is sold on the page any more**: the basket dialog left with the
  product cards.

Not built, from the mockup: an intro line over each column (*Mit Tom
Pawlofsky…*, *Rhino 8 für Windows und Mac…*); it would need two fields on the
software. Worth it if VIAK will write them.

## As built: step 4, the catalogue under Angebote, 2026-10-29

**The URLs** are the table in *The nav, the URLs*, built: [[SiteUrl]] makes
them all (`offers()`, `courses()`, `course()`, `software()` for an Angebot,
`softwareIndex()` and `licence()`), so mails, search, sitemap and cards
moved with it. **The 301s are one hop each**: `/de/kurse` (query kept),
`/de/kurs/{slug}`, `/de/kurs/{slug}/{uuid}`, and the prototype's
`/de/software` and `/de/software/{slug}`. `port:check-urls` now fails a
chain. An Angebot's route comes last and refuses `kurse` and `software`;
[[Software::freeSlug]] skips both (a software called *Software* gets
`software-2`).

**The menu** is *Angebote · Über uns · Kontakt*, back in legacy's `span-6`
now that it is three links again. *Angebote* lights on every catalogue page.
On a phone Kurse and Software are rows of their own under it, a size down
and regular.

**The switch** (`components/offers/switch.blade.php`): *Alle Angebote ·
Kurse · Software* **on Kurse and Software, at the top of the filter column,
drawn as the filter's category rows** under a heading *Angebot* as
*Filter* has one, the rows *Alle · Kurse · Software*, the open one bold (Marcel,
2026-10-29, after a first version as a bold row over all three pages).
**Alle Angebote has none**: its intro links *Kurse* and *Software* instead.

**Alle Angebote** (`/de/angebote`, [[OfferController]]): the top drawn as a
Vorhaben's, a short intro linking Kurse and Software, then per published
category in its order a teal heading (no collapsible, no count: Marcel), small tiles four across from
`lg` (`card/offer.blade.php`: title, tag, arrow; no picture). A tile is an
Angebot or a course no Angebot holds, under every category it has; what has
none goes under **Weitere**, last (on dev: Visual Facilitator 1–5, LEGO
1–2, Urban Sketching, #66; retired 2026-11-03, so *Weitere* goes once VIAK
unpublishes them). No *in Vorbereitung* tiles.

**Software** (`/de/angebote/software`) lists **licence products**, not
software: `card/licence.blade.php` (the software card one level down, the
software's picture, the overlay naming maker, Angebot and *ab* price), A to
Z, with the old filter moved to products ([[LicenceFilter]], was
`SoftwareFilter`). **Kurse** is unchanged but for the switch; its category
filter follows the client's order now, as the Software one does.

**A course page links to its Angebote** under the title (*Alles zu
Rhinoceros*, one per Angebot, none without), as the licence page does.

**The homepage's *Beliebte Angebote*** (`landing/_featured.blade.php`,
`card/angebot.blade.php`): the mockup's paired tiles in the course card's
frame (category, title, square picture, the lead, then *Kurs ab* and
*Lizenz ab* under a grey rule at the tile's foot), and *Alle Angebote von A
bis Z* beside the heading. It reverses the one grid of 2026-10-09.

- **Which Angebote** ([[Software::scopePopular]], #55, my proposal): flagged
  *Beliebt*, **or holding a flagged course**, so the course flags VIAK set
  keep counting. On dev data exactly six, as in the mockup: Rhinoceros,
  SketchUp, Photoshop, After Effects, Twinmotion, Enscape. A flagged course
  no Angebot holds keeps its course card after them.
- **Kurs ab** is the hybrid of #52 ([[Software::courseFrom]]): the bookable
  price, else the lowest course fee with *, Daten auf Anfrage*, else *Kurs
  auf Anfrage*; nothing on an Angebot without courses. **Lizenz ab**
  ([[Software::licenceFrom]]) the cheapest listed licence, else the
  *Lizenz-Hinweis*, else nothing. ~~Rhino reads *Lizenz ab CHF 280.00*:
  that is Bongo, a plugin under it~~: since #67 its own licences come
  first, and Rhino reads 550.
- An Angebot without a picture of its own takes its first course's (on dev,
  SketchUp, Photoshop, After Effects).

**Step 4 is done.** Open: #68 (#66 and #67 answered 2026-11-03), the client's confirmation of #52 and of
the *Beliebt* rule (#55), and #54 (copy: features, leads, *Mehr über*).

**Revised the same day (Marcel):** the Vorhaben chips came out of the hero;
**Kurse and Lizenzen are no longer side by side** but one full-width
collapsible each, Kurse first, their rows drawn as the course page's date
rows (`card/event.blade.php`): three `span-4` columns, the name with its
date or maker; where, how long, *Anmeldung möglich bis* while ahead and how
many more dates, or the features; the price and the button. And no *Alle
Preise exkl. MWST* under the licences.

**And again (Marcel):** under *Kurse* **every date is the course page's own
row** (`card/event.blade.php`: dates and times behind the heart, place,
expert, deadline, state, fee, *Buchen*), with only the course's name over
it, teal and linked (`titled`). A course without a date shows its name and
*Neues Kursdatum folgt in Kürze*, as its page does. **Nothing is booked
there**: the row has no heart, no *Anmeldung möglich bis*, no *Kurs
offen, wird bestätigt* (*Kurs findet statt* stays), and *Details* (the same button) leads to the
course page where *Buchen* stands, as the client wrote.

### Plugins (#67, built 2026-11-03)

**The client, 2026-11-03:** the plugins stay bound to the Angebot they run
in, as in the current shop (3d-software.ch, Rhinoceros, tabs *Produkte* and
*Plugins*), and a licence product is tagged **Software** or **Plugin**.

- **The mark**: `licence_products.plugin`, a checkbox *Plugin* in the product
  form's last row, beside *3-Jahreslizenz auf Anfrage* and *Publizieren*.
  Default Software. A yes/no, as there is no third kind.
- **The data**: `database/data/licences.json` carries `plugin` per product,
  true for the six under Rhinoceros (Bongo 2, VisualARQ 3, Lands Design 6,
  Karamba3D PRO, MatrixGold, Drakon); `licences:import` sets it on create
  and on `--refresh`, and the migration sets the same six on a database that
  already holds the catalogue (staging). The rest are #68, VIAK's to tick.
- **The Angebot page**: *Kurse · Lizenzen · Plugins*, each its own
  collapsible, the plugins in the same rows (`_licence.blade.php`). No
  *Plugins* without one; an Angebot with only plugins shows only *Plugins*.
- **The Software list's tile**: *Software* or *Plugin* before the category
  over the title (*Plugin · Architektur & Interior Design*), and the overlay
  says *Plugin für Rhinoceros*.
- **Lizenz ab** ([[Software::licenceFrom]]) counts the Angebot's own
  licences first, a plugin's only where it has none: Rhino 550, not Bongo's
  280. (Rhino's 90 is the hidden EDU Upgrade, so 550 is right.)
- **No cross-listing** (Marcel, 2026-11-03): 3d-software.ch's *Plugins* tab
  also lists V-Ray, Enscape, Lumion and Twinmotion under Rhino. Here those
  are Angebote of their own and stay on their own page only; a product is
  listed once, under its software. Showing one under a second Angebot would
  need its own setting, and nobody has asked for it.

### The Rhino texts (#70, 2026-11-03)

The client's `00_fertig_Webseitentexte_Rhino.docx` (#54's content, for Rhino
first) checked against the structure. **It fits on the Angebot page and the
course pages almost field for field**: H1, text, tags = title, Kurzbeschrieb,
categories; *Rhino lernen* / *Rhino Lizenzen* = *Kurse* / *Lizenzen*;
course Sublead = Subtitel, Facts = the three facts (dev data already split
Kursziel, Kursinhalt, Voraussetzungen), *Weitere Informationen* =
`information_booking`. Where it does not, Marcel's mail proposes:

- **One page per licence** (Rhino 8, Upgrade, EDU, Update EDU): keep one page
  per product, the subleads as the variants' notes. **EDU public contradicts
  #1/#36** (EDU hidden, entered by VIAK): asked. No field for
  *Systemvoraussetzungen*.
- **A page *Rhino Lizenzen***: none; its sublead as an intro to *Lizenzen*.
- ***Was ist Rhino?* as a subpage**: it fits *Mehr über* (Detailbeschrieb);
  a page only for SEO.
- **New, optional**: Zielgruppen (into the Kurzbeschrieb), an intro line,
  section intros for Kurse and Lizenzen, a quote box. Not built.
- ***Gruppengrösse*** is a fourth fact: into Voraussetzungen, or a fourth field.
- No *Plugins* in the document: it predates #67; the section stays.

### The website texts (#70 answered, 2026-11-06)

Oliver's answer to the mail above, with 30 docs in `.rework/content/`
(`00_fertig_Webseitentexte_*.docx`, superseded versions in `Archiv/`); more
(the KI courses, the plugin list, the software list, the old sitemap) promised
the week of 2026-11-09.

- **EDU licences are public**, reversing #1/#36 ("we adjust later if needed").
- **Gruppengrösse is a fourth fact.** Every course doc fills it.
- **The new optional Angebot fields are wanted**: Zielgruppen, an intro line,
  section intros for Kurse and Lizenzen, a quote box. Every overview doc has
  them in the same order.
- ***Was ist …?* is a page of its own** per Angebot (for Google), linked from
  the Angebot page: title, intro, *Woher kommt …?*, *Für wen ist …?*.
- **A licence page per Angebot** (`Software/X`), not per licence and not per
  product: each licence an H3 with text, price, CTA, the Systemvoraussetzungen
  at the foot. It would replace the product page with the dropdown. Per-licence
  texts are all placeholders. Asked as #71.
- **The docs go beyond the answer**: plugins as Angebote (#72, against #67);
  Angebote without software and Maxon One split from Cinema 4D (#73).
- The tags in the PS are the five already seeded; the docs' per-Angebot tags
  differ from `categories.json` in places (Anima, formZ, Lumion, ZBrush,
  Cinema 4D, Veras) and are to be taken from the docs.
- Every course doc repeats one fixed paragraph about Nightnurse Images: a
  template block, not a field.

Reply with #71–73: `~/Desktop/Webseitentexte-Antwort-Oliver.txt` (2026-11-09).

### As built: what the website texts settled, 2026-11-09

Marcel sent the reply with #71–73 the same day and asked for the settled
part to be built.

- **Five optional Angebot texts** (`software.audiences`, `intro`,
  `courses_intro`, `licences_intro`, `quote`; plain text, translatable),
  in the dashboard form after *Kurzbeschrieb*. On the page: *Zielgruppen:* and
  the intro under the hero's text; a paragraph heading *Kurse* and *Lizenzen*
  (8 of 12 columns); the quote box after the licences and plugins, in the
  testimonial card's frame (1px teal, 8/16px inside), teal text. The
  collapsibles keep their names (*Kurse*, *Lizenzen*), not the docs' *Rhino
  lernen*, *Rhino Lizenzen* and their *Schulung* / *Software* eyebrows.
- ***Was ist …?* is a page**: `/de/angebote/{slug}/was-ist-{slug}`
  (`SoftwareController::about`, `site/software/about.blade.php`), its body
  the *Detailbeschrieb*, relabelled *Was ist …?* in the form (no new column:
  only Rhino had one, our filler). Not found while empty; a wrong second slug
  301s. The Angebot's image on top, then the text page layout, the heading
  and a back link to the Angebot in the aside, further images under the text.
  Meta description = the text's first 155 characters. The Angebot page lost
  *Mehr über …* and links *Was ist {Angebot}?* from the hero. The editor has
  no headings, so the docs' H2s are bold lines; the editor got a hint line.
- **Four facts**: `facts.3` in the course form; the page lays four out two by
  two from `sm`, four abreast from `lg`; three stay as legacy's three columns.
  Existing courses keep *Gruppengrösse* inside their third column until their
  texts are entered.
- **EDU public**: migration `list_edu_licences` lists RHN-1091 (*EDU*),
  RHN-1092 (*EDU Upgrade*), RHN-2008 (VisualARQ *Educational*), and
  `licences.json` with it. Lab licences and the Education Collections stay
  unlisted (a school's order, not a student's). That made Rhino read *Lizenz
  ab CHF 90* (EDU Upgrade). **Settled the same day (Marcel):** a variant has an
  *Ausbildungslizenz* checkbox (`licence_variants.education`, set by migration
  on the 12 EDU, lab and university SKUs and in `licences.json`, badge
  *Ausbildung* in the dashboard list), and `LicenceProduct::fromPrice` leaves
  those out unless a product has nothing else. Rhino reads 550 again.
- **Tags from the docs**: `categories.json` updated for Anima, CINEMA 4D,
  Lumion, Premiere Pro, Unreal Engine, Veras, ZBrush, formZ;
  `catalogue:categories --sync` lets the file win on a database that has an
  assignment (run on dev 2026-11-09; staging step in `Deploy.md`).
- `SoftwareShowcaseSeeder` now writes the client's Rhino copy (not ours).

Not built, waiting on #71–73: the licence page per Angebot, Systemvoraussetzungen,
plugins and topic Angebote, Maxon One. The texts of the other 29 docs are not
entered; an import from the docx is possible once the structure settles.

### The form, by page (Marcel, 2026-11-09)

The software form had eleven texts in one run, for two pages (three if #71
is yes), with nothing saying which. **Grouped by the page each fills, in its
order**, with the collapsible sections the form already had:

- open at the top, the Angebot page's header: Titel, Kurzbeschrieb,
  Zielgruppen, Einleitung;
- **Seite «Angebot»**: Einleitung Kurse, Einleitung Lizenzen,
  Lizenz-Hinweis, Zitatbox;
- **Seite «Was ist …?»**: the text;
- then Einstellungen, Empfehlungen, Bilder, SEO as before.

**Revised the same day (Marcel): one section per page.** The header fields
sat above *Seite «Angebot»* though they fill that page too, so they moved in:
only *Titel* stands above (it names the record everywhere), then *Seite
«Angebot»* with Kurzbeschrieb, Zielgruppen, Einleitung, the two section
intros, Lizenz-Hinweis and Zitatbox, then *Seite «Was ist …?»*. A *Seite «…
Lizenzen»* section follows the same rule if #71 is yes.

Every section shut on load, Einstellungen included (Marcel, same day). Each page's section opens with *Seite ansehen*, a link to the page as saved, in a new tab (`preview` on the section, the resource's `url` / `about_url`); the public page needs the Angebot published.

**Subtitel and Weitere Informationen left the form**: the client's texts have
neither, and no Angebot held either but our Rhino filler. Their columns stay,
a save does not touch them, and the page still shows what they hold. A
*Seite «… Lizenzen»* section (intro line, Systemvoraussetzungen) comes with #71
if the answer is yes. Tabs and a screen per page were weighed and left: the
dashboard has no tabs, and one record wants one save.

### The texts for every Angebot (2026-11-10)

Rhino was the only page with the client's texts; the rest of the docs went in
the next day (Marcel). **`catalogue:texts`** writes them from
`database/data/texts.json`, read out of `.rework/content/` once (a script,
not kept: the docs are not in the repo). Per Angebot the page's Kurzbeschrieb,
Zielgruppen, Einleitung, the two section intros, Zitatbox, and *Was ist …?*
(its H2s as bold lines, as Rhino's). Course pages and licence pages in the
docs are not read.

**22 Angebote plus Rhino.** Seven docs wait: Bongo, Karamba3D, VisualARQ
(plugins, #72); Maxon One, Architekturfotografie, Architekturvisualisierung,
KI-Video (no record, #73). Doc names map to titles by hand (Drakon3D →
Drakon, Corona → Corona Renderer, SketchUp → SketchUp Pro).

**The client's en dashes stay** (52 of them): their copy, verbatim, and the
no-long-dash rule is about the em dash in our text. Rhino's were rewritten by
us on 2026-11-09 and the command does not touch a filled field, so Rhino
keeps that version.

Veras's doc names *Enscape lernen* over its course intro; the intro itself is
about Enscape with Veras, taken as written.


## The answers of 2026-11-12, and the plan from them

Oliver answered #68 and #71–73 by mail, with two attachments: the reworked
software list (`Software_Lizenztypen_claude.xlsx`, one row per article number,
with *Produktart* Software/Plugin, *Frontend Sichtbarkeit* Y/N and a
*Bemerkung*) and the website texts again (4 new docs, 2 changed). The answers,
and the questions they leave (#75–79), are in `Open-Questions.md`.

**What changes, in the order it is built:**

1. **The software list.** Rhino 9 replaces Rhino 8; the Rhino 9 Upgrade is a
   promo, CHF 360 *statt* 550, so a licence gets a **Streichpreis**. Old
   products stay, hidden. Maxwell is deleted. EDU hidden again as the list
   says (#75). RHN-2042 is Drakon's demo, MatrixGold RHN-2043. Every other
   price in the list is the one we have. The Produktart tags wait for step 4.
2. **Hidden work in progress** (data): ComfyUI, D5 Render, FigmaWeave and
   Photoshop-KI are new Angebote, created unpublished with their texts. The
   new *SketchUp + D5 Render* and *Enscape & Veras mit Archicad* courses are
   created unpublished; *Visualisieren mit SketchUp* and *Enscape für
   Archicad* stay online until they replace them (#76). *DaVinci Resolve
   Einführungskurs* is unpublished, which takes the DaVinci Angebot off the
   site with it: an Angebot without a listed licence shows only while it has
   a published course ([[Software::scopeOnSite]]), the coupling Oliver asked
   for. 3ds Max, Substance 3D, ChatGPT, Perplexity and Midjourney are no
   Angebote on the site: legacy's program tags on offline courses.
3. **The texts**: the 4 new docs and the changed Enscape and KI-Video into
   `texts.json`; the changed ones need `catalogue:texts --force`.
4. **Plugins as Angebote of their own** (#72), replacing #67: the plugin flag
   moves from the product to the Angebot, which gets optional host programs
   (many), shown as *Plugin für …*. The Rhino plugins, Rhino.io, V-Ray (with
   Phoenix, Scans, render nodes), Corona, Enscape, Veras. Lumion View is #77.
5. **The licence page per Angebot** (#71): one page per Angebot, a block per
   licence (title, optional note, price, button), Systemvoraussetzungen once
   per Angebot. The note is the list's *Bemerkung* (#79). The 3d-software.ch
   redirects then point at the Angebot's licence page.
6. **Topic Angebote** (#73): Architekturvisualisierung, Architekturfotografie
   (*Gestaltung & Bearbeitung*), KI-Video, tagged *Kurs*, their courses moved
   off Photoshop / After Effects / Premiere; Maxon One out of Cinema 4D.

### As built: step 1, the software list (2026-11-12)

Migration `2026_11_12_000002_software_list_of_2026_11_12` on a database that
has the catalogue; `licences.json` and `categories.json` the same for a fresh
import (`licences:import` on the migrated dev data creates nothing).

- **Streichpreis**: `licence_variants.compare_price`, nullable, in the variant
  form as *Streichpreis CHF* (must be above the price), a *statt CHF …*
  badge in the product's list. The licence row shows it struck through after
  the price, grey (`card/product`). Display only: the basket and invoice
  charge the price. No percentage, no badge: the old shop's *%* box and
  *gespart* were not taken.
- **Rhinoceros 8 → Rhinoceros 9**, renamed with its URL
  (`/de/angebote/software/rhinoceros-9`; nothing is live yet). RHN-1003
  Vollversion 930 and RHN-1013 *Upgrade (Promo)* 360 statt 550 added; Rhino
  8's two stay in the product as *Rhino 8 Vollversion* / *Rhino 8 Update*,
  unlisted (pickable in a manual order). One product, not a hidden second
  one. The Angebot's SEO filler says Rhino 9. *Ab CHF* on Rhino now reads 360.
- EDU unlisted again (RHN-1091, 1092, 2008) as the list marks them (#75).
- RHN-2042 Drakon *Demoversion* (0, listed), MatrixGold RHN-2043.
- **Maxwell soft-deleted** (Angebot, 5 products, 13 licences). Its hosts
  (Archicad, Maya: unpublished programs) stay.
- The Produktart tags are not set: they belong to the plugin move (step 4).

### As built: steps 2 and 3, the unfinished Angebote and the texts (2026-11-12)

**`catalogue:offers`** ([[ImportNewOffers]], `database/data/offers.json`),
rerunnable like the other catalogue commands, a cutover step after the ports:

- **ComfyUI, D5 Render, Figma Weave** created as Angebote, unpublished.
  Their categories in `categories.json`, their texts in `texts.json`.
- **Five courses, unpublished, fee 0** (#80), one per *Schulung* page of the
  new docs and the changed Enscape doc: the H1 split at its colon into title
  and subtitle (*SketchUp & D5 Render* / *Vom 3D-Modell zum Hingucker*), the
  Sublead as the short description's first paragraph, the four facts as the
  course form has them (*Kursziel*, *Kursinhalt*, *Voraussetzungen*,
  *Gruppengrösse*), *Weitere Informationen* with *Melde dich gerne bei uns.*
  and *Ruf uns an.* linked to Kontakt. Software and categories from the doc:
  D5's course under D5 Render and SketchUp Pro, Enscape & Veras under both.
  *Visualisieren mit SketchUp* and *Enscape für Archicad* stay online
  untouched. The Enscape course is named *Enscape & Veras* from the doc's H1
  (#76 asks whether that is the one Oliver meant).
- **DaVinci Resolve Einführungskurs unpublished**; the DaVinci Angebot left
  the site with it, no code needed. Legacy has the course published, so the
  command repeats this after the ports.
- **Photoshop stays**: the Photoshop-KI doc is the Photoshop Angebot's page,
  now filled (it had no texts), and its new course *Rendering-Finish mit
  Photoshop & KI* is unpublished. Photoshop's three published courses move to
  the topic Angebote in step 6; then it has nothing published and leaves the
  site by itself, which is the "ganzes Angebot ausblenden" Oliver asked for.

The docs are read by a script in the scratchpad (not kept), as for
`catalogue:texts`. Note: they separate key and text with U+2028, which
Python's `splitlines()` treats as a line break.

Not yet read: the changed **KI-Video** doc (step 6, its Angebot does not
exist yet). The Enscape doc's Angebot part is unchanged. Two typos in the
Enscape course copy kept verbatim for Oliver to fix: *Hostsoftwar*,
*kennenlerne*.

### As built: step 4, plugins as Angebote (2026-11-12)

#72, replacing #67's build. Migration `2026_11_12_000003_plugins_as_angebote`;
`licences.json` (group keys `plugin`, `runs_in`), `categories.json` and
`texts.json` the same for a fresh import.

- **`software.plugin`** and **`software_host`** (*Läuft in*,
  [[Software::hosts]] / [[Software::plugins]]): on the Angebot form, *Plugin*
  in *Einstellungen* and a shut *Läuft in* section of checkboxes, as the
  product form's *Hostsoftware*. An Angebot is never its own host.
  `licence_products.plugin` is dropped; the product form lost its tick.
- **The product's *Hostsoftware* stays what it was**: the program an order
  must name (the RealFlow Plugin). Not the same thing as *Läuft in*, and the
  hints on both forms say so.
- **Moved into Angebote of their own**: Bongo, VisualARQ, Lands Design,
  Karamba3D, MatrixGold (all *Läuft in* Rhinoceros), Drakon (had an Angebot
  for its courses; now holds its product too), Rhino.io (*Läuft in* CINEMA
  4D). Each took its product's categories. Bongo, Karamba3D, VisualARQ took
  their docs' texts (the plugin docs carry a licences intro where course docs
  carry a course intro). Lands Design, MatrixGold, Rhino.io have no doc.
  None has an image: grey until VIAK uploads one (#69's answer).
- **Ticked *Plugin*, no host**: V-Ray, Corona Renderer, Enscape, Veras.
  *Chaos Vantage* is a program, not a plugin, but sits under V-Ray, so its
  tile reads *Plugin* too (#77).
- **On the site**: a plugin's page says *Plugin für Rhinoceros* under its
  title, each host linked (only hosts on the site), or *Plugin* alone. A
  host's page lists under *Plugins* the products of the plugin Angebote on
  the site that run in it, in the rows Marcel approved for #67. Tiles say
  *Plugin* / *Kurs & Plugin* ([[Software::offer]]); a product tile's overlay
  *Plugin für …* from its Angebot's hosts. *Ab CHF* on an Angebot is its own
  products only (Rhinoceros never read Bongo's anyway).

### As built: step 5, the licence page per Angebot (2026-11-12)

#71. Its order is the client's docs' (`../Software/Corona`): *{Angebot}
Lizenzen* and the *Sublead*, a block per licence (title, text, price, CTA),
*Systemvoraussetzungen*, the page's own Zitatbox.

- **`/de/angebote/software/{angebot}`** ([[SiteUrl::licences]],
  [[LicenceController::show]]), there while the Angebot sells something
  (`Software::sellsLicences`). It replaces the page per product: a
  product's old URL answers **301 to its block**, `…/{angebot}#{product}`
  ([[SiteUrl::licence]] now takes the product). An Angebot wins a slug both
  have; V-Ray's product is V-Ray's block anyway. The sitemap lists the
  Angebote' pages, not products.
- **Product by product, in the card staging had** (`card/product`): name and
  maker as heading, description, *3-Jahreslizenz auf Anfrage*, then one row
  per licence (title, platforms, **the licence's text**, host select, price
  with Streichpreis, *Anzahl*, *In den Warenkorb*). The article carries the
  product's slug as `id`. The heading no longer links (it is the page).
  Grouping by product kept on purpose: V-Ray's 13 licences read as 6 groups.
- **The licence's text** is the variant's *Hinweis*, widened from a line
  (255) to a paragraph (1000, textarea), shown plain under the title with
  its line breaks; it was italic, a remark. The list's *Bemerkung* was
  already in it where it is a note (*Aktivierung auf max. 2 Computern*,
  *Inkl. 5 Simnodes*); *Mindestens 3* is the minimum, *3-Jahreslizenz* the
  product's flag. Everything else waits for #79.
- **New Angebot fields**, in a form section *Seite «Lizenzen»* with *Seite
  ansehen*: `licences_lead` (*Einleitung*), `requirements`
  (*Systemvoraussetzungen*, rich text, empty in every doc so far:
  placeholders), `licences_quote` (*Zitatbox*). `catalogue:texts` fills lead
  and quote for the 20 Angebote whose doc has a licence page.
- The title is *{Angebot} Lizenzen* from the record, not the doc's H1 (*Chaos
  Corona Lizenzen*, *Rhino Lizenzen*): one rule, no field.
- Everything that linked a product page now links its block: the Angebot
  page's rows, the Software list's tiles, the search, the 3d-software.ch
  redirects. Those redirects' fallbacks for the plugins now name the
  plugin's own Angebot (Bongo, Drakon, Karamba3D, Lands Design, VisualARQ,
  Rhino.io); `port:check-shop-urls`: 202 paths, all one hop.
