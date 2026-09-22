# 09 — The public site (in progress)

The current site, rebuilt on the new stack. **Not a new design** — that rule and
its reasoning are in `00-foundation.md` under *Parity means the current design*.

## Status

Started 2026-09-18. The shell, the course list with its full filter, the auth
screens and the **course detail page** are built and match production. `Buchen`
asks about the laptop and confirms the add, and the **basket page** is built and
measured — step 1 of 4, and the first screen on this site to make a real API
call from a real session, which is how it found that `auth:sanctum` could not
see one. **The address step is next**; everything else is under *What is left*.

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
- **The modal, and the basket's two dialogs.** `x-site.modal` is
  `.notification.is-modal`; `x-site.basket-dialogs` is the rental question and
  the confirmation that follows an add. `x-site.toast` gained a live mode so a
  removal can say so. See *The modal is 600px wide and the stylesheet says 480*,
  below.
- **The basket**, at `/de/checkout/basket` — the stacked list with its header,
  the laptop as its own row, the red already-booked warning, the empty state
  and *Weiter*. Behind `auth`, `verified` and `role:student`, as legacy's whole
  checkout is. See *The basket page*, below.
- **Course detail page.** The teal hero, the five collapsibles, the event row
  with its bookmark and its `Buchen`, and the prev/next pair — every block to
  the pixel. See *The course detail page*, below, for the three data findings
  that came out of it.

### What is left

Roughly in the order that unblocks the most.

1. **Basket and checkout — started, and this is where to pick it up.** The one
   with money in it, and the reason this track was chosen: chunk 06 has 251
   tests and has never run in a browser. Its blocker is gone — the auth screens
   are built (item 2) — and the flow is mapped in *What the checkout actually
   is*, below. The shape stays what `06-bookings.md` decided: **a POST per step
   with the state in the session**, because the server is the pricing authority.

   Next, in order:

   1. ~~`Buchen` on the course detail page.~~ **Built 2026-09-21**, and
      **finished 2026-09-22** — `add()` and `remove()` are wired, the header's
      basket count answers, the **rental dialog** asks before the add and the
      **confirmation** follows it, and a removal raises a toast. The modal they
      all needed is `x-site.modal`; see *The modal is 600px wide and the
      stylesheet says 480*, below.
   2. ~~**The basket page** at `/de/checkout/basket`.~~ **Built 2026-09-22** —
      the first time `PriceBasket` ran in a browser, and it did not, until
      `statefulApi()` was registered. See *The basket page*, below.
   3. **The three remaining steps**, then the confirmation. Address is next.
      Unlike the basket it has server state to keep, so it is the first one
      that actually POSTs.

   ~~**One bug to fix first, now confirmed firing.**~~ **Fixed 2026-09-22 — and
   the fix was the other way round.** `POST /api/basket/price` is behind
   `auth:sanctum` while `basket.js` called `price()` from `add()` and from
   `init()`, so clicking `Buchen` as a guest set `store.basket.error` to
   `Unauthenticated.` every time.

   The reflex is to open the endpoint. **Legacy draws exactly the same line**:
   `PUT /basket/{event}` is public and `GET /basket` sits behind
   `auth:sanctum + verified + role:student`, so on production a visitor can fill
   a basket and cannot see what it costs until they log in. Every screen that
   shows a price is behind the login on both sites, so the guard is right and
   the *asking* was wrong. The layout now writes
   `<meta name="authenticated">`, the store reads it, and `price()` returns
   early for a guest — no request, no error. `BookingApiTest`'s *will not price
   a basket for a guest* stays as it was, which is the point.
2. ~~Register, login, password reset.~~ **Built 2026-09-21** — see *Fortify had
   no views at all*, below.
3. **The two portals** — *Meine Kurse*, *Meine Dokumente*, the expert's course
   view. Every endpoint exists (`08-accounts.md`); only the screens are missing.
4. ~~The course detail page.~~ **Built 2026-09-21** — see *The course detail
   page*, below.
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

