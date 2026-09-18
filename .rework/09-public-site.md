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
  software filter, the teal promo box.
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
6. **The rest of the filter.** Only Software is wired. Legacy also filters by
   category and by Ort, Level, Sprache, Experte and Tags; the taxonomies exist,
   nothing filters on them.

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
- **The phone layout has not been seen in a browser.** The rules are asserted in
  tests; the viewport would not resize in this session. Worth eyeballing.
