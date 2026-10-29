# 14 — Angebote: Kurs and Software as one (not yet built)

One page per tool, with the courses and the licences for it side by side. What
the client asked for in the meeting of 2026-10-26, the biggest of the five
changes it raised (the others: `15-search.md`, `13-checkout.md` *Invoice only*,
`16-firmenkurse.md`, `17-mcp.md`).

## Status

**Planned 2026-10-29** from the client's answer, see *The client's answer,
2026-10-29*: two levels, the nav, the URLs and *Kurs ab* settled, #66–67 open. **Scoped 2026-10-26.** **The data built the same day** (*Built ahead of the
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

### The categories

The client's 5 replace the 3 there are (*3D-Software* 18 courses,
*Bildgestaltung & Handwerk* 7, *Management & Kommunikation* 8), on courses,
software and, new, licence products; they drive the filters on Kurse and
Software. A migration maps what it can; the rest is listed for VIAK to set.
**Visual Facilitator, LEGO Serious Play and Urban Sketching fit none of the
5** (#66).

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

- **#66** the categories of the courses that fit none, **#67** the Rhino
  plugins (Bongo, VisualARQ, Karamba3D, Lands Design, MatrixGold, Drakon) as
  Angebote of their own, as the mockup has them, or under Rhinoceros where
  the import put them. Both to the client.

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
