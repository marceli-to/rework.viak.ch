# 09 — The public site (in progress)

The current site, rebuilt on the new stack. **Not a new design** — that rule and
its reasoning are in `00-foundation.md` under *Parity means the current design*.

## Status

Started 2026-09-18. The shell, the course list with its full filter, the auth
screens and the **course detail page** are built and match production. `Buchen`
asks about the laptop and confirms the add, and **the checkout is built end to
end** — basket, address, payment, summary and confirmation. A real purchase has
been taken through it in a browser: two bookings, one checkout, a discount code
and a laptop's VAT, all correct. Along the way it found that `auth:sanctum`
could not see a session at all.

**The student portal is built**, 2026-09-22 — all four screens, driven in a
browser: the profile saved, a laptop added and given up again, and the
cancellation dialog opened with the penalty in it.

**And the expert portal, the same day** — five screens, also driven in a
browser: a note posted to a course, a document uploaded and deleted again, the
message lightbox opened. 429 tests green, Pint clean. It settled
`08-accounts.md`'s finding 5 and turned up three defects in code that was
already built, one of them site-wide. Everything else is under *What is left*.

| | |
|---|---|
| Stack | Blade + Alpine + Tailwind, no Vue on these pages |
| Reference | the live site **plus** `../viak.ch/resources/sass/` |
| Conventions | `resources/css/README.md` — read before writing a class |
| Signing in | `Test-Users.md` — one account per role, from `DevUsersSeeder` |

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
- **A form kit for the checkout to use**: `x-layout.article`, `field`, `select`,
  `checkbox`, `toast`, plus `lang/de/`.
- **The modal, and the basket's two dialogs.** `x-ui.modal` is
  `.notification.is-modal`; `x-dialog.basket` is the rental question and
  the confirmation that follows an add. `x-ui.toast` gained a live mode so a
  removal can say so. See *The modal is 600px wide and the stylesheet says 480*,
  below.
- **The basket**, at `/de/checkout/basket` — the stacked list with its header,
  the laptop as its own row, the red already-booked warning, the empty state
  and *Weiter*. Behind `auth`, `verified` and `role:student`, as legacy's whole
  checkout is. See *The basket page*, below.
- **The address step**, at `/de/checkout/address` — the participant block, the
  *entspricht Teilnehmer-Adresse* toggle, the saved-address picker and the
  *Adresse erfassen* dialog, with the answer in the session. Brings
  `x-ui.lightbox` with it. See *The address step*, below.
- **Payment, summary and confirmation** — the discount-code field, the priced
  summary with both addresses, *Buchen* as a real form post, and the thank-you
  that empties the browser's basket. See *Payment, summary, and the only
  irreversible POST on the site*, below.
- **Course detail page.** The teal hero, the five collapsibles, the event row
  with its bookmark and its `Buchen`, and the prev/next pair — every block to
  the pixel. See *The course detail page*, below, for the three data findings
  that came out of it.
- **The student portal**, at `/de/student/profil` — *Mein Profil* with its
  edit form on its own screen at `/bearbeiten`, the four collapsibles
  (Merkliste, Gebuchte Kurse, Absolvierte Kurse, Dokumente), *Meine Dokumente*,
  one booked seat with its notes and materials, and the invoice-address pages. Brings `x-row.event`,
  `x-row.document`, `x-course.event-state`, `x-ui.back-link` and
  `x-dialog.booking`. See *The student portal*, below, for the six defects
  it turned up.

### What is left

Roughly in the order that unblocks the most.

1. ~~**Basket and checkout.**~~ **Done 2026-09-22.** The one with money in it,
   and the reason this track was chosen: chunk 06 had 251 tests and had never
   run in a browser. Its blocker is gone — the auth screens
   are built (item 2) — and the flow is mapped in *What the checkout actually
   is*, below. The shape stays what `06-bookings.md` decided: **a POST per step
   with the state in the session**, because the server is the pricing authority.

   Next, in order:

   1. ~~`Buchen` on the course detail page.~~ **Built 2026-09-21**, and
      **finished 2026-09-22** — `add()` and `remove()` are wired, the header's
      basket count answers, the **rental dialog** asks before the add and the
      **confirmation** follows it, and a removal raises a toast. The modal they
      all needed is `x-ui.modal`; see *The modal is 600px wide and the
      stylesheet says 480*, below.
   2. ~~**The basket page** at `/de/checkout/basket`.~~ **Built 2026-09-22** —
      the first time `PriceBasket` ran in a browser, and it did not, until
      `statefulApi()` was registered. See *The basket page*, below.
   3. ~~Address.~~ **Built 2026-09-22** — see *The address step*, below.
   4. ~~Payment, summary, confirmation.~~ **Built 2026-09-22.** The flow is
      complete and has been driven end to end in a browser.

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
3. ~~**The two portals.**~~ **The student half is done, 2026-09-22** — four
   screens at `/de/student/profil`, and the five links that pointed at
   `/dashboard` for want of anywhere better now point at it: the header's
   *Profil* icon (desktop and phone), the course card's *Verwalten*, *Adressen
   verwalten* on checkout step 2 and *Zum Profil* on the confirmation. See *The
   student portal*, below.

   ~~**The expert portal is what is left of it.**~~ **Built 2026-09-22** —
   five screens at `/de/experte/profil`, and both things it was waiting on are
   settled: `EventPolicy::viewParticipants` is the ownership check legacy's
   participant list has nowhere, and the message composer has been driven in a
   browser. See *The expert portal*, below.

   ~~**One thing is deferred rather than ported**: legacy's *Teilnehmerliste
   (PDF)*.~~ **Built 2026-09-22**, when the document pipeline was, and the link
   is on the course screen. It sits under the portal —
   `…/kurs/veranstaltung/{uuid}/teilnehmerliste` — rather than at legacy's
   top-level `/pdf/teilnehmer-liste/{event}`, so it goes through
   `teachable()` like every other screen here and cannot drift away from the
   check again. See [[03-invoices]] for the pipeline.
4. ~~The course detail page.~~ **Built 2026-09-21** — see *The course detail
   page*, below.
5. **Experten, Kontakt, Firmenschulung, the homepage** — the live site's own
   pages, rebuilt at parity; the 2026-09-23 mockups of them are phase two
   (`04-content.md`, *The mockup review*). **Experten is done, 2026-09-24** —
   the list and the expert page; see *The Experten pages*, below. **So is
   Kontakt, the same day** — see *The Kontakt page*. Left: the homepage.

   **Firmenschulung is not rebuilt at parity** (Marcel, 2026-09-24). Legacy's
   `/de/individualschulungen` is replaced by the mockup's page, which is phase
   two and waits on mail and `Testimonial` — see `04-content.md`, *The mockup
   review*. Until it exists the old URL keeps serving nothing in the rework,
   and at cutover it must 301 somewhere.
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
  expression, so the Alpine binding on `<x-card.course>` needs two.
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
| `x-layout.article` | `layout/_article.scss:116` — the aside/column text page |
| `x-form.field` | `.form-group` + label + input + error |
| `x-form.select` | `.select-wrapper`, **teal** where the filter's is black |
| `x-form.checkbox` | 12×12, 14×14 from `sm`, solid teal when checked, no tick |
| `x-ui.toast` | `.notification.is-toast`, anchored to the container's edge |

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

### The money column: bare numbers, right-aligned — Marcel, 2026-09-22

Legacy writes the currency on the laptop line and **not** on the course line
directly above it, in the same column of the same row: `499.00` then
`CHF 80.00`. This doc carried that across on parity grounds until Marcel looked
at it. It went to `CHF` on both lines first and then to **neither** — the course
page shows a bare `499.00`, that page is the one anyone can load and compare,
and the basket should not read differently from it.

So: **no `CHF` on a row, on either page.** The currency still leads the
**totals** on step 4, as legacy has it — `CHF 1179.00`, `CHF 6.48` — which is a
real distinction rather than an oversight: those are sums, and the rows are
prices.

Right-aligned for the reason money columns usually are — the decimal points
line up — and because on step 4 it puts the row fees in the **same column as
the totals**, which were already `text-right` and had nothing to align with.
`grow` on the amount is what makes that work: the box has to fill the column
before aligning inside it. The 32/48px gap to *Entfernen* stays where there is
a button, and goes where there is not.

### The deadline and the state read at the row's size — Marcel, 2026-09-22

Legacy gives *Anmeldung möglich bis …* and *Kurs offen, wird bestätigt*
`.text-xsmall` — 12/14/16 against the row's 16/16/18 — so on the live site they
are smaller than the expert's name directly above them, for no reason either the
markup or the design gives. They are not a footnote; the state line is the one
thing on the row that says whether the course is actually happening.

`italic` and nothing else now: no size, no `leading-*`. That is the only way to
*inherit* here, because naming a Tailwind size drags its own line height in with
it. Measured after: all four lines in that column are 18px on 25.2px, differing
only in the italic.

*Kurs ist ausgebucht* followed a few minutes later, on the same reasoning: it
stands in for the button rather than sitting in a sentence, which is why it was
missed first time — but it is the *reason* there is no button, so it is the last
thing on the row that should be whispering. There is now **no `text-*` class
anywhere in that row**, which is the point: inheriting means saying nothing.

**A departure from production, and a deliberate one.**

### And on a phone the price comes before the remarks — Marcel, 2026-09-22

