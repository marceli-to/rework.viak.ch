# 09 — The public site (in progress)

The current site, rebuilt on the new stack. **Not a new design** — that rule and
its reasoning are in `00-foundation.md` under *Parity means the current design*.

## Status

Started 2026-09-18. The shell, the course list with its full filter, and the
auth screens are built and match production. **The checkout is next and is
unblocked**; everything else is listed under *What is left*.

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
- **Auth.** Login, registration, forgot/reset password and verify-email, on
  legacy's own URLs — `/login`, `/de/registration`, `/password/reset`.
- **A form kit for the checkout to use**: `x-site.article`, `field`, `select`,
  `checkbox`, `toast`, plus `lang/de/`.

### What is left

Roughly in the order that unblocks the most.

1. **Basket and checkout — started, and this is where to pick it up.** The one
   with money in it, and the reason this track was chosen: chunk 06 has 251
   tests and has never run in a browser. Its blocker is gone — the auth screens
   are built (item 2) — and the flow is mapped in *What the checkout actually
   is*, below. The shape stays what `06-bookings.md` decided: **a POST per step
   with the state in the session**, because the server is the pricing authority.

   Next three, in order:

   1. **`Buchen` on the course detail page.** `basket.js` already has
      `add()`, `remove()`, `has()` and `setRental()`, and **nothing calls any of
      them** — the header's basket icon still points at `#`. Legacy opens a
      rental dialog first when the event offers laptops (CHF 80 excl. VAT), then
      adds and shows a toast with a link to the basket. `x-site.toast` exists.
   2. **The basket page** at `/de/checkout/basket` — the first time `PriceBasket`
      runs in a browser. It takes `<x-layout.site auth>`, and so does every page
      after it: legacy paints the whole purchase flow teal, not just the login
      (see *The teal background*, below).
   3. **The three remaining steps**, then the confirmation.

   **One bug to fix on the way:** `POST /api/basket/price` is behind
   `auth:sanctum`, but `basket.js` calls `price()` from `init()` whenever
   localStorage holds items. A guest with a filled basket therefore gets a 401
   and `this.error` set on every page load. Invisible today because nothing
   renders it; it surfaces the moment the basket page does.
2. ~~Register, login, password reset.~~ **Built 2026-09-21** — see *Fortify had
   no views at all*, below.
3. **The two portals** — *Meine Kurse*, *Meine Dokumente*, the expert's course
   view. Every endpoint exists (`08-accounts.md`); only the screens are missing.
4. **The course detail page**, which renders but has not been measured against
   `views/` in the legacy SCSS the way the list has.
5. **Experten, Kontakt, Firmenschulung, the homepage** — chunk 04's pages. The
   nav lists Experten and Kontakt pointing at `#` until they exist.
6. ~~The rest of the filter.~~ **Done 2026-09-21** — all seven attributes, the
   three categories as links and the other six as selects.

## What the checkout actually is — mapped 2026-09-21

Read before building it. Legacy's is `frontend/checkout/` — 797 LOC across four
views and an `AddressForm` — and two things about it change the estimate.

