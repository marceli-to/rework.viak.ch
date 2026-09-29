# 11 — Tracking and attribution (not yet built)

Where a customer came from, recorded well enough that VIAK can say what its ad
spend brings in.

## Status

**Not built. Scoped 2026-09-29** from the client's input (below) and a read of
what the live site sends today. Nothing in the rework tracks anything yet, and
**that is a parity gap**, not a neutral starting point: the live site runs a
Google Tag Manager container and a cookie banner, and the rework's layout
carries neither. Cutting over as things stand would switch VIAK's analytics and
ads measurement off.

## What the client asked for

> Was wir unbedingt machen & einrichten müssen, ist ein genaues Tracking der
> Kunden. Ich muss in Zukunft auswerten können, woher die Kunden kommen um
> Conversions zu tracken. Für das würde ich gerne 2 Wege implementieren:
> Automatisch mit Analytics und z. B. dem Meta Pixel für Meta Ads (gleiches dann
> für Google Ads, ChatGPT Ads etc.)
> Manueller Prozess: Im Buchungs-/Kaufprozess ein Popup "Wie wurdest du auf uns
> aufmerksam?" Mit einigen Optionen, die manuell vom Kunden geklickt werden
> können
> Aktuell geben wir einiges an Werbegeld aus, aber ich kann nicht nachvollziehen,
> was wirklich wie viel bringt.

Two ways, then: **automatic** (analytics and ad pixels) and **self-reported**
(a question in the checkout). They answer different things and both are worth
having: pixels see the click but lose everyone who declines cookies or blocks
scripts; the question reaches every buyer but only as well as people remember.

## What the live site does today (measured 2026-09-29)

Read from `../viak.ch/resources/views/web/partials/footer.blade.php`,
`../viak.ch/public/assets/cookieconsent/cookieconsent-init.js`, and the public
container itself (`https://www.googletagmanager.com/gtm.js?id=GTM-M3L7WVP`, which
anyone can download; the tag list below is read out of it).

**The page.** Production-only (`@production`): a Consent Mode default of
`ad_storage` and `analytics_storage` both `denied`, the GTM snippet for
`GTM-M3L7WVP`, and four `type="text/plain"` scripts that orestbida's
cookieconsent v3 unlocks per category to flip consent to `granted`.

**The container** holds fifteen tags:

| Tag | Fires on | Notes |
|---|---|---|
| Google tag, GA4 `G-KS5702S844` | every page | consent-aware (built-in) |
| Conversion linker | every page | keeps the `gclid` |
| Google Ads remarketing (`940420705`) | every page | |
| **Google Ads conversion** (`940420705` / `Aa4HCPPes78CEOHctsAD`), value **CHF 100 fixed** | **any form submit** | see finding 1 |
| GA4 event `Course_Form_Sent` | any form submit | same trigger |
| Universal Analytics `UA-83757430-1`, three tags | page / form / click | UA stopped processing in 2024; dead weight |
| GA4 events `klick_email_adresse`, `klick_telefonnummer` | `mailto:` / `tel:` clicks | |
| GA4 event `min_3_pages` | page view, > 3 pages in session | |
| **Meta pixel** `1348356603888166`, `PageView` only | every page | **Custom HTML**, see finding 3 |

### Findings

These explain most of *"ich kann nicht nachvollziehen, was wirklich wie viel
bringt"*. None is a code bug in legacy's PHP; they are container and banner
configuration.

1. **A booking is never counted as a conversion.** The Ads conversion fires on
   GTM's *form submit* trigger. Legacy's checkout completes with an
   `axios.post` from a button in `checkout/views/Summary.vue`, with no `<form>`
   around it, so the actual purchase fires nothing. What *does* fire it:
   login, registration, the newsletter box, password reset, the contact form.
   Each counts as CHF 100 regardless of what, if anything, was bought.
2. **`ad_storage` can never be granted.** The page unlocks the `ads` category,
   but `cookieconsent-init.js` defines only `necessary`, `analytics` and
   `functionality`. There is no switch a visitor could flip. Google Ads runs
   cookieless for everyone, including people who clicked *Akzeptieren*.