Stacked, legacy's column order reads *deadline, state, price* — the two asides
first and the number they are about last. Reversed, the facts close on what it
costs and the remarks become the footnote they are, with 16px between them.

**The price is printed twice, and that is the cheap answer rather than the
lazy one.** `order` cannot reach across parents and the price lives in the
*third* column, so moving it meant either splitting the grid into five items
with explicit `col-start`/`row-start`, or flattening the row — both of which put
a desktop layout that has been measured against production at risk in order to
fix a phone. A `sm:hidden` copy beside the remarks and `max-sm:hidden` on the
original changes nothing above `sm`; `$fee` is decided once at the top of the
component, so the two cannot drift.

### The one pasted embed that was not a bare iframe — 2026-09-22

The video on the SketchUp course ran past its column and gave the page a
horizontal scrollbar. The ratio box was right; the selector was not.

`[&>iframe]` is a **child**. Of the 19 rows in `course_videos`, 18 are a bare
`<iframe>` and **one is wrapped in `<div class="embed-container">`** — a class
that has no CSS anywhere in either codebase, pasted by whoever made that entry.
The wrapped one kept the `width="640"` on its own tag and overflowed.

Legacy's own rule is `.ratio-container iframe`, a **descendant**, which is why
it never had this problem. Now `[&_iframe]`, plus `[&>div]:h-full` so a wrapper
has a height for the iframe's `h-full` to resolve against. Measured after:
699×393 inside a 699×393 box, no page overflow.

Worth keeping in mind for every other field an editor pastes into: the shape is
whatever somebody's clipboard held that day, so matching on depth is the wrong
thing to be strict about.

### The phone layout, seen at last — 2026-09-22

`resize_window` only ever shrinks a window on this machine, which is why the
checkout was measured at desktop. It left one at 500px, and Marcel put the
production basket beside it — the page nobody here can log into. Four things
came out of that pair.

**Every button on the site was the wrong width on a phone.** Measured: legacy's
*Entfernen* is **461px** at 500px and ours was 95. `%btn` is `display: flex` and
never declares a width — so on a block-level box `width: auto` fills the parent,
and the same button becomes a flex item in a `justify-between` row at `sm` and
shrinks to its content with the 140px floor. One declaration, two behaviours,
and it is why every caller in a block context had been passing `w-full` by hand.

A `<button>` does not inherit that: form controls size to `fit-content` whatever
their `display`. `max-sm:w-full` on `x-ui.button` rather than
`w-full sm:w-auto`, because the auth screens pass `w-full` deliberately and must
keep it at desktop. The course page's *Buchen* now measures 461 against
production's 461.

**Legacy's middle column on the laptop row is a literal `&nbsp;`**, there to
occupy a grid cell. Below `sm` there is no grid, so it rendered as a blank 24px
line between *Mietcomputer* and its price. Hidden below `sm`: a spacer for a
grid that is not there has nothing to space.

**The *Entfernen* of the course sat flat against the *Mietcomputer* heading**,
0px between them, so the button read as the laptop's. Production does this too —
Marcel's word was *"wrong on both envs"* — so it is a fix rather than a port.
24px above the heading below `sm`, the same 24 the action itself uses.

**The day and its hours share a line on a phone** (Marcel, 2026-09-22). Legacy
breaks them at every width, which on a three-day course spends six lines on what
is really three facts — and the phone is where the row is already tallest. The
break is hidden below `sm` and a comma stands in for it; a `<br>` set to
`display: none` genuinely stops breaking, which is what makes this one class
rather than two renderings.

`23. November 2026, 08.45 – 17.00 Uhr` is ~270px at 16px, so it still fits a
320px phone's 288px column; narrower than that it wraps, which is what it did
before anyway. **Both the basket row and the course page**, which Marcel asked for
once he had seen it in the basket.

### There is no total on the basket page

Legacy's `Overview.vue` shows a fee per row and **no sum**; the total first
appears on the summary step. Matched, because parity, but it is a real gap in a
screen called a basket and it is on the list for Marcel.

### Seeing it at phone width

~~**The phone layout has not been seen.**~~ **Seen 2026-09-22**, at 500px, and
it found four things — see *The phone layout, seen at last*, below. Marcel
confirmed the last round of them in his own browser.

**`resize_window` cannot be relied on here.** It reports success and leaves
`innerWidth` where it was; over one session it shrank a window once, then
refused to widen it, then refused to do either. So narrow browsing is a human at
the keyboard, and anything claimed about a phone layout should say which of the
two it was — measured, or reasoned from the rendered source.

## The address step — 2026-09-22

`/de/checkout/address`, step 2 of 4, from `frontend/checkout/views/User.vue` and
its `AddressForm.vue`. The first step with **server state**, and the first that
is a plain Blade form.

### The session holds a uuid, not an address

`00-foundation.md` settled the flow as a POST per step with the state in the
session, and [[CheckoutSession]] is that state: what the customer has decided
that is not in the basket. The basket stays in the browser — legacy's
server-held one is why a completed checkout left nothing behind
([[06-bookings]]).

It stores the chosen address's **uuid**, and every read re-checks the row still
belongs to the customer, because a session outlives the row it names. Deleting
an invoice address in another tab would otherwise bill the next checkout to a
row the customer no longer has. A test pins that.

It also keeps a shape question out of the middle of the flow — see below.

### Server-rendered, where legacy made two API calls to draw it

`User.vue` fetches `/api/student` and then `/api/basket`, nested, before it can
paint a screen whose every value the server already had: the customer's own
address, their saved invoice addresses, and which one they picked. Here the
controller hands all three to the view. The only JavaScript left is the
checkbox that reveals the picker and the dialog that adds to it.

### Three things that would have been wrong

- **A hidden `<select>` still posts its value.** `x-show` is `display: none`,
  not `disabled`, so ticking *entspricht Teilnehmer-Adresse* after picking an
  address would have billed the employer anyway. `<template x-if>` takes it out
  of the DOM, which is the version that cannot be wrong.
- **`exists:user_addresses,uuid` is not enough.** It passes for any address in
  the table, so a guessed uuid would bill a stranger's employer. The row has to
  be one of the customer's own, which is a `where` on the relation rather than
  a validation rule.
- **The checkbox is inverted.** Checked means *same as the participant* — the
  common case, 126 of 710 bookings use a separate address — so ticking it
  *hides* the picker. Legacy binds `:checked="hasAdresses ? false : true"`, and
  reading it the other way round makes the whole step behave backwards.

### The dialog is a real form post

Legacy's `AddressForm.vue` calls `/api/student/address` and splices the answer
into the select. Here it is a form that POSTs, so the step behaves like every
other form on the site and a validation error comes back through the session
with `old()` intact. `StoreAddressRequest` is the same rule set the API uses,
unchanged.

**The dialog has to reopen itself**, or the redirect lands on a closed dialog
with the messages hidden behind it. It keys that on the form's own fields being
in the error bag — so an `invoice_address` error, which belongs to the step
rather than the dialog, does not open it. That one is a toast, as legacy raises
it.

### `x-ui.lightbox`, and how it differs from the modal

Legacy has two overlays that both extend `%lightbox`. `.notification.is-modal`
is the message-with-buttons one; `.lightbox` is the one you put a form in. They
differ in exactly three ways:

| | modal | lightbox |
|---|---|---|
| border | 3px | 2px |
| box | 600px flat | 600–900px, shrink to fit |
| padding | 24/16, 32/24 from `lg` | 12, 24 from `sm` |

The width really is a range here — `max-width: 900px` and `min-width: 600px`
do not collide — unlike the modal, where a 480px max-width loses to the same
600px min-width and the box is always exactly 600. Measured 626×691 on the
address dialog, inside the range and driven by the form.

`.lightbox-overflow` caps the inner scroller at **90vh**, which is the whole
reason legacy has a second overlay: a long form scrolls inside the box instead
of pushing it off the screen.

### Legacy's picker label has no street in it

`address_str` is company, name, city — so two addresses at the same firm in the
same town read identically in the dropdown. Carried across as found;
`UserAddress::summary()` is that string and `UserAddress::lines()` is the block
form, both as lines rather than as the HTML with `<br>` in it that legacy's
accessors returned.

**The country is named only when it is not Switzerland**, which is also
legacy's rule.

### A shape question the summary step has to settle

There are **three** disagreeing ideas of what a frozen invoice address looks
like in this codebase today:

| where | shape |
|---|---|
| the 126 ported bookings | `{"lines": [...]}` — [[LegacyInvoiceAddress]] |
| [[CompleteCheckoutRequest]] | `name`, `street`, `zip`, `city`, `company` |
| `UserAddress::toSnapshot()` | `first_name`, `last_name`, `company`, `street`, `street_no`, `zip`, `city`, `country_code` |

The first is settled and deliberate: legacy stored a rendered HTML fragment and
there is no honest way back to fields, so the history keeps the lines that were
actually printed. The other two are a disagreement inside the rework, and
**step 4 is where it gets decided**, because step 4 is what writes one. The
session holding a uuid rather than a snapshot is what keeps this out of step 2's
way — and it means the address is read as it is at the moment of purchase, not
as it was when the customer picked it.

### What is not verified

The phone layout of **this step** specifically. Its rows reuse the
`.stacked-list` geometry that the basket's were measured with at 500px, and its
form controls are the kit the auth screens use — but the *Adresse erfassen*
dialog at phone width has never been looked at by anybody. The lightbox is
`max-w-[90%]` below `sm` with a 90vh scroller, so a long form should scroll
inside the box rather than push it off screen; that is a reading of the CSS,
not a measurement.