## The course detail page — 2026-09-21

Rebuilt against production, in front of the basket: `Buchen` lives on this page,
and the page was still the pre-parity stub (`text-4xl font-semibold`, `px-4`,
`space-y-6`). Building the button first would have meant measuring the page
twice.

The shape is legacy's: a `content-text-media` hero — one visual, then a `span-4`
aside of title and expert line against a `span-8` column of short description,
**all of it teal**, headings and body copy alike — and then a stack of
collapsibles, with the previous/next pair at the foot.

Every block now measures the same as production at 1697px:

| | production | here |
|---|---|---|
| Hero article | 977px | 977 |
| Aktuelle Kurse | 1177→1536 | 1177→1536 |
| Videos | 1600→2073 | 1600→2073 |
| Facts | 2137→2919 | 2137→2919 |
| Weitere Informationen | starts 2983 | 2983 |
| An event row | 118px | 118 |
| Weitere Kurse | 117px | 117 |

### Three things the rebuild found, none of them cosmetic

**`course_videos` was never ported.** The table is in the legacy schema, 19 of
32 courses have a row, and no `.rework` document mentions it — so those courses
were quietly losing a whole section of their page. Added: a migration, a
`CourseVideo` model, a `videos()` relation, and `PortCourses::portVideos()`.
`code` is an `<iframe>` an editor pasted and prints unescaped, which makes this
the only table besides the rich-text fields that the site trusts with raw HTML.

**`PortMedia` flattened legacy's three image roles into one.** It wrote
`is_teaser => false, is_og => false` for every row without ever reading
`images.type`, so 382 course images arrived undifferentiated. Three consequences,
all live until today: the course **card** showed whichever image sorted first
rather than the one the editor marked as the teaser; the course **page** had no
way to find its visuals; and every per-course **`og:image`** was lost to the site
default. Fixed in the port, and `HasMedia` grew `openGraph()` and `visuals()` —
spelled as methods because the schema says what an image is *not*, and
`->where('is_teaser', false)->where('is_og', false)` reads like a bug wherever it
appears.

**`courses.reviews` is an Elfsight embed** — which answers `Open-Questions.md`
#12 and undoes the plan in `04-content.md`. All 32 non-empty rows are a
`<script src="apps.elfsight.com/p/platform.js">` and an empty div, across 23
distinct widget ids, so the *Kundenmeinungen* cards on the live page are Google
reviews painted by a third party at run time. There is no testimonial data to
port into a `Testimonial` model, because VIAK never had any.

`PortCourses` reported nothing and dropped all 32: `maybeJson()` returns null for
anything that is not JSON. It now records a finding per row. Carrying the embed
across would put a third-party script on every course page, which is a decision
rather than a port — **Marcel's**, and it is back on the open list.

### What is deliberately not there yet

- **The rental dialog.** Legacy asks, before adding an event with
  `rentals_available`, whether to rent a laptop at CHF 80 excl. VAT — and says
  in the same breath that you can change it later. `Buchen` adds with no rental,
  which is that dialog's cheaper answer, so nobody is charged for something they
  did not ask for; what is missing is the offer. It needs the modal
  (`.notification.is-modal`) that the basket page needs too, so the two arrive
  together.
- **The toast after adding**, for the same reason.
- **The expert's name is not a link.** There is no expert page until chunk 04,
  and an anchor to `#` inside a sentence is worse than none. It looks identical:
  the stacked list gives its links no underline and only a teal hover.
- **No carousel.** Legacy puts a Swiper in the hero when a course has more than
  one visual. Not one of the 32 does — 181 visuals, never a second on the same
  course — so a library and a set of controls would be maintained for a case
  that does not occur.
- **The Kundenmeinungen column** renders only when `reviews` is filled, which is
  never, pending the decision above.

### The Tailwind line-height trap, twice in one page