**The payment step takes no payment.** `Payment.vue` is a paragraph of text
("QR-Einzahlungsschein oder Kreditkarte, Rechnungsstellung sobald die
Durchführung feststeht") and the **discount-code field**. Nothing else. Invoices
are raised on `EventConfirmed` (`03-invoices.md`), so **there is no Stripe
anywhere in this flow** — the `PaymentController` and its checkout session are a
separate thing, for paying an invoice that already exists.

**The whole flow is behind `role:student`**, including the basket page. An
anonymous visitor can fill a basket — it is `localStorage`, not a session — and
is sent to login at the first step.

| Step | URL | What it holds |
|---|---|---|
| 1/4 | `/de/checkout/basket` | the items, priced |
| 2/4 | `/de/checkout/address` | participant address from the profile, plus an invoice address: "entspricht Teilnehmer-Adresse", a saved-address picker, or a new one. Carries the RAV explainer. |
| 3/4 | `/de/checkout/payment` | the payment-options text and the discount code |
| 4/4 | `/de/checkout/summary` | confirm |
| — | `/de/checkout/confirmation` | thank you |

`config/site.php` already has the segments (`basket` → `warenkorb`, `checkout`),
though legacy's own URLs are `/de/checkout/…` throughout.

### The server side is done and untouched

`BasketController::price()` and `::store()` exist, tested, behind
`auth:sanctum`. `CompleteCheckoutRequest` takes the selection, the code,
`total_shown` and an optional `invoice_address`, and **refuses a checkout whose
total moved**. The Blade steps should call the same `PriceBasket` /
`CompleteCheckout` Actions rather than the API — `00-foundation.md` settled the
flow as "Blade's shape, not a client-held wizard's" — and the API endpoints stay
for the basket's live pricing, which `basket.js` already uses.

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

### Three more from the same comparison — 2026-09-21

**The filter has one control, not two.** `.icon-filter` is a single **22×22**
button at `position: fixed; top: 26px; right: 16px; z-index: 101`, and
`shared/components/ui/icons/Filter.vue` swaps its path on an `active` prop — the
funnel when the panel is shut, the cross when it is open. At the top of the page
it lands beside the page title, which is what made an earlier pass read it as
part of the header: it put a trigger in a header slot and gave the panel a
second, `large` (31×30) cross of its own. That second cross sat **in the flow**,
so it also pushed `Filter` from legacy's 88px down to about 118.

Three consequences of getting it right, none of them cosmetic:

- `site-header__title` holds the `h1` and nothing else — no slot, no
  `justify-between`. `layout/_header.scss:34` is the whole rule.
- The trigger is `fixed`, so it stays put while the list scrolls. The header
  version scrolled away.
- The panel no longer needs a window event to be opened: the control is inside
  `courseFilter`'s own scope and sets `open` directly.

**The menu footer has no padding of its own.** `.site-menu__footer` is
`display: flex; justify-content: space-between; height: 64px` and nothing more.
The 8px on the left is each icon's own `mx-2x`; the 12px on the right is the
cross's `mr-3x`. Padding the footer `px-8` instead put the cross **20px** from
the panel edge where legacy has **12**.

**One difference left deliberately.** Legacy's `Anzeigen` shows no count on a
fresh load — `courses` is empty until something is filtered, because the
unfiltered list renders through the island's `<slot />` and only a filter click
calls `getResults()`. Ours says `Anzeigen (32)` from the start. Legacy's own
template asks for the count; its zero is an artefact of the two rendering paths
this rebuild collapsed into one. Worth a word if it should read bare instead.

### The active filter is black, and the stylesheet says grey — 2026-09-21

`.filter__item.is-active { color: $color-tertiary; font-bold }` reads as *bold
and grey*, and that is how it was ported. The live page shows **bold and black**,
because the rule sets the colour on the *item* while the `a` and the `select`
inside both carry their own from `form/_global.scss`:

```scss
input, textarea, select, button {
  color: $color-primary;        // black, and it wins
  @include font-bold();
  outline: none !important;     // no focus ring anywhere
}
```

The grey therefore lands on nothing — the link fills the item. The same global
rule is where the selects' missing focus ring comes from, and why a *chosen*
select goes bold while an untouched one stays regular
(`.filter h2, a, select` sets `font-regular` back).

**This is the opening rule of this chunk, failing in the other direction.**
*Measure the page, do not read the stylesheet* was written about placement; it
applies just as well to a colour that is declared and then never reaches
anything.

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

## Fortify had no views at all — 2026-09-21

`GET /login` was a **500**. Fortify was installed with chunk 08 and every route
it registers was live, but the package ships no views: until something calls
`Fortify::loginView()`, the controller has nothing to return. There was no
`config/fortify.php` and no service provider either. The checkout sits behind
`auth`, so this was the thing in front of it.

Built: login, registration, forgot- and reset-password, verify-email — all Blade
on the public layout, because that is where they are on the live site. Legacy's
registration was a 207-line Vue island posting JSON and painting its own errors;
the same seventeen fields as a form let Laravel do both.

Four pieces came out of it that the checkout needs next:

| Component | From |
|---|---|
| `x-site.article` | `layout/_article.scss:116` — the aside/column text page |
| `x-site.field` | `.form-group` + label + input + error |
| `x-site.select` | `.select-wrapper`, **teal** where the filter's is black |
| `x-site.checkbox` | 12×12, 14×14 from `sm`, solid teal when checked, no tick |
| `x-site.toast` | `.notification.is-toast`, anchored to the container's edge |

And `lang/de/` — legacy's `auth`, `passwords` and `validation` carried across,
because without them a failed login reads **`auth.failed`**.

### The URLs are legacy's, and the `guest` middleware is not

Two things found by opening the pages rather than the tests.

**A signed-in visitor to `/login` landed in the admin dashboard.** The `guest`
middleware does *not* read `config('fortify.home')` — it calls
`RedirectIfAuthenticated::defaultRedirectUri()`, which hunts for **a route named
`dashboard`**. This app has one, the SPA shell, so every already-signed-in
student who opened a login or registration page was bounced into
`/dashboard/termine`. `App\Support\Home` now answers it by role, and the same
answer backs `LoginResponse` and `RegisterResponse` — both through
`intended()`, which is what will carry a guest back to the checkout step that
bounced them.

**The screens sit on legacy's URLs.** Fortify's defaults are its own, and three
of them are wrong here:

| | live site | Fortify's default |
|---|---|---|
| Register | `/de/registration` | `/register` |
| Forgot password | `/password/reset` | `/forgot-password` |
| Reset link | `/password/reset/{token}` | `/reset-password/{token}` |
| Request a link (POST) | `/password/email` | `/forgot-password` |

`config/fortify.php`'s `paths` moves them, `RoutePath::for()` being there for
exactly this. `/login`, `/logout` and `/email/verify` needed no entry — Fortify
and legacy's `Auth::routes()` already agree. `/register` keeps legacy's own 301
to the prefixed form.

**The keys nest.** `RoutePath::for()` reads them with `config()`, so
`'password.request' => …` is a path through the array and not a key with a dot
in it — written flat, it silently does nothing.

### Three things measured rather than assumed

- **An input's line height is `normal`; a select's is 1.3.** Legacy's normalize
  sets `input { line-height: normal }` — the old Firefox fix at
  `helpers/_normalize.scss:336` — and says nothing about `select`. 37.5px against
  39.2 on the login field. The same family as the Tailwind pairing trap above.
- **A form select is teal.** `.select-wrapper select` is painted
  `$color-secondary` globally; the course filter overrides it back to black. Two
  selects, two colours, both correct.
- **The chevron is one definition now.** `.select-chevron` in `app.css`, because
  a pseudo-element is not expressible as a utility and two components need it.

### The teal background, and `h1` is bold — 2026-09-21

Three differences Marcel spotted by holding the two login pages side by side,
all confirmed by measuring production rather than reading the SCSS.

**`<html>` is teal on every screen behind a login.** `layout/_base.scss:10`:

```scss
html.is-auth { background-color: $color-secondary; }
```

The body keeps its white and its `min-height: 100vh`, so the teal shows only in
the gutters either side of the 1100px column, never below it. Legacy sets
`is-auth` on all eight auth views **and on the whole purchase flow** —
`checkout/index`, `checkout/confirmation`, the four `payment/` pages — plus the
two portals (`user/student`, `user/expert`, `students/index`) and the
maintenance page.

Here it is a prop on the layout: `<x-layout.site title="…" auth>`, which puts
`bg-teal` on `<html>`. Two things it needed:

- **`bg-white` on `body`**, which was not there. Tailwind's preflight leaves the
  body transparent, so without it `is-auth` paints the whole page rather than
  the gutters. Legacy writes it explicitly (`layout/_base.scss:26`).
- **The basket and the checkout must pass it.** They do not exist yet; this is
  the note that says so when they are built, and `LayoutTest` asserts the flag
  on the five screens that do.

**`h1` is bold.** `components/headings/_h1.scss` is three declarations —
`font-bold`, `color: $color-secondary`, `margin-bottom` — and the rework had the
colour and not the weight, on all seven headings. Production: 700. Ours was 400.

**The auth headings also carried a `text-3xl` they should not have.** Legacy's
`h1` sets no font-size at all, so the size comes down from
`article.content-text` (16/18/24) and that is the body scale. Spelling `text-3xl`
on the heading made it 24px from `sm` up, a size above its own body copy on a
tablet. Removed; it inherits now, as legacy's does.

Measured against production at 1481px, both sides now report the heading at
**24px / 700 / `rgb(70,186,186)` / 31.2px line-height, at x 203 y 136** — the
same numbers to the pixel.

### A top margin on an inline-block eats 6px

Found while checking the gap above *Passwort vergessen?*, which was 32px here
and **38px** on production.

`.form-helper` has no margin of its own. The gap is the submit button's
`.form-group` bottom margin — legacy wraps every control, the button included —
and that margin collapses through the `<form>`. Below it the helper starts a new
line box, and the parent's 24px/1.3 strut adds ~6px of half-leading above the
inline-block. 32 + 6 = 38.

Carrying the same 32px as `margin-top` on the helper instead does **not** give
38: a top margin on an inline-block raises the top of the line box past the
strut, so the half-leading is absorbed rather than added. The margin has to sit
on the form, as a block margin, which is where legacy's ends up. `mb-16
lg:mb-32` on the `<form>` now, and nothing on the helper.

Two smaller things from the same reading of `form/_layout.scss:150`:
`.form-helper` **underlines on hover at a 1px offset and stays black** — the
rework had it turning teal. The teal hover belongs to `.icon-arrow-right:below`
(`components/icons/_arrow.scss:51`), which is the *Nicht registriert?* link
beside it, and that one was already right.

### Two departures, both deliberate

- **Login is rate limited**, five a minute per email and IP. Legacy has none at
  all, and `08-accounts.md` already carries one account-takeover finding. The
  sixth attempt is a bare **429** rather than legacy's `auth.throttle` message,
  because Fortify throttles in route middleware and not in the validator — a
  friendly 429 view is in `Open-Questions.md`.
- **Two-factor and passkeys are off.** Fortify's stub enables both; neither
  exists on the live site, and both add account-recovery surface that wants a
  decision rather than a default.

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