## Payment, summary, and the only irreversible POST on the site — 2026-09-22

Steps 3 and 4, from `Payment.vue` and `Summary.vue`, plus the confirmation —
which, unlike the four steps, really is a Blade page on the live site too.

### Payment takes no payment

The whole step is a paragraph of text and a discount-code field. Invoices are
raised on `EventConfirmed`, days or weeks later ([[03-invoices]]), so **there is
no Stripe anywhere in this flow**; `PaymentController` and its checkout session
are a separate thing, for paying an invoice that already exists.

**The code belongs to the basket, not to the session**, and that is the one
design call on the page. Everything else the checkout remembers is an *answer*
and lives in [[CheckoutSession]]; a discount code is part of what is being
priced, and the basket is the browser's. `basket.js` already carried `code`,
`/api/basket/price` already took it, and [[CompleteCheckoutRequest]] already
expected it in the payload — putting it anywhere else would have made two
sources of truth for one number.

Legacy validates the code before letting you past, with
`GET /api/discount-code/check/{code}` behind a client-side `length < 12` guard —
a wrong constant, since all 102 codes are 14 characters. Here *Weiter* calls
`applyCode()`, which prices the basket with the code: the same question, asked
of the thing that will actually answer it. A code that will not apply comes back
422 and the label turns into legacy's red *Gutschein-Code ist ungültig!*.

### The summary is half server, half browser

The two addresses are the server's — one is the customer's own, the other is the
answer step 2 put in the session — so they are rendered in Blade. The lines are
the browser's, because the selection is, so they come back from
`/api/basket/price` exactly as on step 1. The row itself is now
`x-row.basket`, shared by both, because legacy renders the same
`StackedListEvent` on both with the `action` slot filled only on the basket.

**A VAT row is the one departure on this page.** Legacy's summary shows *Total*
under the label **exkl. Mehrwertsteuer**, because `getTotals()` hard-zeroes VAT
under a `@todo: fix vat on event` that chunk 03 found was wrong about its own
zero. Courses are exempt, so the row appears only when a laptop is rented —
**every other basket renders precisely legacy's rows.** Two reasons it had to:

- The customer would otherwise be quoted a number they are not charged.
- `total_shown` is checked against the figure they actually pay, so a net total
  would have been refused by our own guard.

The client's own confirmed pattern is `Gesamtnettosumme` → `zzgl. 8.1 % MwSt.` →
`Gesamtsumme` ([[03-invoices]]), which is the shape now on the page. The *Total*
sublabel says `exkl. Mehrwertsteuer` when there is no VAT — legacy's sentence,
and true, since nothing on that basket is taxed — and `inkl. Mehrwertsteuer`
when there is.

### *Buchen* is a form, not a fetch

Legacy sends `POST /api/booking` and then sets `window.location.href`. Here it
is a real form post: a CSRF token, a redirect, and a failure that lands back on
the page with a message the customer can read.

The hidden inputs are the browser's basket, written out by `<template x-for>` —
the only way a server-rendered form can carry a selection the server does not
hold. **Nothing about the price crosses that boundary.** The server re-prices
from scratch, re-resolves the code, re-checks every seat, and refuses a checkout
whose total has moved.

### Two bugs this uncovered

**The three failure modes answered JSON to a browser.** `DiscountCodeNotRedeemable`,
`BasketPriceChanged` and `SeatNotAvailable` were registered with
`response()->json(…, 422)` unconditionally, which was right while the only
caller was the API. A form post that gets 422 JSON back shows the customer a
page of braces. Each one now redirects with the message in the error bag when
the caller is not asking for JSON, which is what puts it in the summary's toast.

**`basketList` never exposed `pricing`.** The component had an `items` getter
reading `basket.pricing?.items` and nothing for the totals, so every totals row
on the summary silently evaluated `undefined && …` and rendered nothing — and
the form's `total_shown` would have posted empty. Caught by looking at the page,
not by a test: `x-if` on an undefined variable is not an error in Alpine.

### The frozen invoice address, settled

Three shapes disagreed ([[09-public-site]], *The address step*). The winner is
**the fields a `UserAddress` has**, because it is what the customer actually
picks and the only one carrying a country and a street number — both of which an
invoice needs. `CompleteCheckoutRequest`'s flat `name` is gone. The 126 ported
bookings keep their `{"lines": […]}`, which is the honest record of what was
printed on a bill that has already gone out ([[LegacyInvoiceAddress]]).

It is snapshotted **at the moment of purchase**, not at step 2 — which is what
holding a uuid in the session buys, and what a test pins: an address edited
between the two bills the edit.

### The confirmation has one job the server cannot do

Emptying the basket. It is in `localStorage`, so the page tells Alpine to clear
it on arrival. Legacy never had to: `BasketStore` was a session bag a completed
checkout simply dropped — which is also why nothing in the old data records that
two bookings were one purchase, the gap [[Checkout]] exists to close.

### Driven end to end, on the real pages

A basket of two courses and a laptop, with a fixed CHF 80 code:

```
Zwischentotal                          CHF 1179.00
Gutschein-Code VIAK-JBR9-7JAZ        – CHF   80.00
MwSt. 8.1 % auf Mietcomputer           CHF    6.48
Total (inkl. Mehrwertsteuer)           CHF 1105.48
```

*Buchen* produced **one checkout and two bookings**, the discount recorded once
against the order rather than twice against the lines — which is the CHF 80 bug
chunk 06 was built to fix — the rental frozen at 80.00, and the basket empty
afterwards. The test purchase was then removed from the local database.

## Legacy styles editor links twice, and we ported the wrong one — 2026-09-22

Every link inside *Detailbeschrieb* and *Weitere Informationen* came out **teal
with a heavy underline** where production has plain black. Marcel spotted it;
the cause is that legacy has two rules and the comment in `x-ui.rich-text`
named only one of them.

| rule | where it applies | link |
|---|---|---|
| `article.content-text-media .text-media__body > div a` | the course page's **teal hero** | teal, 3px/1px → **5px/2px from `bp-sm`** |
| `.container-course .text-item a` | the **collapsibles** below it | `%link-underline`: 3px offset, 1px thick, **no step**, no colour |

The hero's treatment had been applied to both. Measured on the live page:
`rgb(0, 0, 0)`, `1px`, `3px` in the collapsibles — and that is **60 links across
two fields** (`information_booking` 38, `information_content` 22) against the
hero's four in `short_description`. The heavier one was the rarer one.

**The colour is now not set at all, in either mode.** It does not need to be:
the hero article is `text-teal` and the collapsibles are black, so a link that
says nothing inherits the right answer in both places. Legacy states
`color: $color-secondary` on the hero rule and arrives at the same result the
long way round. The `hero` prop carries the one thing inheritance cannot — the
heavier underline.

Verified after, on the two pages that have each kind: `rgb(0,0,0)` 1px/3px in
the collapsibles, `rgb(70,186,186)` 2px/5px in the hero.

**The lesson is the comment, not the CSS.** The old one said "links teal and
underlined … from `layout/_article.scss`", which was true of the selector
somebody read and false of the component it was written on. A source reference
has to name the *selector*, not the file.

## The *Adresse erfassen* dialog, measured properly — 2026-09-22

Built from the SCSS and only checked for its box dimensions; Marcel put it
beside production's and three things came apart. Legacy's own markup rendered
into a live page at 1717px gives the numbers:

| | production | was |
|---|---|---|
| header → form | **24px** | 0 |
| *Speichern* | **566×28**, full width | shrink-wrapped to its label |
| *Abbrechen* above | **16px** | 24 at `lg` |

**Every `lg:` spacing class in legacy is dead.** `helpers/_spaces.scss`
generates the unprefixed classes and `%sm\:` / `%md\:` *placeholders* — nothing
ever emits an `lg:` one. So `class="mt-6x lg:mt-12x"` is 24px flat, not 48, and
`mt-4x lg:mt-6x` is 16 flat, not 24. Both were ported as the markup reads,
which is the wrong number twice. Reading the markup is not reading the
stylesheet, and neither is measuring.

**And `align-items: center` does not stop a `width: 100%` child filling its
box.** `.btn-primary` extends `%btn-full-width`, so *Speichern* is full width
inside a centred flex column — it is centred and 100% at the same time, which
looks like a contradiction and is not. Ours shrink-wrapped because
`x-ui.button` is full width only below `sm`, so the call site passes `w-full`
the way the auth screens do.

After: widget 626 / pad 24 / border 2px, overflow 0 4px and 90vh, h1 24px on
31.2 with 12 below, body 24 down, label 18, *Speichern* 566×28, *Abbrechen* 16
above at 16px — **every value production's.**

### The `.text-xsmall` controls take the tablet's size on a phone

*Adresse erfassen* and *Adressen verwalten* now read 14px rather than 12, the
same call as the RAV passage above them (Marcel, 2026-09-22): they are the only
two controls in that block and one of them opens a form.

**And the plus icon is gone** from *Adresse erfassen*, where legacy sets a 12px
one 8px to its left. The word already says what the control does, and it was
the only icon anywhere in the checkout — it read as decoration rather than as
meaning. `x-icon.plus` went with it, so the Blade and Vue icon sets are back to
the split `resources/css/README.md` describes: `Plus` is the dashboard's.