`resources/css/README.md` warns that redefining `--text-lg` leaves Tailwind's
`--text-lg--line-height` in place. Both bites here were invisible until measured:

- The collapsible sets its own size (`%content-list-collapsible` is
  `sm:fs-16x md:fs-18x`, 18px inside a 24px page) and **`text-xl` brought a 1.4
  line height with it**, which the body's inherited 1.3 cannot override because
  it lands on the element itself. Facts came out 54px taller than production.
- The event row's small print is `.text-xsmall` — a size and nothing else, so it
  inherits the row's 1.5/1.4. Giving it `text-lg` brought 1.556 instead and grew
  the row from 118px to 119.

**`leading-[inherit]` is not the way out.** It inherits the parent's line height
as the *used* value, 25.2px, which on 16px text is taller again than the 1.4 it
came from — 25.88px, and the row still measured 119. The numbers have to be
written down.

One more of the same family, without Tailwind's help: a `<div>` per event day
rounds each day's 2 × 25.2px up on its own and made a two-day row a pixel taller.
Legacy puts all the days in one block separated by `<br>`, and that is why.

## `auth:sanctum` could not see a session at all — 2026-09-22

The largest thing this chunk has found, and the basket page found it in its
first second: a signed-in customer opened `/de/checkout/basket` and the page
said **Unauthenticated.** in red.

Laravel's slim skeleton does not register
`EnsureFrontendRequestsAreStateful`, and `bootstrap/app.php` never added it. So
the `api` group never ran it, `auth:sanctum` fell through to the **token**
guard, found no bearer token on a browser request, and answered 401 —
**every endpoint behind that guard, for every signed-in page**. The basket, the
bookings, the bookmarks, the profile, the addresses, the whole Vue dashboard.
One line fixes it:

```php
$middleware->statefulApi();
```

### Why 251 passing tests said nothing

`actingAs()` sets the guard directly. It never goes near the middleware stack,
so a test can authenticate and reach an endpoint that no browser on earth
could. Chunk 06 was green and unreachable at the same time, and the doc's own
note — *"chunk 06 has 251 tests and has never run in a browser"* — turns out to
have been more literal than it read.

The regression is pinned by asserting the middleware is **on the `api` group**
rather than by making a request, because a request test would authenticate the
way the others do and pass either way. Checked by removing the line and
watching it fail.

**This is the argument for the whole track.** The reason to build the public
site before anything else was that the money flow had never been seen. Two
screens in, it has paid for itself twice: this, and the guest pricing error.

## The basket page — 2026-09-22

`/de/checkout/basket`, step 1 of 4, from `frontend/checkout/views/Overview.vue`
and `shared/components/ui/layout/StackedList*.vue`.

### It is the one screen here rendered in the browser

Everything else on this site is server-rendered Blade. The basket cannot be:
the selection lives in `localStorage` and the server does not know it until a
later step posts it. So the page renders a shell, asks `/api/basket/price` —
the endpoint `00-foundation.md` kept for exactly this — and `<template x-for>`
draws the rows. The markup stays in the template; only the data is fetched.

**Nothing on the page computes a price.** Every figure is `course_fee`,
`rental_fee` or `total` as the server returned it.

### Measured against production's own classes

The page is behind a login, so it was measured the same way the modal was: by
rendering legacy's `.stacked-list-*` markup into a live page and reading
`getComputedStyle`. Ours against production, at desktop:

| | production | rebuilt |
|---|---|---|
| container top margin | 64px | 64px |
| header | 1068×31, 18px, gap 40 | 1068×31 |
| header cell | 329×23, 8px below | 329×23 |
| row | mt 32, pt 16, 1px rule | same |
| row type | 18px at line height 25.2 | same |
| row grid gap | 40px | same |
| *Entfernen* | 140×28 on `#969696` | same |
| footer | mt 48, 2px `#969696` | same |
| *Weiter* | 1068×56, 24px, lh 24 | same |

