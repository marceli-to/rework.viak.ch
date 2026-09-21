# 09 — The public site (in progress)

The current site, rebuilt on the new stack. **Not a new design** — that rule and
its reasoning are in `00-foundation.md` under *Parity means the current design*.

## Status

Started 2026-09-18. The shell and the course pages are built and match
production; everything else is listed under *What is left*.

| | |
|---|---|
| Stack | Blade + Alpine + Tailwind, no Vue on these pages |
| Reference | the live site **plus** `../viak.ch/resources/sass/` |
| Conventions | `resources/css/README.md` — read before writing a class |

### Built

- **URLs.** `/de/kurse`, `/de/kurs/{slug}`, a 301 from the legacy uuid form, `/`
  → `/de`. Segments come from `config/site.php`, so `/en/course/{slug}` exists
  the moment a locale is added.
- **Head.** Title as `page • Visualisierungs-Akademie`, canonical, hreflang, og
  tags, and the five tags the live site sends that an earlier pass had missed.
- **Shell.** `body` as the container, the 12-column header with its rule, the
  logo, the nav, the basket and profile icons. No footer — the live site has
  none except on the homepage.
- **Course list and card.** The outer grid, the card with its hover overlay, the
  teal promo box, and **the whole filter** — all seven of legacy's attributes,
  hiding and showing cards rather than reloading the page. See *The filter
  renders everything and hides the rest*, below.
- **Mobile chrome.** The teal menu panel, the bottom-right burger, the
  full-screen filter panel.
- **Media.** 306 ported images rendering through Glide as AVIF/WebP with a JPEG
  fallback.

### What is left

Roughly in the order that unblocks the most.

1. **Basket and checkout.** The one with money in it, and the reason this track
   was chosen — chunk 06 has 251 tests and has never run in a browser. The shape
   is decided: **a POST per step with the state in the session**, because the
   server is the pricing authority (`06-bookings.md`). This is what tests the
   Alpine decision.
2. **Register, login, password reset.** Fortify is installed and `User`
   implements `MustVerifyEmail`; the screens are not built.
3. **The two portals** — *Meine Kurse*, *Meine Dokumente*, the expert's course
   view. Every endpoint exists (`08-accounts.md`); only the screens are missing.
4. **The course detail page**, which renders but has not been measured against
   `views/` in the legacy SCSS the way the list has.
5. **Experten, Kontakt, Firmenschulung, the homepage** — chunk 04's pages. The
   nav lists Experten and Kontakt pointing at `#` until they exist.
6. ~~The rest of the filter.~~ **Done 2026-09-21** — all seven attributes, the
   three categories as links and the other six as selects.

## The filter renders everything and hides the rest — decided 2026-09-21

The listing used to filter in SQL, so every change of filter was a navigation.
On a phone that is the bug: the filter is a *full-screen panel*, and the
navigation closes the panel you are still using, one attribute at a time.

Legacy solved it with the Vue island (`frontend/filter/Index.vue`, 395 LOC,
POSTing `/api/course/filter` per click, the card markup living a second time in
`filter/components/Card.vue`). The stack here already answers it: **the
controller renders all 32 courses and the query string decides which carry
`hidden`**, and Alpine then owns that same class. Nothing new was installed —
Livewire would have meant a round trip per click to filter rows already in the
browser, plus its own copy of Alpine on top of the dashboard's Vue.

What it keeps, which is the whole reason the query string was there:

- **No JavaScript still filters.** The `href` is a real link to the view it
  selects; the `@click.prevent` beside it is what a browser runs.
- **A filtered view is still linkable** — Alpine writes the query string back
  with `replaceState`, and a cold load of that URL renders the same page.
- **A crawler now sees the whole catalogue** on `/de/kurse` rather than a slice.

And it gives desktop legacy's immediacy back: clicking a filter there had also
become a page load, which legacy's never was.

Three things to know before extending it:

- **One definition of a match, twice.** `CourseController::index()` decides it
  in PHP and `course-filter.js` decides it in the browser, over the same uuids —
  the card carries them in `data-facets`. Change one rule and change the other.
- **`::class` on a component tag.** Blade reads a single leading colon as a PHP
  expression, so the Alpine binding on `<x-site.course-card>` needs two.
- **Alpine's `:class` object form removes a class the server put there**; the
  string form only manages what Alpine itself added. The first is why
  `{ hidden: … }` can undo a server-rendered `hidden` and `open ? '' : '…'`
  could not.

**This is a 32-course list with no pagination.** Paginate it and hiding rows the
server did not send is wrong — but the query string still filters server-side,
so the way back is `index()`, not a rewrite.

## Four layout fixes from comparing against production — 2026-09-21

Marcel sent four screenshots of the live page. Everything below was measured on
it with `getBoundingClientRect`, which is the method this chunk already argues
for, and every number now matches.

**The card grid is two columns on a phone.** `frontend/filter/Index.vue` writes
`class="card-teaser span-6"` with **no breakpoint prefix**, so it is 6-of-12 at
every width. The rebuild read it as `col-span-12 sm:col-span-6`.

**The grid gap grows on both axes.** `grid-gap: $space-4x`, `$space-10x` from
`bp-md` — 16/16 then 40/40. The rebuild grew only the column gap. The *header*
is different and was right: legacy gives it `grid-column-gap`, column only.