## The checkbox sat high, and `items-start` is why — 2026-09-22

Legacy's checkbox is an inline input with `vertical-align: middle`, so the box
rides the middle of the line it is on and the 2px of label padding below
`bp-md` is a nudge on top of that. Ours is a flex row with `items-start`, which
pins the box to the top of a line box taller than itself.

So the offset is stated now — half the difference between the line and the box
at each step:

| | line | box | offset |
|---|---|---|---|
| base | 14px × 1.3 = 18.2 | 12 | 3 |
| `sm` | 16px × 1.3 = 20.8 | 14 | 3 |
| `lg` | 18px × 1.3 = 23.4 | 14 | 5 |

Measured at `lg` after: the box's centre and the label's first-line centre are
**0.3px apart**. Marcel confirmed the base and `sm` steps in his own browser —
`resize_window` could not reach them.

`items-center` would have been shorter and wrong — it centres against the
*whole* label, so the registration form's two-line consent would drag the box
to the middle of both.

### And the RAV passage gets the tablet's size on a phone

`.text-xsmall` is 12/14/16; the phone step is now 14. It is the longest passage
in the checkout and the only one a customer has to *act* on — it tells a RAV
client whose address to enter — and a phone is where it is hardest to read.
24px between it and the checkbox below `sm`, where it sits directly overhead
rather than in the column beside it.

## A text size is only a size now — 2026-09-22

`leading-[1.3]` had reached **42 places** across the views, restating a value the
`<body>` already sets, and Marcel asked why. The answer was a workaround that had
outlived its excuse.

Declaring `--text-lg` in `@theme` does **not** displace Tailwind's own
`--text-lg--line-height`, so every stock-named size kept emitting one — 1.556 on
`text-lg`, 1.4 on `text-xl`, 1.2 on `text-3xl` — over a page whose body says 1.3.
`resources/css/README.md` had said *"until the pairings are nulled, say the line
height you mean"* since 2026-09-21. Nulling them is one line:

```css
@theme { --text-*: initial;  /* then declare the nine */ }
```

Wiping the namespace drops the default sizes **and** their paired line heights.
It also retires `text-base`, which the conventions have always said does not
exist and which was compiling anyway.

### It was hiding a real bug

Diffed against the state before, the wipe moved **370 elements** — all of them
the header nav, which was taking Tailwind's pairing rather than the body's 1.3.
Measured against production:

| | production | before | after |
|---|---|---|---|
| 500px | 24px / 31.2 | 24 / 28.8 | 24 / 31.2 |
| 800px | 16px / 20.8 | 16 / 24.9 | 16 / 20.8 |
| 1200px | 20px / 26 | 20 / 28 | 20 / 26 |

Production's nav has no line height of its own and inherits 1.3 at every width.
Ours had been two pixels out on every page of the site, in the one element that
appears on all of them — and nobody had noticed, because the header row has a
`min-height` that absorbed it.

### Then 39 of the 42 came out

Removed wholesale and diffed again. The method: every element's computed
`font-size` and `line-height` across **nine pages at three widths — 6,825
elements**, rendered in same-origin iframes so the width is set rather than
asked for. (Which is also the way round `resize_window`, see above.)

Two rounds of that found the three that are real:

| where | why |
|---|---|
| `<body>` | it **is** the 1.3 everything inherits — removing it took the whole site to 1.5 |
| the checkbox label | sits inside a `.stacked-list` row at 1.5 / 1.4 and has to reset; the box-alignment offsets assume 1.3 |
| — | nothing else |

Final diff: **zero changes** across all 6,825.

### The test now guards the cause

`CourseFilterTest` had an assertion pinning `leading-[1.3]` into the filter's
markup — one page, one symptom. It asserts the scale itself instead: that
`partials/type.css` wipes the namespace, declares no `--text-base`, and declares
no line height at all. A page proves one element; that proves the rule.

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
they are one component in two modes — `<x-ui.toast>` with a slot for a server
flash, `<x-ui.toast live />` driven by the store.

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

**And it has two sizes, not one** — found 2026-09-22, when Marcel put a devtools
ruler on it. `icons/_basket.scss` sizes the **anchor**: 19×16 from `bp-sm` and
**26×22 from `bp-md`**, with the badge stepping 16→18 and its offset −12→−14.
Only the first step had been ported, so the icon sat a quarter too small on
every desktop. Both now measure 26×22 and 18×18 against production's 26×22 and
18×18.

Worth noting *why* it slipped: the profile icon beside it has the same two-step
shape and was ported correctly, so the header looked deliberate. A single
measurement of one icon would not have caught it — the icons have to be
measured against the live page one at a time.

**The mobile menu has a *Warenkorb* entry**, and ours did not. `menu.blade.php`
is **two `<ul>`s** inside `.site-menu__main` — the three nav links, then basket
and profile — which on desktop are the two `span-6` lists and on a phone stack
into one. So *Warenkorb* belongs between Kontakt and Profil, hidden at a count
of zero like its desktop twin.

Its badge is a different animal: **26×26, `margin-left: 12px`, and teal on
black** rather than 16px white-on-black hanging off an icon (`bp-xs` in the same
file). It sits beside a word rather than on a glyph, so it is read rather than
glanced at. Taken from the compiled stylesheet, not eyeballed — the window was
wide again by then.

**`arrow-right` is two SVGs**, genuinely different artwork per breakpoint.
**`profile` draws a different figure when signed in.** **`cross` takes a size
argument.** All three look like bugs and are not.

**The logo gets *smaller* at the first breakpoint** — 48px, 44px at sm, 56px at
lg. Also the design's own doing.

**A green `npm run build` says nothing about a component nobody imports yet.**
Vite tree-shakes it, so four broken Vue icons compiled clean. Run them through
`vue/compiler-sfc` directly.

## Open, for Marcel

- **The summary shows a VAT row that legacy does not** — only when a laptop is
  rented, because courses are exempt and legacy hard-zeroed the rest. The
  alternative was quoting a total the customer is not charged. Worth a nod, not
  a decision, unless you disagree.
- ~~**What a frozen invoice address looks like.**~~ **Settled 2026-09-22**: the
  fields a `UserAddress` has. The 126 ported bookings keep their lines.
- **Adressen verwalten** points at `/dashboard`, because the student portal is
  not built yet (item 3 under *What is left*). Legacy links
  `/de/student/profil`.
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

## The student portal — 2026-09-22

`/de/student/profil` and three screens under it, from `backend/student/` — four
Vue views, 839 lines, three API calls to draw a screen whose every value the
server already had. Server-rendered Blade like the rest of the site; the only
JavaScript is the Alpine store behind *Annullieren* and the laptop.

`08-accounts.md` built every endpoint these use, so this was frontend work —
and it still turned up **six defects**, four of them in code that was already
built and green. That is the pattern the chunk doc already named: *a green suite
can still be unreachable*.

### The edit form is a screen, not a panel — Marcel, 2026-09-22

Legacy toggles the profile form in place off `isEdit`. Rebuilt that way first,
as Alpine state, and it is **wrong** — not stylistically, structurally.

*Rechnungsadressen* lives **inside** that form, and its links go to screens of
their own. So: open the form, click `+`, type an address, save — and you land
back on a profile whose panel is shut, with the address you just created
invisible inside it. The *Zurück* link on the address screen does the same. The
state that says *the form is open* does not survive leaving the page, and the
form is the only place the list exists.

**Legacy has exactly this hole and an SPA hides it**: its router-links unmount
`Index.vue` and it remounts with `isEdit = false`. The address is equally
invisible there; it is just less obvious, because nothing navigated.

So the form gets a URL — `/de/student/profil/bearbeiten`, from the `edit`
segment already in `config/site.php`, with the POST landing on the same URL so a
validation failure comes back by itself.

**And it is the form and nothing else** (Marcel, 2026-09-22). Not the profile
with the form swapped in: a sibling of the address screens, built the same way —
an aside carrying the heading and a *Zurück*, the form in the `span-8` column,
and the page ends. The first attempt kept the four collapsibles underneath on
the grounds that legacy's is one page; it is one page there *because* it is a
toggle, and once it is a screen the course lists are only something to scroll
past on the way to *Speichern*.

Three things follow from *only the form*:

- **The aside carries *Zurück*, not *Logout*.** The same control the address
  screens have, pointing at the profile. Offering to end the session from the
  middle of an unsaved form is not the exit anyone is looking for — and *Logout*
  belongs on the screen you land on, which still has it.
- **No pencil.** On the profile it opens this; here *Zurück* closes it, and two
  controls doing one job is what the toggle was.
- **`edit()` loads none of what the landing screen does** — no bookings, no
  bookmarks, no documents, no penalties. A form asking for a phone number has no
  use for a course list, and computing one per visit was the cost of treating
  this as the same page.

What it settles, beyond the bug:

- **Both screens have no JavaScript of their own left.** No toggle, no `x-show`,
  no `x-cloak`. The pencil is a link, *Abbrechen* is a link, and the browser's
  back button does what it looks like it should. What Alpine remains on the
  profile belongs to the collapsibles, the basket and the cancellation dialogs;
  the edit screen has only the two collapsibles inside the form.
- **The three address redirects go back into the form**, not to the read view —
  which would show somebody who just added an address a page with no addresses
  on it at all.

A query flag (`?bearbeiten`) would have done the same job — the course filter
already writes its state back that way — and it was the first attempt. A route
is better for something that is a *screen* rather than a *view of one*: it can
be linked, it can be the target of a redirect, and it does not need Alpine to
read it back.