One value was wrong before it was measured: the header came out **25.2px line
height against production's 23.4**, because `.stacked-list-header` sets a size
and no line height — so production inherits the body's 1.3 — while saying
`lg:text-xl` brings Tailwind's own 1.4 along. Two pixels, and they push every
row on the page down. The row below it is *not* the same case:
`.stacked-list` states 1.4 itself.

### Four things the page does that the course page's row does not

- **The course title leads the row**, as an `h2` link to the course, above the
  dates. The course page's own row has no need of it.
- **No state line.** `is_basket` is the one thing the basket takes *away* —
  *Kurs offen, wird bestätigt* does not appear here.
- **The laptop is its own row in the same grid**, not a line inside the
  course's. Legacy appends three more `.stacked-list__col` divs after the first
  three, so they wrap onto a second row of the same twelve columns — which is
  why CHF 80.00 lines up under the course fee. Its *Entfernen* drops the rental
  and keeps the course.
- **Red when the course is already booked.** `has-booking` recolours the rule
  and every text in the row through `*:not([class*=btn-])`, so the buttons keep
  their grey. [[CompleteCheckout]] refuses that line outright; meeting the
  refusal at the basket rather than at the last step is the point. This is also
  the only thing `PriceBasket`'s `$for` argument has ever been for — it was
  accepted and unused until now.

### There is no total on the basket page

Legacy's `Overview.vue` shows a fee per row and **no sum**; the total first
appears on the summary step. Matched, because parity, but it is a real gap in a
screen called a basket and it is on the list for Marcel.

### Two smaller carried-over oddities

- `CHF 80.00` on the laptop row and a bare `499.00` on the course row, in the
  same list. Legacy's inconsistency, not ours.
- `\n\n` again: legacy writes the currency in one place and not the other for
  no reason either file gives.

### What is not verified

**The phone layout has not been seen.** `resize_window` reports success and
leaves `innerWidth` unchanged on this machine, so the page has only been
measured at desktop. Below `sm` the row stacks and the button takes its 24px
top margin, which is the same arrangement the course page's row was measured
with — but it has not been looked at, and that is a debt rather than an
assumption.

## The modal is 600px wide and the stylesheet says 480 — 2026-09-22

`.notification.is-modal` is the dialog legacy asks its questions in, and it is
the single best argument this chunk has for measuring rather than reading.

**The SCSS says 480 and the browser says 600.** `.notification.is-modal`
extends `%lightbox`, whose `> div` carries `min-width: 600px` from `bp-sm` up.
The modal's own rules then set `max-width: 360px` at `bp-sm` and
`480px !important` at `bp-md`. A min-width always beats a max-width, `!important`
or not, so **the box on production is 600px** and the 480 has never once
happened. A port written from the stylesheet comes out a fifth too narrow and
nothing in the source says why.

Measured with `getComputedStyle` over legacy's own markup rendered into a live
course page, 2026-09-22:

| | phone | `sm` | `lg` |
|---|---|---|---|
| box | 80% of the window | 600px | 600px |
| box padding | 24 / 16 | 24 / 16 | 32 / 24 |
| type | 16px | 16px | 20px, line height 1.3 |
| border | 3px, in the variant's colour | | |
| message | bold, centred | | |
| text | 16px whatever the box does, 16px above it | | |
| actions | 32px above, stacked, 12px apart | | |

The rebuilt dialog measures 600×274 against production's 600×274, and the
confirmation 600×196 against 600×196.

### The actions column is as wide as its widest button

`.notification-actions` is a shrink-to-fit column inside a centred column, and
`.btn-primary` carries `width: 100%` — so **every button is the width of the
longest label**, capped at the 240px the actions block imposes. The rental
dialog drops the cap with `!max-w-none`, because *Nein, ich bringe meinen
eigenen Laptop* wraps onto two lines at 240. Both its buttons then come out
339px, which is the long label's own width and not a number anybody chose.

### Two buttons whose hover does nothing, and it is not an oversight