**The mobile menu opens with the logo.** `menu.blade.php` puts the link before
`.site-menu__main`, inside the panel's 8px padding, and the 72px below it is the
`ul`'s own margin. The rebuild kept the margin and dropped the logo.

**The social icons are `#8C8C8C`**, hard-coded into the artwork rather than
named in `_colors.scss`. Porting them to `currentColor` — which is right for
every other icon here — turned them black. A sixth grey nothing recolours is
better left in the path than made a token.

Two more the screenshots settled, neither of them asked about:

- **`Zurücksetzen` is always shown**, with nothing chosen as much as with
  something. Legacy renders it unconditionally; the rebuild hid it.
- **`Anzeigen (32)` lost its space.** The button is a flex container, so
  `Anzeigen <span>` is two flex items and the whitespace between them collapses.
  Label and count have to be one text node.

### The trap worth the most: Tailwind pairs a line-height with every size

`resources/css/README.md` says the type scale deliberately carries no line
heights, because legacy sets them per component. **That is the intent, not the
behaviour.** Redefining `--text-lg` in `@theme` does not remove Tailwind's own
`--text-lg--line-height`, so `text-lg` was emitting **1.556** where legacy
inherits the body's **1.3**.

It is invisible wherever a box has a `min-height` — which is most of this page,
and why it went unnoticed — and visible the moment one does not: the six select
rows were each a pixel short, and the whole list sat 4px low under a heading
whose line box was 4px too big.

Fixed here by naming `leading-[1.3]` on the three places in the filter that
depend on it. **Not fixed globally**, because nulling the nine pairings moves
type on every page and wants its own pass — `grep`ping `text-` against
`leading-` across `views/site` finds 12 elements relying on the pairing today.

## The event-expert link was never ported — found 2026-09-21

`event_expert` was **empty**. `PortCourses::clear()` has always emptied it and
nothing ever filled it, because legacy calls the table `event_user` and the port
looks for it under the new name. 341 rows, 14 experts.

It failed quietly in two places: a course card's hover overlay simply omitted
*Experte*, and the filter's new Experte list came out with nothing in it. Both
degrade to "this course has no expert", which looks like data rather than a bug.

`portEvents()` now fills it, resolving legacy user ids through the uuid
`PortUsers` carries across, and reports any expert it cannot find. The dev
database was backfilled in place rather than re-ported: 325 of 341 rows, the 16
being events the port skips for their own reasons.

**Worth generalising:** a pivot that is emptied and never filled leaves no
error, no null and no missing column — just a relation that is always empty. The
other pivots deserve a count check.

## Measure the page, do not read the stylesheet

The most useful thing learned today, and it cost three corrections to learn.

Structure is readable from the SCSS. **Placement often is not**, because the
values that decide it are the ones nobody wrote down:

- The header nav is **top-aligned**, not centred. `align-items` is never stated,
  so reading gives no clue — its box is 28–54px inside a row running 28–108px.
- `max-width: 1100px` alongside `padding: 16px` means the body is 1100
  *including* the padding. Read as content width it makes the page 32px too wide.
- The container gutter **steps**: 16px on phones, 32px from 700px. Expressed as a
  max-width of `calc(100% - 32px)` plus padding, it reads like one value.
- Header icons are sized by **height**, not width. `w-16` on the profile icon is
  a quarter too big below the desktop breakpoint.

`getBoundingClientRect` over the live page settles any of these in a minute.
Every box in the header now matches production to the pixel.

## Traps worth not rediscovering

**Legacy's `%word-break` includes `hyphens: auto`.** Without it a long German
compound breaks mid-word with no hyphen — `Architekturvisualisierun|g` where the
live site gives `Architekturvisualisie-rung`.

**The basket icon is hidden when empty.** `!hide` at a count of zero, which is
why the live header usually shows only the account icon. It is not missing.

**`arrow-right` is two SVGs**, genuinely different artwork per breakpoint.
**`profile` draws a different figure when signed in.** **`cross` takes a size
argument.** All three look like bugs and are not.

**The logo gets *smaller* at the first breakpoint** — 48px, 44px at sm, 56px at
lg. Also the design's own doing.

**A green `npm run build` says nothing about a component nobody imports yet.**
Vite tree-shakes it, so four broken Vue icons compiled clean. Run them through
`vue/compiler-sfc` directly.

## Open, for Marcel

- **Experten and Kontakt** are in the nav pointing at `#`. Fine while they have
  no pages; worth a decision if that lasts.
- **`meta keywords`** is carried across verbatim. Google has ignored it since
  2009 — removing it is a decision rather than a port, so it waits.
- **`APP_NAME` is now the short name**, `Visualisierungs-Akademie`, because the
  title format needs it. Legacy keeps the legal entity —
  "Visualisierungs-Akademie Schweiz GmbH" — in `APP_NAME` for **mail**. If
  outgoing mail should say the legal name, that needs its own config key.
- **The phone layout has been seen in a browser** for the course list, at 500px
  on 2026-09-21: the panel opens, two filters in a row leave it open, `Anzeigen
  (4)` counts and closes it, and the console is clean. `resize_window` does work
  — it clamps to a 500px minimum, which is under the 700px breakpoint. The rest
  of the site still has not been eyeballed.