### The URLs are legacy's two role trees, with the segments in config

**Marcel's call, 2026-09-22.** `/de/student/profil` and `/de/experte/profil`,
not one `/de/konto` for both.

The argument for merging them was the single `ProfileController` — one
controller for all three roles was an explicit chunk 08 decision. The argument
against is the data: **four accounts hold more than one role** (three
Admin + Expert + Student, one Admin + Student), and what a student sees and what
an expert sees are different screens over different data, not two views of one.
A dual-role user needs both at once, and the role in the path is what separates
them — it is also what the `role:` middleware is already guarding.

What is *not* legacy's: the segments come from `config/site.php` rather than
being written into `routes/web.php`, so `/en/student/profile/documents` exists
the day `'en'` joins `site.locales`. Legacy spells both languages out by hand,
twelve routes for six. Six new segments — `student`, `profile`, `event`,
`address`, `create`, `edit` — join the ones that were already there.

The `account` → `konto` and `documents` → `dokumente` pair that has been sitting
unused in `config/site.php` stays unused, exactly as `basket` → `warenkorb` does
for the checkout. Adopting either would be a decision rather than a port.

### Measured against production's own classes

The portal is behind a login, so it was measured the way the modal and the
basket were: by rendering legacy's markup into a live page carrying the
production stylesheet and reading `getComputedStyle`, at three widths.

| | phone (375) | sm (800) | lg (2560) |
|---|---|---|---|
| `.collapsible-container` | mt 48 | mt 48 | mt 64 |
| `.collapsible` | mb 64, 1px `#505050` | mb 64, 2px | mb 64, 2px |
| `.btn-collapsible` | 14px, p 8/12 | 16px, p 16/24 | 18px, p 16/24 |
| `%stacked-list` row | 16/24, mt 16 pt 8 | 16/22.4, mt 32 pt 16 | 18/25.2, mt 32 pt 16 |
| grid gap | (stacked) | 16 | **40, rows as well as columns** |
| `.stacked-list__icon` | absolute, t 12 r 0 | pr 16, pt 4 | pr 16, pt 4 |
| `.stacked-list__action` | mt 24 | 0 | 0 |
| aside's back / logout link | mt 0 | mt 20 | mt 40 |
| `.icon-edit` | absolute t 0 r 0, 18×18 | | |
| `.form-danger-zone` | mt 24, p 8, 14px | mt 48, p 8/12/12/12, 16px | mt 48, p 12/16/16/16, 18px |
| `.no-results` | mt 16, italic | | |

Everything in the middle three rows is what `x-card.event` and
`x-row.basket` were already built to, which is the useful part: the row
this portal is made of is the row the course page and the basket already draw,
and it measured identically without being touched.

**The row gap is the one that would have been missed.** `%sm\:grid-cols-12` sets
`gap`, so legacy's twelve-column grid has a 40px gap **between rows** as well as
between columns — which is what spaces the laptop line under the course line,
and the rental offer under both. Add a margin for it and you get 64. Found by
measuring the laptop row rather than by reading the SCSS, where one `gap`
declaration covers both.

### Two lists split on the date, and 67 live rows say why

Legacy's *Gebuchte Kurse* is `bookings()->notFlagged('isConcluded')` and
*Absolvierte Kurse* is `flagged('isConcluded')->flagged('hasParticipated')`.
`isConcluded` is written in exactly one place — `EventClosedHandler` — and
**only for a booking already flagged `hasParticipated`**, so a seat nobody
ticked off never leaves the first list.

Measured against the 2026-09-11 snapshot:

| | |
|---|---:|
| Active bookings | 527 |
| — carrying `isConcluded` | 436 |
| — **not**, and the course has already run | **67** |
| Students affected | **63** |
| Oldest | **16 March 2023** |

So 63 students open the live site today and see courses from 2023 listed as
*Gebuchte Kurse*, each with a live *Annullieren* button beside it — and
cancelling one would fire the 100 % penalty rule against a course that ran two
years ago.

**The rework splits on the event's date**, which needs no flag anybody has to
remember to set, and which the rework could not have used anyway: neither flag
was ported. *Absolvierte* then overstates slightly — it includes a course
somebody booked and did not attend — and that is the right trade: attendance is
recorded by the participation confirmation, not by a list heading, and the
behaviour that matters is that a course which has happened cannot be cancelled.
Today counts as upcoming, which is the boundary `Event::scopeUpcoming` already
draws and legacy's `date > today` left in neither list.

### The cancellation dialog names the price before it asks

Legacy's `confirmBookingCancellation()` builds one of two sentences out of
`booking.cancellation.penalty` and `.amount`, and `06-bookings.md` said the
rework tells the student at the moment they cancel. This is the other half:
told **before**.

The two figures are rendered into the row by the server from
`CancellationPenalty` — the same class `RaiseCancellationPenalty` calls — so the
dialog cannot promise one number and the invoice say another. Nothing in the
browser computes a penalty.

### Six defects, four of them in code that was already green

Worth listing, because five of the six are the same shape: **a rule written for
a JSON client, met for the first time by a form.**

1. **`route()` inside the locale group could not resolve.** Every route in the
   prefixed group takes `{locale}`, and `SetLocaleFromUrl` then calls
   `forgetParameter('locale')` — correct for the controllers, and it leaves
   `route()` with a required parameter and no value. The checkout never hit it
   because it goes through `SiteUrl`; the portal's form actions are named
   routes, so the first `route()` call in the group was also the first
   *Missing parameter: locale*. Fixed with `URL::defaults()` in the same
   middleware, which makes every named route in the group resolvable.

2. **The profile form demanded the password on every save.** `UpdateProfileRequest`
   required `current_password` when `filled('email')`, and an edit form prints
   the current address in the field — so a student could not correct a phone
   number without typing their password. *Filled* is not *changed*; the rule
   says `changesEmail()` now, and `UpdateProfile` compares too. Legacy sidesteps
   this by calling the field `new_email` and leaving it blank, which is a
   different form rather than a different rule.

3. **And then failed on the empty one.** An untouched password box posts `''`,
   which `ConvertEmptyStringsToNull` turns into `null`, which fails `string`. An
   API client omits the key and never meets it. `nullable` added; `required`
   is implicit and still fires ahead of it.

4. **`Event` had no `media()` relation.** `port:media` wrote **13 rows** with
   `mediable_type = App\Models\Event` — zips of models and textures, workshop
   PDFs across 5 events — and they have been unreachable ever since, because a
   morph with no relation on the owning side raises nothing at all: no error, no
   null, no missing column. The same failure `event_expert` had. `Open-Questions.md`
   already carries the item asking for the rest of the ported pivots to be
   counted; this is the second one found by tripping over it.

5. **`MessageResource` read `$this->author->firstname`** — legacy's column name,
   where `name` held the surname. It does not exist here, so Eloquent returned
   null and `trim()` swallowed the leading space: the right answer by accident,
   one rename from a stray space in every payload.

6. **The header's *Profil* icon pointed at `/dashboard` for everyone, including
   a guest** — so a signed-out visitor clicking it landed on the admin SPA
   shell. It is `SiteUrl::profileFor()` now: login for a guest, the student
   portal, the expert portal, the dashboard for an admin-only account, in that
   precedence. Legacy adds a fourth case this does not have — a `selected-role`
   in the session, set by a role-picker screen after login — which four accounts
   see and which is not built.

### Three parity defects in the registration form, found on the way

The profile form is the registration form's twin, so building it meant looking
at that one again. All three were confirmed on the live `/de/registration`,
which is public:

| | production | was here |
|---|---|---|
| Geschlecht | `männlich / weiblich / andere` | `Frau / Herr / Divers` |
| Strasse / Nr. | `span-6` + `span-6`, 329px each | 9 / 3 |
| PLZ / Ort | `span-6` + `span-6`, 329px each | 4 / 8 |

The gender labels are the more interesting one. *Frau / Herr / Divers* is
defensible as copy — the field exists for the salutation on an invoice
(`Gender`) — but it was neither measured nor recorded, and the portal's own form
would then have disagreed with it. Both now come from `Gender::label()`, which
returns exactly the three strings `genders.description` holds in the legacy
database, so there is one place to change it if VIAK decides the salutation is
the better wording.

### Small things worth knowing before the expert portal

- **`@js()` is not compiled inside a component tag's attribute.** It reaches the
  browser as those six characters and Alpine answers *Invalid or unexpected
  token*. An echo is, so `{{ Js::from(…) }}` — `Js` is `Htmlable`, so `{{ }}`
  hands the JSON over without escaping it twice. `x-data="bookmark({ … @js(…) })"`
  on the course card works because it sits on a plain `<button>`.
- **`can('viewForEvent', $event)` denies silently.** The ability is on
  `MessagePolicy` and the argument is an `Event`, so the Gate resolves
  `EventPolicy`, which has no such method. `can('viewForEvent', [Message::class,
  $event])` is the call the API already makes.
- **The count beside a collapsed collapsible is a `<strong>`, not a `<span>`.**
  `%content-list-collapsible` styles `> h2 a span` with a 12px margin and
  regular weight, and `Count.vue` renders a `strong` — so **that rule has never
  matched anything**. Ported as it renders: bold, `#505050`, one space.