Inside `.notification-actions`, legacy restates `.btn-primary` and
`.btn-secondary` at four class selectors deep — grey fill, grey border, and
white or grey text. `.btn-primary:hover` is two selectors deep, so **the black
hover the rest of the site has never fires in a modal**. An accident of
specificity that has been live for years.

`.btn-success` has no such override, so it does darken — to `#427b3c`, which is
`darken($color-success, 15)` read out of legacy's compiled
`public/assets/css/app.css` rather than recomputed. That is the one derived
colour in the palette.

Both ported as found. The dead hover is worth a decision rather than a quiet
fix, so it is on the list for Marcel.

### Three more things the markup does not say

- **`\n\n` in the rental text is not a paragraph break.** `Basket.vue` writes
  `…(exkl. MwSt.)\n\n(Du kannst dies auch später noch anpassen)`, and
  `white-space: normal` collapses it to one space. Production runs the sentence
  on, so the rebuild does too.
- **The backdrop does not close it on production.** `Notification.vue` defines
  an `addListeners()` that wires exactly that and never calls it, while the
  overlay still says `cursor: pointer` and the box `cursor: default`. The
  rental dialog has no close button either, so a visitor who wants neither
  answer has only the Escape key. **The rebuild honours what the cursor
  promises** — a deliberate departure, and the only one here.
- **Legacy renders both dialogs inside every `basket-button`.** A course page
  with four events carries eight hidden modals and eight copies of the text.
  The state lives on the basket store here, so the page carries one of each; a
  test counts them.

### The post-add message is a modal, not a toast

Worth saying because the earlier note in this doc called it a toast. Legacy
raises `.notification.is-modal` in green with *Der Kurs wurde im Warenkorb
abgelegt.* and two buttons, *Warenkorb* and *Schliessen*. The **removal** is
the toast — grey, because `$toast-colors` maps `default` to `#505050` and
legacy calls `$toast.open('…')` with no type.

That removal toast is also legacy's second toast implementation:
`vue-toast-notification`, with `vendor/vue-toast/_main.scss` existing purely to
make it look like the Blade one. Measured side by side they differ by a pixel
of padding and by the 480px step at `md`, which the vue one never got. Here
they are one component in two modes — `<x-site.toast>` with a slot for a server
flash, `<x-site.toast live />` driven by the store.

### Alpine 3 will not evaluate a directive outside a component

Not legacy's, ours, and it cost half an hour. `x-show="$store.basket.rentalFor"`
on an element with **no `x-data` anywhere above it** is never initialised: the
element keeps its `x-cloak`, nothing renders, and **nothing errors**. A store
magic is not a scope. Both the modal and the live toast carry an empty
`x-data` for this reason.

### `w-600` was 2400px

The spacing scale stopped at 500, and Tailwind falls back to its own
`calc(var(--spacing) * n)` for any number it has no token for — with `--spacing`
still `.25rem`. So `w-600` compiled, validated, and came out **four times** the
intended width, stretching the dialog across the window. The ceiling is now
1200 and `resources/css/README.md` says so out loud, because this is precisely
the silent wrongness that partial exists to prevent.

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

- **The basket shows no total.** Legacy's step 1 prints a fee per row and no
  sum — the total first appears on the summary. Ported as found, but a basket
  that will not tell you what it costs is a real gap, and the server already
  computes the number.
- **The modal's dead hover.** `.btn-primary` and `.btn-secondary` inside a
  notification have no hover on production, because the rule that recoloured
  them outranks their own `:hover`. Ported as found. Giving them the black
  hover the rest of the site has is a one-line change and a design decision.
- **`/de/checkout/basket` or `/de/warenkorb`.** `config/site.php` carries a
  `basket` segment of `warenkorb` that legacy never used — it serves
  `/de/checkout/…` throughout, and the rebuild follows it. Nothing indexes
  these pages, so it is a naming question rather than an SEO one, but the
  segment sits there unused until it is answered.
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