3. **The Meta pixel ignores the banner.** Custom HTML tags are not
   consent-aware, and this one has no consent condition on it, so it loads on
   every page view before and regardless of any choice. It also only sends
   `PageView`: Meta has no purchase or lead event to optimise ads against.
4. **Consent Mode is v1.** Only `ad_storage` and `analytics_storage`; Google's
   EU user consent policy (EEA, UK and Switzerland) has required
   `ad_user_data` and `ad_personalization` as well since March 2024, without
   which conversion and remarketing features for those users degrade.
5. **Small leftovers in the banner**: the *Kontakt* link points to
   `chamgroup.ch`, the privacy link to a February 2023 PDF, and one section
   links `href="#"`.

**The live site is not fixed.** These findings are what the rework must not
carry across; they are fixed in the rework and at cutover, nowhere else. The
client should still hear them, because they explain why the ad numbers VIAK is
looking at today do not add up.

## What to build

### Principle: GTM stays the door, the app supplies the facts

The client wants Meta, Google Ads, "ChatGPT Ads etc." Each network adding its
own snippet to the Blade layout means a deploy per network. Instead, **GTM stays
the single entry point** (the existing container, so history and the Ads link
survive) and the application's job is to (a) ask for consent properly and
(b) push well-formed events into `dataLayer` at the moments that matter. Whoever
runs the ads then wires networks to those events in GTM without touching code.

### Step 1 — Parity: GTM and the banner come across (before cutover)

- A `x-tracking.head` component in the site layout, production-only as legacy
  has it, container id from `config('services.gtm.id')` (`GTM_ID`), not
  hard-coded. Nothing in the dashboard layout.
- The cookie banner, as Consent Mode **v2**: default all four signals `denied`,
  categories `necessary`, `analytics`, `marketing`. `marketing` grants
  `ad_storage`, `ad_user_data`, `ad_personalization`. Fixes findings 2 and 4.
- Styled to the site. The banner is new-ish UI, but it exists on the live site
  today, so parity applies: measure it there first.
- The Google map and the Elfsight widgets are third-party loads on page view;
  whether they wait for consent is the question already open under #18 and in
  `x-ui.map`. Settle it here, once, for all of them.

### Step 2 — Events in `dataLayer`

GA4's recommended e-commerce names, because GA4, Google Ads and Meta's GTM
templates all understand them without mapping:

| Event | Where | Payload |
|---|---|---|
| `view_item` | course page | course id, title, category, price |
| `add_to_cart` | *Buchen* confirmed | event id, course, price, laptop yes/no |
| `begin_checkout` | basket → address | items, value |
| `purchase` | confirmation page, **once** | `transaction_id` = checkout uuid, `value` = net after discount, excl. VAT, `currency` = `CHF`, items |
| `sign_up` | registration completed | none |
| `generate_lead` | Kontakt / Firmenschulung sent (phase two, #24) | form name |

`purchase` is the one that matters. It is pushed server-rendered on the
confirmation page from the stored `Checkout`, flashed once, so a reload does
not count a second sale. The value is the checkout's own number, which is what
Ads and Meta should be optimising against instead of a flat CHF 100 (courses
are VAT-exempt, a laptop is not; see VAT rules in `03-invoices.md`, so *net*
is the comparable figure).

Then in GTM, not in code: the Ads conversion moves from *form submit* to the
`purchase` event with dynamic value, the Meta pixel moves to Meta's consent-aware
template and gets `Purchase`, the UA tags go. **Whoever holds the GTM account
does this**; see questions.

The live site and the rework share one container, and a published change
applies to both at once. So the new setup is prepared in a separate workspace
(or a GTM environment pointed at the rework's staging host) and **published at
cutover**, not before; the live site keeps running on today's version untouched.

### Step 3 — First-party attribution, stored with the checkout

Pixels only see consenting visitors without blockers. So the app keeps its own
record, independent of any third party:

- On the first request of a session, a middleware reads `utm_source`,
  `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, the click ids
  (`gclid`, `gbraid`, `wbraid`, `fbclid`, `msclkid`) and the external
  `Referer`, and keeps them in the session (the session cookie is already
  *necessary*; no new cookie).
- `CompleteCheckout` copies them onto the `Checkout`: a nullable
  `attribution` json column (landing path, referrer host, utm fields, click
  id). Same for the `User` at registration, as *first touch*.
- This only works if the ads' landing URLs carry UTM parameters. Google Ads
  auto-tagging supplies `gclid`; Meta and everything else need UTM templates
  set in each campaign. That is a task for whoever runs the campaigns.

Whether storing click ids against a customer record needs a line in the
Datenschutzerklärung is the client's (or their privacy advisor's) call; the
current one already covers *Zählpixel* and Google/Meta generally.

### Step 4 — "Wie wurdest du auf uns aufmerksam?"

- A **question on the summary step**, not a popup: a single-choice field just
  above *Buchen*, with *Andere* opening a short text field. A modal in the last
  step of a checkout is the one place a popup costs sales. If the client wants
  a popup anyway, it is the same data and the same column.
- Stored on the `Checkout`: `heard_from` (a key) and `heard_from_other`
  (text). Optional or required is the client's call; recommendation is
  optional with no preselection, since a forced answer is a random one.
- Asked **once per customer**: shown on a user's first checkout only, since the
  question is about how they found VIAK, not how they found this course. A
  returning customer's later checkouts inherit nothing and show nothing.
- The options live in `config/tracking.php` as key → label, so the stored key
  survives a relabel. The client supplies the list.

### Step 5 — Seeing it: a dashboard report

A dashboard screen (Vue, [[07-dashboard]]) over a date range: checkouts and
revenue grouped by self-reported source, and by `utm_source` / click-id
network, side by side, with a CSV export. Without this the data exists and
nobody looks at it. GA4 and the ad networks keep their own reports; this one is
the only place that sees *every* buyer.

### Later, not now

Server-side conversions (Meta Conversions API, Google Ads offline conversion
import or enhanced conversions) recover purchases the browser pixel misses. They
send customer data to Meta and Google from the server, which is a bigger
privacy decision and a real integration each. Worth it once steps 1 to 5 show
how large the gap actually is.

ChatGPT's ads: whatever measurement OpenAI offers advertisers when VIAK starts
has to be checked then. UTM parameters on the landing URLs work regardless, and
step 3 records them.

## Order of work

1. Step 1 (parity) and step 3 (attribution capture): no client input needed
   except the GTM access question.
2. Step 2's `purchase` event, then the container changes by whoever holds the
   account, prepared unpublished.
3. Step 4 once the client sends the option list.
4. Step 5.
5. At cutover: publish the new container version together with the switch.

## Questions for the client

Also in `Open-Questions.md` as #29 to #32.

- **#29 Who manages the GTM container and the ad accounts** (VIAK, or an
  agency)? The container changes in step 2 need edit access, and campaigns need
  UTM templates.
- **#30 Which networks, exactly?** Google Ads and Meta today; LinkedIn,
  ChatGPT, anything else planned?
- **#31 The answer options** for *Wie wurdest du auf uns aufmerksam?*, and
  whether it is optional.
- **#32 Popup, or a field in the last checkout step?** Recommendation: the field.

Kurzfassung für den Kunden, zum Weiterleiten:

> Auf der heutigen Website wird eine Buchung nie als Conversion gezählt. Google
> Ads zählt stattdessen jedes abgeschickte Formular (Login, Registrierung,
> Newsletter) pauschal mit CHF 100. Zudem kann niemand Werbe-Cookies zulassen,
> weil der Cookie-Banner diese Kategorie gar nicht anbietet, und das Meta Pixel
> lädt ohne Einwilligung. Die heutige Website bleibt so, wie sie ist; korrigiert
> wird das mit der neuen Website. Dort: echte Kauf-Events mit Betrag, eigene Erfassung der
> Herkunft (UTM, Klick-IDs) bei jeder Buchung, die Frage «Wie wurdest du auf uns
> aufmerksam?» im letzten Buchungsschritt und eine Auswertung im Dashboard.
> Wir brauchen: Zugang zum Tag Manager (oder die Agentur), die Liste der
> Werbekanäle und die Antwortoptionen für die Frage.