- **The booked-course checkmark is teal.** `Checkmark.vue` hardcodes
  `fill="#46baba"`, and the Blade set normalises every icon to `currentColor` so
  a `text-*` can reach it — which is right, and means the colour has to be said
  at the call site instead of being smuggled in with the artwork.
- **A form's submit button is full width.** `%btn` is `display: flex` and never
  states a width, so legacy's `<a class="btn-primary">` in block flow fills its
  column — 699px on the registration form, 663 inside the danger zone. A real
  `<button>` sizes to `fit-content` whatever its display, so the width has to be
  said. The row buttons are unaffected: those are flex items with a 140px floor.
- **`danger-dark` joins `success-dark`** as the second and last derived colour.
  `.btn-danger:hover` is `darken($color-danger, 15)`, which the compiled
  stylesheet resolves to `#b31c00`. The note beside `success-dark` said two was
  the point to ask for a scale instead; the answer is still no — `danger` and
  `success` are the only two colours legacy darkens, and a scale would invent
  seven values nothing uses.

### What the screens do not do, on purpose

- **No compose box on the course thread.** 251 messages over four years and
  **every one written by an admin or an expert** (`08-accounts.md`), so the
  student's side is a read. The endpoint that would take one is behind
  `MessagePolicy::create`, which is staff-only.
- **A cancelled seat keeps its screen but loses the thread.** The booking is the
  customer's own history and its documents still point at it; the course's notes
  and materials belong to the people actually on the course, which is
  `MessagePolicy`'s and `MediaPolicy`'s call rather than the screen's. Legacy
  has no state for this at all — it drops a cancelled booking out of every list,
  so the only way back to that screen is a link that now 404s.
- **The address pages leave the profile form.** Legacy's router-links do the
  same and anything typed above is lost either way. Fixing it means a nested
  form or a dialog, and the checkout's *Adresse erfassen* lightbox is a
  different screen with a different job — it exists so a customer mid-purchase
  does not lose the basket.

### Five things the screenshots caught — 2026-09-22

Marcel put the rebuilt portal beside the live one and found four. Two are
**site-wide** rather than the portal's, and both are the same mistake: a value
read out of the SCSS instead of off the page.

#### The input colour was inverted on every form

`form/_global.scss` states it twice, and the **second** rule wins:

```scss
button, input[type=text], select, textarea  { color: $color-primary }   // #000
.select-wrapper, input[type=text], textarea { color: $color-secondary } // #46baba
```

Read from the stylesheet the first is the answer. Read from the browser the
second is: measured on the live `/de/registration`, every text input is
`rgb(70, 186, 186)`. So the **labels are black and the values are teal**, which
is the whole visual logic of these forms — and `x-form.field` had it the other
way round, with a docblock quoting the losing rule as its evidence.

`x-form.select` had it right all along, because `.select-wrapper select` is the
one place the teal is stated only once and there was nothing to misread.

It shipped on login, registration, password reset, the checkout's address dialog
and the portal. One class.

#### *Abbrechen* was bigger than the button above it

`.form-helper` is **14/16/18 and italic**. It had no size at all, so it inherited
the page's 24px. And the gap to the button is the button's own `.form-group`
margin — 16px, **32 from `lg`** — where this had `mt-16` on the helper and
`mt-32` above the button, giving 16 at every width.

| | production | was | now |
|---|---|---|---|
| *Abbrechen* | 18px / 23.4 italic | 24px / 31.2 | 18px / 23.4 |
| gap below *Speichern* | 38px | 16px | 38px |

#### An invoice address is a person **or** a firm — Marcel, 2026-09-22

The first cut required first and last name and left company optional, which is
legacy's shape. *Rechnungen, Muster AG* needs no contact name, and demanding one
invents a person.

So: no company → both names; a company → names optional; **one name and no
company is still a failure**, because half a name is not one.
`required_without` on each name gives the first three and
`required_without_all` on the company makes the *neither* case say so.

The `*` came off all three — a star on Vorname would claim something the server
does not enforce, and `required` on the input would stop the browser submitting
a valid company-only address before the server saw it. The rule is stated once,
as a hint under *Firma*, where somebody who left the names blank is looking.
Laravel's own wording for `required_without` names the other field — *"Vorname
muss ausgefüllt sein, wenn Firma nicht ausgefüllt ist"* — which is accurate and
reads like a riddle, so all three carry one sentence instead.

**The data did not force this.** All 122 ported addresses carry both names, so
the old rule refused none of them; 114 also carry a company, which says only
that the employer-paying case is the norm. The loosening is safe precisely
because nothing existing depends on the stricter form.

`StoreAddressRequest` is shared with the checkout's *Adresse erfassen* dialog,
so that gets the same rule and the same sentence.

#### Un-hearting a bookmark left its row on the list

`Bookmark.vue` takes a `callback` prop and the **Merkliste is the only caller
that passes `hideAfter`** — because it is the list *of* hearted courses, so
removing one has to take the row with it. Ported without it, the row stayed and
went on advertising a course that was no longer on the list.

Legacy removes the element (`el.remove()`); this hides it, so a failed request
can put it back — legacy's cannot, having already thrown the markup away.

Both verbs also raise the toast legacy raises, in legacy's words and in its grey
(`$toast.open()` with no type). They were left out when the heart was built
because nothing drew a toast yet; the store arrived with the basket.

#### And the same Blade trap twice

`x-data="bookmark({ … @js(…) })"` works on the course card and **not** on the
portal's row, because there it sits on `<x-row.event>` — and a Blade
directive inside a *component tag's* attribute is not compiled. It reaches the
browser as those six characters, Alpine fails to initialise, and nothing says
so: the heart simply does not respond. `{{ Js::from(…) }}` is the form that
works, because an echo is compiled and `Js` is `Htmlable`.

This is the second time it bit in one day — the first was the cancellation
dialog's payload. The rule, stated once: **inside a `<x-…>` tag, `@js` is text
and `{{ }}` is code.**

Which is also why `x-row.event` now merges `$attributes` onto its
`<article>` rather than writing a bare `@class`: the Merkliste needs `x-data`
and `x-show` on the row itself, because un-hearting has to hide the whole thing
and the heart is three levels down in the icon slot.

#### The delete box was not a box

`.form-danger-zone` is `border: 2px solid` — **all four sides** — and this had
`border-y-2`. The earlier measurement read `borderTopWidth` and
`borderBottomWidth`, found 2px on each, and stopped; the SCSS says `border` in
one word. Without the sides there is nothing for the 16px padding to hold
*Löschen* away from, so the button read as full-bleed and the block stopped
looking like a box at all.

It also **sets its own type size**, 14/16/18, and was inheriting the page's 24px
— the same mistake *Abbrechen* made above, found in the same pass and not
generalised at the time. That is now two blocks on this screen that set a size
legacy states and we inherited instead: worth treating as the default suspicion
whenever a rebuilt block looks a size too big.

| | production | was | now |
|---|---|---|---|
| border | 2px all round | top and bottom | 2px all round |
| type | 18px / 23.4 | 24px / 31.2 | 18px / 23.4 |
| padding | 12/16/16/16 | same | same |
| box / button width | 699 / 663 | — | 699 / 663 |

#### Du, Dir, Deine — capitalised

The site addresses the customer informally and capitalises it throughout:
**90 occurrences in legacy's copy and not one lowercase**. *Die Annullation wird
Dir per E-Mail bestätigt*, *Deine Merkliste ist leer*, *Falls Du keinen Laptop
hast*.

The e-mail verification flash had it both ways inside a single sentence —
*"Deine Angaben wurden gespeichert. Bitte bestätige deine neue E-Mail-Adresse
über den Link, den wir dir geschickt haben."* Which reads as sloppiness rather
than as a style, and is the only rendered string on the site that broke the
rule.

#### And the address screens are *Adresse*, not *Rechnungsadresse*

`Form.vue`'s `title()` computes **Adresse bearbeiten** and **Adresse
hinzufügen**. These said *Rechnungsadresse bearbeiten* and *Rechnungsadresse
erfassen*, which is neither legacy's word nor consistent with the *Adresse
löschen* box at the foot of the same screen. The screen is already reached from
a block headed *Rechnungsadressen*, so the longer word was saying it twice.

Not the same word as the checkout's *Adresse erfassen* dialog, which is also
legacy's — there it is a dialog and here it is a page, and they are allowed to
differ because production does.

## The expert portal — 2026-09-22

`/de/experte/profil` and four screens under it, from `backend/expert/` — four
Vue views, 578 lines, two API calls to draw a screen whose every value the
server already had. Server-rendered Blade like the rest of the site; the only
JavaScript is the message lightbox and the delete confirmation.

The student portal's sibling, and mostly its markup: same article, same
collapsibles, same row. **Where the two differ is what a course *is* to each of
them.** A student's course is a seat they bought, so their screen leads with the
booking and what can still be done to it. An expert's is a room they will stand
in, so theirs leads with who is coming — and carries the two things only an
expert does, which are writing to the class and giving it files.

| screen | |
|---|---|
| `/de/experte/profil` | the address block, *Bevorstehende* and *Vergangene Kurse* |
| `…/bearbeiten` | the profile form — the student's minus *Rechnungsadressen* |
| `…/kurs/veranstaltung/{uuid}` | *Informationen*, *Teilnehmer*, *Nachrichten*, *Kurs-Dokumente* |
| `…/{uuid}/message` | the composer |
| `…/{uuid}/file-upload` | the course materials form |

The last two keep legacy's **English segments inside the German path**, which is
the same wart `/de/checkout/basket` carries and the same reason to keep it:
nothing behind a login is indexed, so it is a small question rather than an SEO
one ([[SiteUrl::checkout]]).

### Finding 5, settled — and it is one method

`08-accounts.md`'s finding 5 is that `GET /pdf/teilnehmer-liste/{event}` is
gated by `role:admin,expert` and nothing else, so **any of the 18 accounts
holding the Expert role can download the names, towns, phone numbers and email
addresses of every student on every course in the archive**. It had to be
settled before this screen was built, because this screen is where the link to
it goes.

It is not a missing idea, it is an omission on one route: the neighbouring API
call, `EventController::findExpertEvent`, does `authorize('containsEvent',
$event)`. So the rework states the neighbour's rule once —
`EventPolicy::viewParticipants`, *admin or teaches it* — and every caller asks
it: the screen that draws the list, and whatever serves it as a PDF the day
there is a PDF.

**A 404 rather than a 403** when it denies, which is the student portal's rule
for the same reason: a course somebody does not teach and a course that does not
exist are told apart only by whoever is asking, and answering *forbidden*
confirms that the uuid is real.

The guard above it is legacy's — `role:admin,expert`, so an admin reaches these
screens too, and the policy then admits them to every course rather than to the
ones they teach. That is right: an admin answering a question about a course
should not have to be added to it as an expert first.

### The PDF is deferred, and that is the only thing that is

Marcel's call, 2026-09-22. Nothing in the rework generates a PDF — no dompdf —
and `03-invoices.md` deferred the QR bill and the participation confirmation to
whichever chunk builds the document pipeline. Building one here would set the
letterhead conventions for all three from the smallest of them.

What is *not* deferred is the check in front of it, which is the half that was a
rework question. The route arrives already gated.

Worth noting what the PDF holds that the screen does not: **phone numbers and
email addresses**. Legacy's screen shows name, town and firm and nothing more —
`EventParticipantsResource` hands the email out only to an admin and the expert
view never renders it — so the contact details exist on that path alone, which
is exactly what makes the missing check matter.

### Three defects, all in code that was already built

1. **The nav lit *Experten* on every screen of the expert portal.** The header
   matched by path prefix — `request()->is('de/experte*')` — and
   `/de/experte/profil` begins with that segment. Legacy is immune because it
   matches by **route name**: its pattern is `page.expert` exactly and its
   portal route is `de.page.expert.profile`. Matched by route name here now,
   which also means the two items whose pages arrive with chunk 04 need no
   special case while they point at `#` — a pattern that matches nothing is
   simply false.

   Found in the browser on the first screen, and it could not have been found
   before: the route did not exist when the header was built.

2. **The student's course thread was rendered inline, and that is not legacy's
   design.** Both portals draw the thread through
   `shared/modules/messages/Index.vue`, and what it draws is a **row** — date,
   sender, 35 characters of the body — with an *Anzeigen* that opens the message
   in a lightbox. The student screen printed the subject, the date and the whole
   body into the collapsible. Readable, and a design nobody chose.

3. **And its course materials the same way.** `files/components/ListItem.vue`
   draws four columns — name, uploaded at, size, buttons. The student screen
   drew two and put the size in parentheses, at `round($size / 1024 / 1024, 1)`
   where legacy's filter is base **1000** with two decimals and the trailing
   zeros trimmed. 1,536,000 bytes reads *1.54 MB* on production and read *1.5
   MB* here.

Both of the last two are now one component used by both portals —
`x-row.message` and `x-row.file` — which is what legacy has and what
building the second portal made obvious.

### The lightbox inherits a line height from where it sits, not from what it is

The one measurement worth keeping. `.message__inner` is the **third** box in
legacy's overlay family and it restates `%lightbox > div` at a higher
specificity:

| | modal | lightbox | message |
|---|---|---|---|
| border | 3px | 2px | 2px |
| box | 600 flat | 600–900 | **480–700** |
| padding | 24/16, 32/24 from `lg` | 12, 24 from `sm` | **8, 16 from `sm`** |

Measured against the live stylesheet with legacy's markup rendered into it, and
confirmed against the rework at the same width: 480px wide, 16px of padding, a
2px `#505050` border, a header at `mb-24 pb-8` over a 1px rule, `.text-xsmall`
labels at 16px, and a footer at `mt-24 pt-8` over the same rule.

**And the nesting is load-bearing.** `Item.vue` renders a `<div>` holding the row
*and* the overlay as siblings — inside the collapsible, which gives the box its
16/18px type, but **outside** `.stacked-list-item`, which is the only thing on
the page setting line height to 1.4. Built with the overlay inside the
`<article>` first and the box came out at 1.4 against production's 1.3: 1.8px on
every line of a message that can run to a screenful. The wrapper `<div>` looks
like nothing and is the whole fix.

### The composer is a form, and that is the bigger departure

Legacy's is TinyMCE plus a `vue-dropzone` that posts each file to `/api/file` as
it is dropped and sends a list of uuids with the message.

- ~~**The body is a `<textarea>`.**~~ **A tiptap editor since 2026-09-23**
  (Marcel), with three of legacy's eight buttons — see *The composer's editor*
  below. Without JavaScript the textarea underneath is still the field, and
  its blank lines still become paragraphs, escaped first.
- **The attachments come with the form.** One multipart POST, so an abandoned
  draft leaves nothing behind. Legacy's eager upload is why **11 of its 44 files
  are attached to nothing at all**.

~~What is lost is the thumbnail strip and the per-file remove.~~ **The drop box
came back on 2026-09-23** (Marcel), without the eager upload. `<x-form.file-input>`
draws legacy's box — measured on the live expert upload at 1482px and matching
to the pixel — over the same `<input type="file">`, and
`js/site/components/file-drop.js` writes each dropped file into the input's
`files` through a `DataTransfer`. So a drop fills the field and nothing leaves
the browser until the form is sent; the controllers did not change.

- **The border is 1px solid black, teal on hover**, not legacy's 2px dashed
  grey — changed the same day to match the composer's editor (Marcel).
- **The message is German**: *Dateien hierher ziehen oder klicken*. Legacy's
  *Drop files here to upload* is `vue2-dropzone`'s built-in default, not copy
  anybody wrote.
- **The list under the box is new.** Legacy lists a file once it has
  *uploaded*, with a download link and a description field that belong to a
  stored file. These are not stored yet, so each row is a name, a size and a
  cross to take it back out.
- **`accept` is checked in JavaScript too**, because the attribute only filters
  the dialog and a drop ignores it. The rejection is legacy's own sentence.
- **And on the server, which legacy never did** (Marcel, 2026-09-23). The list
  lives in `App\Support\DocumentTypes`: legacy's nine plus JPG, PNG and TIFF
  (HEIC was added and taken out again the same day as unnecessary), used by
  both requests and both forms. Checked by extension *and* by sniffed content,
  so a program renamed `handout.pdf` fails too. The content side is
  `mimetypes` with a deliberately wide list, not `mimes`, because libmagic
  reports some old `.doc`/`.xls` files as `application/vnd.ms-office` or
  `CDFV2`, which map to no extension.
- **Legacy's disabled *Speichern* is CSS**: the input is `required`, so it is
  `:invalid` until it holds a file, and the button reads that through
  `group-has-[:invalid]`. It works the same with no JavaScript.
- Without JavaScript the box stays `x-cloak`ed and the styled native input is
  what the visitor gets.

The dashboard's image field is a separate uploader, and deliberately so: it
uploads straight into the media library and crops there, which is the ported
forrerzimmermann Vue UI in chunk 04.

### The composer's editor — tiptap in TinyMCE's clothes, 2026-09-23

Asked for at parity, then cut down on the evidence: **none of legacy's 255
messages uses any formatting.** The port copies bodies verbatim
(`PortMessages.php:92`) and every tag in them is a `<p>`. So the toolbar is bold,
a bullet list and a link (Marcel's call between full parity, this, and keeping
the textarea), and the schema is cut to match (`js/shared/editor.js`) so a
shortcut or a paste cannot bring back what the toolbar left out.

- **tiptap, as forrerzimmermann uses it** — v3, StarterKit plus Link — but
  `@tiptap/core` under Alpine rather than `@tiptap/vue-3`, since the public site
  has no Vue. The schema lives in `js/shared/` so the dashboard's `richtext`
  field can import the same one in chunk 04.
- **Loaded on this page only.** Dynamically imported, so the ~145 kB gzipped of
  ProseMirror and tiptap never reach another page; the manifest confirms the
  site entry references it only as a dynamic import.
- **TinyMCE 5.10.9's proportions, the site's clothes.** Measured on legacy at
  1482px: the 320px box, the 39px toolbar, 34px buttons with 24px icons. The
  first cut also took the oxide skin's colours and system font to the pixel,
  and looked like somebody else's widget; Marcel asked for it to look like the
  other controls instead — 1px black lines, Effra, teal for hover and active.
  The text inside is `x-ui.rich-text`'s, at the size the thread shows it,
  so the composer previews the message. The icons are TinyMCE 6.8's, which is
  MIT; 5.x is LGPL.
- **`[&_li>p]:mb-0` went into `x-ui.rich-text` too.** tiptap wraps a list
  item's text in a paragraph, and the paragraph margin spaced the list out in
  the thread as well. TinyMCE's lists — every course description — have no
  `<p>` in an `<li>`, so they are untouched.
- **The link dialog is a bar.** Legacy's is a modal with four fields; this asks
  for the address only, adds `https://` or `mailto:` to what people actually
  type, and sits over the text so the box keeps its height.
- **Cleaned on the way in** by `App\Support\MessageHtml`
  (`symfony/html-sanitizer`): `p br strong ul li` and `a[href]` with `http`,
  `https` or `mailto`, nothing else. `RichText`'s `strip_tags` on the way out
  keeps attributes, so `<a href="javascript:…">` would have survived it. An
  emptied editor still sends `<p></p>`; the request cleans before it validates,
  so `required` sees nothing.
- **Unlisted elements are dropped with their text**, not unwrapped. Unwrapping
  sounds kinder and leaks: the body context will not register a drop for a
  `<head>` element, so a `<style>` left its CSS behind as text. The schema has
  already reduced a paste to the allowed tags, so only a hand-made request
  carries anything else.

**The trap inside it**: `UploadMedia` leaves the file in `temp/` and
`AttachMedia` is what moves it to `uploads/`. `PostMessage` takes an
`attachments` array and only *associates* each row — which is right for the API,
where the files were uploaded by an earlier request and are already in place.
Handing the form's uploads to it instead writes rows whose files are in the wrong
directory, and `MediaController` answers 404 for every one. The test pins both
directories.

### Small things

- **The seat count is `12 / 14 Teilnehmer`, with `&thinsp;` either side of the
  slash**, and it goes in the same box as the fee rather than beside it —
  legacy renders a bare `<div>` around both whether or not either is shown.
  Three flex children in a `justify-between` column would have spaced the count,
  the fee and the button evenly across it.
- **No fee and no `mit …` on these rows.** What a course costs is the student's
  question, and naming the expert on the expert's own screen is noise. Both are
  props `x-row.event` already had.
- **`Vergangene Kurse` keeps its *Detail* button**, where the student's
  *Absolvierte Kurse* loses everything but the link. An expert still wants the
  participant list of a course that has run: it is who was in the room.
- **A cancelled course shows its cancelled bookings.** Legacy's
  `$event->isCancelled() ? $event->cancelledBookings : $event->bookings` reads
  like a trick and is the right answer — calling off a course cancels every seat
  on it, so the live list would be empty and the expert would lose the list of
  people they have to apologise to. The rework adds the word *annulliert* beside
  each, which legacy does not: there, the two states are indistinguishable.
- **`belongs_to_message` has never hidden anything.** Legacy hides the *Löschen*
  on a course document that also belongs to a message, and its `fileables` pivot
  keeps the two sets disjoint — 13 event files, 20 message files, no overlap. In
  the rework it cannot arise: a `media` row has one owner, and
  `MediaPolicy::delete` admits only a file whose owner is an Event.
- **The delete is a form, not a link.** Legacy's *Löschen* opens a
  `<notification>` and then DELETEs over axios. Here the button opens the same
  confirmation and the confirmation submits a hidden form — a POST with a token,
  rather than something a prefetcher can fire. `$store.confirm` holds the id of
  that form and nothing else, so one dialog serves the page where legacy renders
  one per row.
- **The message is recorded but not mailed, and the toast says so.**
  `PostMessage` writes the recipient rows; there is no Mailable anywhere in the
  rework yet, on this path or the checkout's. *Die Nachricht wurde erfasst.* is
  the honest sentence until there is one.

## The Experten pages — 2026-09-24

`/de/experten` and `/de/experte/{slug}/{uuid}`, legacy's `ExpertController`
rebuilt. Measured against production at 1482px on the same day: every card,
the overlay, the hero and the course list land on production's pixel, save the
arrow, which is 1px off because the icon is 30px wide and legacy's is 29.924.
**Phone width is not measured** — the classes carry legacy's breakpoints from
the SCSS, and `resize_window` cannot be trusted to prove them.

- **The list is the course card without the filter.** Legacy wraps the grid in
  `<course-filter>`, which draws nothing on this page, so it is one full-width
  12-column grid: `span-6`, `span-4` from sm. The overlay lists the courses
  under *Kurse:* as `%unordered-list` — disc, 20px margin on the item — and
  pushes the arrow to the far edge (`icon-arrow-right:after` is
  `space-between`).
- **The expert page is the course page's hero in bold.** The same
  `content-text-media`; legacy's `is-course` modifier is what makes the course
  column regular weight, and this page does not carry it.
- **The URL keeps its uuid.** A person has no slug column — legacy derives it
  from the name through `SlugHelper` — so the uuid is what resolves, as it
  does on the live site, and a stale slug 301s to the current one. The slug is
  legacy's spelling exactly (`daniel-naehring`, `guenes-direk`), checked
  against all ten live URLs. See [[SiteUrl::expert]].
- **The course list is legacy's `getCourses()` plus three filters**: published,
  not cancelled, published course. Legacy lists unpublished and cancelled dates
  too, which can link to a 404. On the 2026-09-11 data the filters change
  nothing for any of the ten experts. **Order is by event id**, not date,
  because legacy reads the pivot unordered and gets entry order — by date,
  seven of the ten lists come out reshuffled. Checked expert by expert.
- **`User` finally reads its media.** `port:media` had written 48 rows against
  `User` since 2026-09-18 — a teaser, a visual and an Open Graph crop for each
  of 16 people — and nothing could read them back. `User` now uses `HasMedia`.
  That closes the `User` half of the *count-check the morphs* item in
  `Open-Questions.md`. The card takes the `is_teaser` row strictly, not
  `teaser()`, which would fall back to a crop of the 16:9 visual where legacy
  shows a placeholder.
- **The local names are not the live ones.** The dev database is pseudonymised
  (`user6@example.test`, invented names) but keeps the uuids and the order, so
  compare the two sites by uuid, not by name.

## The Kontakt page — 2026-09-24

`/de/kontakt`, a `Route::view` — legacy's controller existed only to hand the
page its team members. Measured against production at 1482px the same day:
every block, heading and card lands on production's pixel, the
Datenschutzerklärung included (14,845px, element by element). Phone width not
measured, as with Experten.

- **Team is not built, because it has never rendered.** Legacy shows
  `team_members` with `publish` set and the table is **empty** — so the
  collapsible has never appeared on the live site. `08-accounts.md` lists the
  table for chunk 04; the review gives the team its own page in phase two.
- **The copy is legacy's partials, converted by script** — `__('…')` unwrapped,
  and the `<h2>`s legacy leaves open inside its asides closed. Carried
  verbatim otherwise, save one fix: legacy's *Escher-Wyss-PlatzWer* runs two
  sentences together and reads *Escher-Wyss-Platz. Wer* here (Marcel,
  2026-09-24). The live site still has the typo. `x-card.text` is
  `article.card-text`, with a `privacy` flag for the one card that has its own
  headings.
- **The AGB is a file in `public/media/downloads/`**, at legacy's path,
  because the Impressum links it there. Legacy's same folder holds two older
  Datenschutzerklärung PDFs that nothing on the site links; they were left
  behind.
- **The map loads Google only with a key.** `GOOGLEMAPS_APIKEY`, legacy's
  name, into `services.google_maps.key`. The key is the client's and belongs
  to production; without it the page draws the map's 16:10 grey box, so the
  layout holds locally and in tests. Loading Google on page view is the same
  consent question as the Elfsight widgets, and legacy asks nothing.
- **Two changes to `x-ui.collapsible`**, both for this page and both
  harmless to the others: `last` drops the 64px under the final block
  (`.container:last-of-type`), and a block that starts **shut** is now
  `x-cloak`ed. Without it the 14,000px Datenschutzerklärung painted open for
  a frame before Alpine closed it. The two student-portal blocks that start
  shut get the same fix.


## Where a component goes — 2026-09-24

`resources/views/components/` was `site/` plus a flat pile of 35 files. The
prefix separated nothing: the dashboard is Vue, so every Blade component serves
the public site or the PDFs. It is now grouped by what a component *is*
(Marcel, 2026-09-24):

| Folder | Holds | Tag |
|---|---|---|
| `layout/` | the page shell and what only it uses: `site`, `header`, `article` | `x-layout.article` |
| `ui/` | primitives with no domain: button, modal, toast, collapsible, lightbox, rich-text, back-link, map | `x-ui.button` |
| `form/` | controls that post: field, select, checkbox, file-input, editor | `x-form.field` |
| `card/` | legacy's `components/cards/` — course, event, expert, text | `x-card.course` |
| `row/` | a line in a `.stacked-list` — basket, event, document, file, message | `x-row.event` |
| `dialog/` | a store-driven pair of modals, once per page — basket, booking | `x-dialog.basket` |
| `course/` | course-only pieces that are neither card nor row: filter, event-state | `x-course.filter` |
| `icon/` | one SVG per file, including the logo | `x-icon.cross` |
| `media/`, `documents/` | the image class component; the PDF layout | |

A new component goes in the folder of its *shape* before its *domain* — a card
for experts is `card/`, not `expert/`. A domain folder is for what has no shape
folder, like the filter. The dashboard's Blade shell is `views/dashboard.blade.php`,
not a component, since nothing renders it as one.

`breadcrumb` and `textarea` were deleted: breadcrumb had no caller since chunk
02 and was in stock Tailwind units; textarea lost its only one to the tiptap
editor (b718c67), whose no-JS fallback is the same control.
