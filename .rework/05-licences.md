# 05 — Software licences (being built)

The second thing VIAK sells.

## Status

Not built. Shape settled 2026-09-17 against the client's answers and the mockups
in `history/mockup/`, then **revised 2026-09-29** against the client's product
list: this replaces an existing shop, the catalogue has three levels rather than
two, and an order line has a quantity. See *The real catalogue* below. The
questions that list raised went to the client on 2026-09-29 and are under *Open
questions*; prices and article numbers are the one that blocks an import.

**2026-10-08: all seven answered**, with an updated list carrying prices and
article numbers. Nothing blocks the import any more; see *The client's answers*
right below, which supersedes parts of *The real catalogue*.

**Built 2026-10-08, first slice: the catalogue.** What is there:

- **Schema** (`2026_10_08_000001_create_licence_catalogue`): `manufacturers`
  (taxonomy-shaped), `licence_products` (software group, maker, title, slug,
  description, `hosts`, `three_years_on_request`, publish, order),
  `licence_variants` (title, `sku`, net `price`, `licence_type`, `access`,
  `platforms`, `note`, `min_quantity`, `listed`, order). Products and variants
  soft-delete, since orders will point at them.
- **Import**: `php artisan licences:import` reads `database/data/licences.json`,
  which *is* the grouping below (generated once from the client's xlsx, which
  stays outside the repo). Rerunnable: what exists is left alone; `--refresh`
  writes the file over it and moves variants to the product the file names,
  for #47–49. 107 variants in 39 products (38 plus *Update Maxwell V5* on its
  own until #47), 9 new software groups, 15 makers.
- **Dashboard → Software** (`/dashboard/software`, in the main menu after
  *Experten*, Marcel 2026-10-08; the phone menu went to 16px, 16 apart, to
  fit a fourth item at 390px): one collapsible per software group, per
  product its maker, variant count, *ab CHF* (cheapest listed non-demo, net)
  and *Nur manuell* when no variant is on the site. The product form
  ([[LicenceProductSchema]]) ends in a collapsible *Varianten* list
  ([[VariantSection]]): rows drag into the dropdown's order, a pencil each to
  the variant's own form (`/dashboard/software/{product}/variante/{uuid}`,
  [[LicenceVariantSchema]]), a `+` to add. Marcel replaced the first build's
  repeater the same day: nine fields per variant on one page was too much.
- **The three levels are named Software → Produkt → Lizenz** in the UI
  (Marcel, 2026-10-08, later): *Software* is the group (`software`, what
  courses hang off: Rhinoceros), *Produkt* what has a maker and a page
  (`licence_products`: Rhinoceros 8, Bongo 2), *Lizenz* what is bought
  (`licence_variants`: commercial, update, demo). Before, *Software* meant
  both the group and the product, and the group was *Software-Gruppe*. The
  tables keep their names; in the code a Lizenz is still a variant. So the
  page lists *Produkte*, then *Software* and *Hersteller*; the product form
  is *Produkt erfassen* with a *Lizenzen* list; the variant form is *Lizenz
  erfassen*, its name field *Titel*; an order position picks a *Lizenz*.
- **Labels settled with Marcel, 2026-10-08:** the product form opens with
  *Titel*, then *Software* and *Hersteller* each full width. On the variant,
  `access` is *Nutzung*: *Einzelplatz (named)*, *Netzwerk (floating)*,
  *Einzelplatz oder Netzwerk* (the vendors' terms alone meant nothing to him);
  `listed` is *Im Shop bestellbar*, with a hint that unticked means only for
  orders VIAK enters (it is not *Publizieren*, which is the product's);
  *Mindestmenge* is the smallest quantity in the basket. No *Abbrechen*
  button: no dashboard form has one, *Zurück* does that job.
- **Software** (then *Software-Gruppen*) and **Hersteller** are lists on the *Software* page,
  below the groups, each under its own teal title with a `+` like the page's
  own header, not as collapsibles (Marcel, 2026-10-08: first built into
  *Einstellungen* as *Software*, renamed and moved the same day). The page
  has the dashboard's search (`?suche=`): products by name, maker, group,
  variant name or article number; groups and makers by name.
- **The shop's label for a variant is built, not typed** (Marcel,
  2026-10-08): about twenty names in the client's list are only the vendor's
  word (*floating*, *node-locked*, *named*). `LicenceVariant::shopLabel()`
  drops those words (and *Jahresmietlizenz*) from the name and adds Lizenztyp
  and Nutzung in German: *floating* → *Jahresmietlizenz, Netzwerk
  (floating)*; *Teams, named user* → *Teams, Jahresmietlizenz, Einzelplatz
  (named)*. A demo (no Lizenztyp) keeps its name. For the public software
  pages and the checkout, and already the dashboard's variant row, left of the
  article number and price badges (Marcel: name plus type and use read twice).
- **Adding a group or a maker from the licence form:** a `+` at the right of
  the *Software* and *Hersteller* labels opens a lightbox with *Bezeichnung*
  ([[TermDialog]]); saved, it joins the dropdown and is picked (Marcel,
  2026-10-08). The product's form is *Software erfassen* / *bearbeiten*,
  as the menu item, not *Lizenz* (Marcel, same day). The settings endpoint now refuses a name its list already
  has, whatever the case, in *Einstellungen* too. Their forms are the settings
  form under `/dashboard/software/liste/{kind}/{uuid}`; badges count courses
  and licences, and a used one is not deleted.

**Built 2026-10-08, second slice: licence orders and the dispatch worklist.**

- **Schema** (`2026_10_08_000002_create_licence_orders`): `licence_orders`
  (number, six digits as bookings and invoices; user, `invoice_id`, frozen
  `invoice_address`, `delivery_email`, `entered_by`, `paid_at`) and
  `licence_order_items` (variant nullable, frozen `title`, `sku`, unit
  `price`; `quantity`, `host`, `position`, **`dispatched_at`/`dispatched_by`
  per line**: one order can go to two resellers on two days). The proposal's
  `state` column became the lines' dispatch; *paid* is `paid_at` or the
  invoice's status, not a copy of it.
- **[[PlaceLicenceOrder]]**, for the admin now and the checkout later:
  freezes each line (a variant as *Product, shop label*, e.g. *V-Ray, Solo,
  Jahresmietlizenz, Einzelplatz (named)*), then **a priced order is invoiced
  at once**, one `LICENCE` line per order line at 8.1 %, `itemable` the line,
  the description *2 × …, für SketchUp*, and its PDF made. **A free order has
  no invoice and is paid when placed**: the *A free order* proposal, built.
- **Backoffice → Bestellungen** (`/dashboard/bestellungen`): *Offene
  Bestellungen* (a line still to send, oldest first) and *Versendete* (newest
  first, paged), searched by number, customer, line title or article number.
  Each row has the payment as a badge (*Kostenlos*, *Bezahlt*, *Rechnung
  offen*) and *1 von 2 versendet*. The `+` picks the customer in a lightbox.
- **The order's page** (`/dashboard/bestellung/{uuid}`): customer, *Lizenzen
  an*, the invoice (PDF link) and per line *Versendet*, which turns into a
  badge with date and who; *Versand offen* undoes a mistaken tick.
- **Bestellung erfassen** (`/dashboard/kunde/{uuid}/bestellung/erfassen`,
  from the list's `+` only; the customer page lists the orders but enters
  none, Marcel 2026-10-08):
  invoice address from the customer's own, *Lizenzen an* (empty: the
  account's), positions of one select over the whole catalogue (**unlisted
  variants marked *nicht im Shop***) or ***Freie Position*** (Bezeichnung,
  Preis netto), *Host-Software* where the product has hosts, *Anzahl*
  starting at the minimum but not held to it, and the running total.
- Not decided, so not built: **no mail** goes out yet, neither to the
  customer (the invoice is in *Rechnungen* to send) nor to VIAK; that is the
  checkout's design ([[10-mail]]). Dispatch does not wait for payment, since
  #2 is still open: the worklist shows both and leaves it to VIAK. An order
  cannot be cancelled yet.

**Next, in order:** the public software pages and the checkout
(`13-checkout.md`), which need the design review the mockups still lack.

**Built 2026-10-08, third slice: the software pages** (Marcel: "as similar
to the course page as possible", the list with its filter too).

- **Where the copy lives: on the Software**, the level the page is about
  (Rhinoceros), not the product. Migration
  `2026_10_08_000003_add_page_to_software` gives `software` the course's own
  columns by the course's names, all translatable: `slug`, `subtitle`,
  `short_description`, `full_description`, `information` (*Weitere
  Informationen*, one column where a course has two: Marcel asked what the
  unlabelled second editor was for, and it went),
  `seo_description`, `seo_tags`; images through the media table (the course
  form's *Bilder*, owner `software`); categories in `software_category`, the
  courses' own categories. Every existing row got its slug. The slug is made
  once in `Software::booted()`, whichever door creates the row (form,
  settings list, the product form's `+`, the import); renaming keeps it.
- **Dashboard → Software is one list since, drawn as *Kurse*** (Marcel,
  2026-10-08: products and a separate *Software* list made no sense once a
  software had a page): a collapsible per software (all of them, by name,
  dimmed unpublished, products counted), the **pencil** on it to the
  software's form, its products inside, a **`+`** under them adding a
  product already filed under it (`?software=`, the form's new `prefill`).
  The title's `+` adds a software. **Hersteller went back to
  *Einstellungen*** the same day (Marcel); the `+` beside the product form's
  select still adds one.
- **The software form**: the pencil and the title's `+` open
  *Software erfassen / bearbeiten* ([[SoftwareSchema]], `views/Licence/Software.vue`,
  `/dashboard/software/liste/software/{uuid}`), the course form minus number,
  fee, facts, PDF text, levels, languages and videos. Delete refused while a
  course or product uses it.
- **`/de/software`**: the course list's page with a card per software
  (`card/software`: category, title, square teaser; overlay maker(s),
  number of products, *ab CHF … exkl. MWST*) and **the course list's filter
  panel**, now taking its selects as a prop ([[SoftwareFilter]]): the
  categories as links, then *Hersteller*, *Lizenztyp*, *Plattform*,
  *Nutzung*. A software matches when one of its listed licences does; a
  licence for *Einzelplatz oder Netzwerk* counts as both. Options only where
  something carries them. The header's *Software* links here now.
- **Only software with something to order is on the site**
  (`Software::onSite()`): published, with a published product that has a
  listed licence. Software only courses use (SketchUp, Godot) has no page,
  and a maker whose products are all hidden (Gemvision) is not offered.
- **`/de/software/{slug}`**: the course page's hero and collapsibles.
  *Aktuelle Kurse* becomes **Lizenzen: every product** (Marcel's pick over a
  page per product), each a row like an event's (`card/product`): name and
  maker (+ description, *3-Jahreslizenz auf Anfrage*) in `span-4`, then in
  `span-8` the licence select (`shopLabel()`, listed only), *Hostsoftware*
  where the product has hosts, platforms, *Stück* (starts at the minimum),
  the price (a demo *kostenlos*), *In den Warenkorb*. Two columns, not the
  event's three: the labels run long. Under the list *Preise exkl. MWST.*
  Then **Kurse** (the courses that teach it, as course cards),
  *Detailbeschrieb*, *Weitere Informationen* (`span-8`), *Kundenmeinungen* (testimonials
  whose subject is the software, as a course's), and *Weitere Software*.
- **The button does nothing yet**: the basket is courses only and behind a
  login; adding licences is the checkout (`13-checkout.md`), next.
- Left out against the wireframe, because the course page has neither: the
  Vorhaben tags and the image gallery. **3d-software.ch's URLs** and where
  they redirect: a cutover question.

## The client's answers — 2026-10-08

The client sent the list back (`Software_Lizenztypen_claude.xlsx`, same name, now
with *Preis ex. MWST* and *Artikelnr.*) and answered 33–39 in one mail.

| # | Answer | What it does to the shape |
|---|---|---|
| 33 | **Every row has a price and an article number.** The column says *ex. MWST*: prices are **net**. | The import is unblocked. VAT is added on top at 8.1 %, as already decided for licences. All 107 article numbers are unique. |
| 34 | **No 3-year product.** "3-Jahreslizenz auf Anfrage erhältlich" is information shown on the product. | `term_years` and the second select go. One select per product. A product flag prints the sentence; it is not a variant and not priced. |
| 35 | **A demo is a free order.** All 10 demos are CHF 0. | A demo is a variant at price 0, ordered like any other. See *A free order* below. |
| 36 | **Hidden products are never ordered on the site.** VIAK records the products that are ordered regularly (Rhino EDU…) and enters the order by hand when one comes in by mail or phone. | *Not listed* means: in the admin's order form, never on the site, not reachable by link. Closes question 1, Bildung: the site never sells an EDU licence. |
| 36 | **The blank line is wanted back.** Today VIAK creates a manual order, adds an "empty product" and types title and price; there are too many one-off variations a year to keep them all as products. | The admin order gets a free line: title and price typed in, no variant. Already proposed under *The blank product*; now a requirement. |
| 37 | **Variants as a dropdown, consistently, updates included.** | Updates and upgrades are variants of the product they update, not products. |
| 38 | **A note is enough** for "nur zusammen mit Neulizenz". | No dependency rules in the basket. A free note on the variant. |
| 39 | **No other minimum or maximum quantities.** | `min_quantity` stays for the four Teams variants (3). No maximum. |
| — | Twinmotion corrected; MyArchitectAI's demo now has no licence type; MyArchitectAI and V-Ray Render Node 20 Stück are **new products**, and the client wants the render node as one product with the count in the dropdown. | The three data fixes are done. Render nodes were already planned as variants (1 / 5 / 20 Stück). |

### What changes in the shape

- **One select, not two.** Without terms, a product page shows one dropdown of
  its variants. Nothing else selects.
- **Licence type, access and platform move to the variant.** Consistent grouping
  puts perpetual and subscription rows in one product (formZ 10: Core, Pro,
  Pro Jahresmiete, three updates, demo), and platform differs inside a product
  (Enscape Collection is Windows only, Solo is not). Still display only.
- **A free `note` on the variant** carries what the spreadsheet's remark column
  says per row: "nur zusammen mit Neulizenz", "5 Rendernodes", "Inkl. 5 Simnodes",
  "Aktivierung auf max. 2 Computern", "Studio + alle Plugins".
- **`listed` is on the variant, not the product.** Grouping updates into
  products puts hidden rows beside listed ones: Rhinoceros 8 EDU, EDU Upgrade and
  the Lab licences are variants of Rhinoceros 8. The site shows the listed
  variants; a product with none is not on the site at all (MatrixGold, the
  Education Collections).
- **No "Preis auf Anfrage" in the catalogue.** Every row has a price, the
  cheapest is a demo at 0. Proposal: the price is not nullable; on-request goes,
  and an enquiry is the Kontakt form. A one-off price is the blank line.
- **Host software stays a choice frozen on the order line** (Maxwell V5 plugin,
  RealFlow Plugin), unchanged.

### A free order — proposal

A demo costs 0, so a basket of only demos has nothing for Stripe to charge
(Stripe Checkout refuses a zero amount) and nothing to invoice. Proposal: a
zero total skips payment, the licence order is created **paid**, no invoice is
raised (a CHF 0 invoice would only post noise to Run My Accounts), and it lands
on the dispatch worklist like any other, because VIAK still has to get the demo
from the vendor. A demo beside a priced licence is a 0 line on that invoice.
Touches `13-checkout.md`.

### Grouping proposal

The client asked for it, the import needs it, and nobody has drawn it yet. 107
rows become **38 products**; *(hidden)* marks variants not listed. Product names
lose "(Jahresmietlizenz)", which moves to the variant's licence type.

| Group | Product | Variants |
|---|---|---|
| Rhino | Rhinoceros 8 | Vollversion, Update; *(hidden)* EDU, EDU Upgrade, Lab 30 seats, Lab 30 seats Upgrade |
| | Bongo 2 | Vollversion, Update |
| | VisualARQ 3 | Vollversion, Update; *(hidden)* Educational |
| | Lands Design 6 | Vollversion, Update |
| | Karamba3D PRO | Jahresmietlizenz, Demoversion |
| | MatrixGold *(hidden)* | one |
| | Drakon | one |
| V-Ray | V-Ray | Solo named, Premium floating, Collection named, Collection floating |
| | V-Ray Render Node | 1, 5, 20 Stück |
| | V-Ray Education Collection *(hidden)* | 0–14, 15–29, 30–49, 50–74, 100+ seats |
| | Chaos Vantage, Chaos Phoenix, Chaos Scans | one each |
| Twinmotion | Twinmotion | Jahresmietlizenz, Demoversion |
| Unreal Engine | Unreal Engine | Jahresmietlizenz, Demoversion |
| Veras | Veras | Pro named, Pro floating, Ultra floating, Demoversion |
| formZ | formZ 10 | Core, Pro, Pro Jahresmietlizenz, Update 9/8/7 auf 10, Demoversion |
| Cinema 4D | Cinema 4D | Einzelperson, Teams named (min. 3), Teams floating (min. 3) |
| | Maxon One | Einzelperson, Teams named (min. 3), Teams floating (min. 3) |
| | Rhino.io | one |
| Enscape | Enscape | Solo named, Premium named, Premium floating, Collection named, Collection floating, Demoversion |
| | Enscape Education Collection *(hidden)* | 0–14, 100+ seats |
| Corona | Corona | Solo named, Premium floating, Collection named, Collection floating |
| | Corona Render Node | 1, 5 Stück |
| KeyShot | KeyShot Studio | Professional, Business, Web, VR |
| Lumion | Lumion View | Jahresmietlizenz, Demoversion |
| | Lumion Pro | named, floating, Demoversion |
| Maxwell Render | Maxwell V5 Bundle | node-locked, floating, Update node-locked, Update floating |
| | Maxwell V5 (plugin, host choice) | node-locked, floating |
| | Maxwell V5 Studio | node-locked, floating |
| | Maxwell V5 Rendernodes | mit Neulizenz (note), separat, Update |
| Anima | Chaos Anima | floating, Demoversion |
| ZBrush | ZBrush | one |
| RealFlow | RealFlow 10.5 | node-locked, floating, Plus node-locked, Plus floating |
| | RealFlow Plugin (host choice), RealFlow Simnodes | one each |
| HDR Light Studio | HDR Light Studio | PRO, PRO floating, AUTOMOTIVE, AUTOMOTIVE floating |
| MyArchitectAI | MyArchitectAI | Starter, Studio, Team, Demoversion |

Two calls in it are ours and could go the other way: the Education Collections
are their own hidden products (seat bands of a university licence, not an
edition of V-Ray), while Rhino's EDU rows are hidden variants of Rhinoceros 8.
The catalogue admin lets VIAK regroup either way.

### Still open after the answers

- **"Update Maxwell V5, node-locked / floating"** (MXS-1501, MXS-1502): an update
  of the plugin or of Studio? Decides which product they sit under; left out of
  the table above.
- **Lumion Pro Floating**: article number LUM-1005, but its shop URL ends in
  LUM-10260. Which is right? The only mismatch among the 101 rows with a URL.
- **The grouping above**, for the client's nod before the import.

These are #47–49 in `Open-Questions.md`.

The short version: **there is no integration, and a licence is not an entity.**
Fulfilment is a human forwarding an email, and *Meine Lizenzen* is purchase
history. What is left is a catalogue with variants, an order line, and an admin
worklist. Chunk 03 owns the money and is where the real weight sits.

## Fulfilment is manual, and there is no reseller API — answered 2026-09-17

The whole loop, as the client described it:

1. The customer orders the licence on the site and pays.
2. VIAK receives an email.
3. A human at VIAK orders the licence from the reseller.
4. A human at VIAK sends the licence to the customer.

**No integration. No vendor SDK, no API credentials, no sandbox, no third-party
failure modes.** That is the single largest thing this answer removes, and it
takes most of the imagined weight of this chunk with it. What remains on our side
is small and entirely ours:

- a purchasable product,
- a basket line and an order that is not a course booking,
- a notification to VIAK when one is bought,
- an admin surface where a human marks it dispatched.

### What it costs instead

Delivery is at human speed. Nothing can promise a key on the payment
confirmation screen, no queued job can deliver one, and the gap between "paid"
and "has licence" is however long it takes someone at VIAK to work through their
inbox. That gap has to be modelled — an order item that is paid and not yet
fulfilled is a normal state here, not an error — and it has to be visible to the
customer, because they have paid for something they do not yet have.

It also means the dispatch step is the only thing standing between a paying
customer and silence. An email to `info@` is not a work queue. The admin needs a
list of outstanding licence orders that a person can work through and tick off,
and that list is the actual deliverable of this chunk.

## Anyone may buy — answered 2026-09-17

Students and non-students both. A licence sale does not require a course
booking and does not require ever having been a student.

This is the structural half of the answer, and it lands on chunk 03, not here:

- **`invoices.booking_id` cannot survive.** A licence-only order has no booking.
  That alone does *not* force Order/OrderItem — a polymorphic `invoiceable`
  solves it, and more cheaply. The open question is whether one checkout produces
  one invoice or several; see `03-invoices.md`.
- **A buyer is a user with no booking.** Chunk 01 ported 578 users who are all
  students. The rework now has account holders who have never attended anything —
  which touches the role pivot from chunk 02, the account pages, and whatever
  the checkout does about guests.
- **A basket can hold both.** A course and a licence in one checkout — billed on
  two different triggers, so two invoices on two different days. See below.

## A licence bills on a different trigger — belongs to chunk 03

A course invoice is raised when the **event is confirmed**, not at checkout —
mean lag 27.7 days in the live data. A booking is a commitment; until the course
has the numbers there may be nothing to charge for.

A licence has no such step. No headcount, nothing to call off, available the
moment it is paid for — so it is invoiced **at purchase**.

That means a basket holding a course and a licence necessarily produces two
invoices, on different days. Not a compromise; it falls out of the domain. It
also means `invoices.booking_id` goes (a licence invoice has no booking) while
the scalar `vat` column can survive, since each invoice still covers one billing
event. See `03-invoices.md` for the schema call and for the one question it
raises: whether VIAK orders from the reseller before or after the money arrives.

## What a licence is — answered 2026-09-17

The first answer was "the software as a shop item, that's it". Put next to the
mockups, it resolved into three decisions:

| | Decision |
|---|---|
| **Variants** | **Yes.** A product has purchasable variants — Einzelplatz / Netzwerk / Studierende — at their own prices. The "ab CHF 590.–" on the hub is the cheapest of them. |
| **Preis auf Anfrage** | **Real.** Some software cannot be bought directly at all. ~~Seven of the nine products on `Software.html` are in this state, so it is the common case, not the exception.~~ **2026-09-29:** that count came from the mockup. In the real catalogue nearly everything is sold at a price, so this is the exception. |
| **Meine Lizenzen** | **Purchase history only.** Not a licence with a life. |

### Meine Lizenzen is history — and that is the decision that shrinks this chunk

The mockup shows validity ("Gültig unbefristet", "Gültig bis 03.02.2027"), a
renewal ("Verlängert am 03.02.2026") and a per-licence "Verwalten →". **None of
that is being built.** The page lists what the customer bought and when.

This is worth stating loudly because it removes an entity. A licence is **not** a
record with an owner, a state and a lifespan — it is a line on an order that has
been fulfilled. Everything that would have followed from the other reading goes
away with it:

- no `Licence` model holding a key, a seat count and a validity window,
- nothing that has to notice an expiry, and no scheduled job to notice it with,
- no renewal flow — a renewal is just buying again,
- no "Verwalten", which would have meant either a vendor portal we do not
  integrate with or an account management surface nobody has designed.

It also means **we never hold a licence key.** VIAK forwards it from their own
mailbox; it does not pass through the system. That is one less secret to store,
and it is why the history page can only ever show a product, a variant and a date.

The cost is the customer's: a year after buying an annual licence, *Meine
Lizenzen* will still say they bought it and will not say whether it still works.
That is a fair v1 trade — the licence's real state lives with the vendor anyway —
but it is a deliberate departure from the mockup, not an oversight. **Do not
"fix" it later without asking.**

## The real catalogue — 2026-09-29

The client went through their existing shop with Claude and sent the result as a
spreadsheet (`Software_Lizenztypen_claude.xlsx`, kept outside the repo on
Marcel's desktop): one row per product, with Hersteller, Produktart (Software /
Plugin), Lizenztyp (Perpetual / Subscription), Lizenzzugriff (named / floating),
Plattform, Frontend-Sichtbarkeit, a remark and the shop URL.

### This replaces 3d-software.ch — answered 2026-09-29

VIAK already sells software, at **3d-software.ch**. Nothing above knew that; it
was all derived from the mockups. **The rework replaces that shop.** So the
catalogue is not a blank page to be designed, it is an existing one to be moved,
and the mockup's nine products are not the scale.

The spreadsheet has **no prices**, and the article numbers (RHN-1002, VRY-1001…)
survive only inside the shop URLs. Both were asked for on 2026-09-29, open
question 2 below. We do not scrape them from the old shop: the client owns the
numbers and should hand them over.
**2026-10-08: handed over**, net, in the updated list.

### What is in it

| | |
|---|---|
| Products | **107**, in 17 groups (Rhino, V-Ray, Twinmotion, Cinema 4D, Maxwell…) |
| Manufacturers | 15 — Chaos alone has 35 products, Next Limit 19 |
| Listed in the shop | 94; the **13 hidden** ones are all EDU, lab and university seat-band licences |
| Licence type | 57 subscription, 40 perpetual, 10 none (the demos; MyArchitectAI's corrected 2026-10-08) |
| Demos | 10 |
| Updates and upgrades | 14 |

### Three levels, not two

The 2026-09-17 shape was `software` → variants. The list does not fit it. A
spreadsheet group is **not a product**: the Rhino group holds plugins by five
different makers (Bongo, VisualARQ, Lands Design, Karamba3D, Drakon), and the
client's own dropdown example splits V-Ray from V-Ray Rendernode, both in the
V-Ray group. So:

```
software            the group — Rhino, V-Ray, Maxwell…  (what courses hang off)
licence_products    V-Ray, V-Ray Render Node, Bongo 2…  manufacturer_id, slug, copy
licence_variants    Solo named / Premium floating / 5 Stück / Update…  price, term
```

**2026-10-08:** the variant loses `term` and gains `sku`, licence type, access,
platform, `note` and `listed`; see *What changes in the shape* above.

- **`software` stays the group**, so courses and licences still meet on one row,
  which was the point for the Vorhaben pages. Eight of the 17 groups already
  exist as course software (Rhinoceros, V-Ray, CINEMA 4D, Twinmotion, Corona
  Renderer, ZBrush, Enscape, Lumion); Unreal Engine, Veras, formZ, KeyShot,
  Maxwell, Anima, RealFlow, HDR Light Studio and MyArchitectAI would be new rows.
  Drakon is a course software row *and* a product in the Rhino group — fine, the
  two levels are independent.
- **The manufacturer moves to the product.** The proposal below put
  `manufacturer_id` on `software`; the Rhino group alone has five makers, so it
  cannot live there.
- **The Hersteller filter** on the hub then filters products, not software.

### ~~Variants carry a term~~

**Superseded 2026-10-08:** there is no 3-year product, only a note on the
product (#34). One select, no `term_years`. Kept for the reasoning.

The client wants the edition in one dropdown and the term (1 / 3 years) in a
second. The cheapest shape that gives that: a variant is a flat, priced row, and
has an **optional `term_years`**. The page shows the distinct editions in the
first select and the terms that edition has in the second. No general
option/combination system — a product with no terms (every perpetual licence)
simply shows one select.

16 products say "3-Jahreslizenz verfügbar" but none has a 3-year product in the
shop today. Whether its price is fixed or 3× annual is open question 3.

The render-node packs (1 / 5 / 20 Stück) are **variants**, not a quantity: each is
its own SKU at its own price.

### Things that are not variants

- **Host software.** Maxwell V5 and the RealFlow Plugin come for Rhino, Archicad,
  Cinema 4D, SketchUp… at one price. Seven hosts × node-locked / floating as
  variants would be fourteen rows with identical prices. Instead the product
  declares a list of choices and the chosen one is **frozen on the order line**,
  like the price. VIAK needs it to order the right thing from the reseller.
- **Quantity.** "Mindestens 3 Lizenzen" on the Cinema 4D and Maxon One Teams
  licences is a quantity with a minimum. `licence_orders` below had no quantity;
  it gains `quantity`, and the variant gains `min_quantity` (nullable).
- **Platform, named/floating on products that allow both, "max. 2 Computer",
  "Studio + alle Plugins".** Display information on the product. Nothing
  computes with them. **2026-10-08:** on the variant, since one product now
  mixes them.

### Hidden products

The 13 EDU and seat-band licences are in the shop and sold, but not listed. That
is not `publish = false` — an unpublished product is one nobody can buy. It needs
a separate *not listed* state. Whether a customer can reach one on the site at all
(a direct link) or VIAK only ever invoices it is open question 5; if the latter,
a hidden product is just a preset for the blank one below.

This answers half of the Bildung question: education licences are real. They
are hidden and sold on request, which is the cheap middle option that question
already proposed.

**Answered 2026-10-08 (#36): the latter.** A hidden variant is only ever picked
by an admin entering an order taken by mail or phone; the site never shows or
sells it. `listed` sits on the variant (see above).

### The blank product — wanted, 2026-10-08

The client's example: someone wants an annual licence for only a few months, VIAK
works out the price by hand and still sends a normal invoice. That is **a licence
order an admin creates**, with a free title and a free price instead of a
variant: `licence_variant_id` becomes nullable, and the order freezes a title as
it already freezes the price. It should still land on the dispatch worklist,
because VIAK still has to order it from the reseller.

Chunk 03 already allows the invoice side: `invoice_items.itemable` is nullable and
the line carries its own description.

### Catalogue admin

The client wants to create, edit and delete products themselves. Expected — the
catalogue is theirs and changes, EDU products are added on demand. A dashboard
screen, not a seeder.

### Data to fix before an import

- "TWinmotion" in three rows (Excel autocorrect).
- MyArchitectAI's demo is typed Subscription; every other demo has no type.
- MyArchitectAI (4 products) and V-Ray Render Node 20 Stück are marked visible
  but have no shop URL — new, presumably.

All three are in the 2026-09-29 questions. **Fixed by the client 2026-10-08**:
MyArchitectAI and the 20-node pack are new products. One new mismatch is open,
Lumion Pro Floating's article number (#48).

## The shape that falls out — proposed 2026-09-17

**2026-09-29:** partly superseded by *The real catalogue* above — three levels,
manufacturer on the product, a quantity and a host choice on the order. Kept as
written for the reasoning.

Small, and entirely ours. **Proposals, not decisions** — written down so the
build starts from something concrete rather than re-deriving it.

### `licence_orders` — the missing parallel to `Booking`

For a course, `Booking` is the thing that exists between checkout and invoicing:
it freezes what was sold, and the invoice arrives later when the event confirms.
A licence has no equivalent, and it needs one. The fulfilment state has to live
somewhere, and the admin worklist needs a table to query.

```
licence_orders   uuid, number, user_id
                 software_variant_id
                 price            frozen at purchase, as bookings.course_fee is
                 invoice_address  json, frozen — same reason as on bookings
                 state            AWAITING_DISPATCH | DISPATCHED
                 ordered_at, dispatched_at, dispatched_by
```

`invoice_items.itemable` then points at `Booking | LicenceOrder`, which is the
same morph pattern `course_taxonomy` already uses.

`dispatched_by` is worth having from the start: fulfilment is a person doing
something manual, and when a customer says the licence never arrived, the
question is who sent it and when.

Whether `state` gains a third value — dispatch before or after payment — is open
question 2 in `Open-Questions.md`.

### `software` outgrows being a taxonomy

`software` is one of five identically-shaped taxonomy tables today: uuid, `json
title`, order, publish. The licence catalogue needs it to be a content entity —
slug, descriptions, SEO, a marketing page — plus **variants**:

```
software              + slug, summary, description, seo_*, manufacturer_id
software_variants     software_id, title, price NULLABLE, order, publish
```

**The nullable price is what makes "Preis auf Anfrage" work** without a second
model: a variant with a price is purchasable, a variant without one renders as an
enquiry. The hub's "ab CHF 590.–" is then `min(price)` over the priced variants.

The mockups also filter by **Hersteller** — Robert McNeel, Chaos, Epic Games,
Maxon — which is just another taxonomy, so the cheapest route is adding
`manufacturers` to the existing taxonomy migration's `TABLES` array. Identical
shape, no new pattern.

**This breaks a symmetry on purpose.** `2026_09_11_000002_create_taxonomy_tables`
deliberately built all five taxonomies from one loop because they differ only in
meaning. `software` now stops being one of the five and becomes a model with a
taxonomy-shaped past. That is the right call — it is the only one of the five
that is a thing customers buy rather than a label — but it should be a decision
rather than a drift.

The payoff is that courses and licences hang off the same `software` row, which
is exactly what the Vorhaben pages need: a curated list of courses *and* licences
for one tool, from one relation.

### The account area gates on being logged in, not on `Role::Student`

`Role::Student` is documented as "books courses". A licence-only buyer books
nothing and may well be a company. Nothing gates on the role yet, so this is a
note for when the SPA routes are built rather than a fix: *has an account* is not
one of the three capabilities, and should not be made into one.

~~The roles stay as they are.~~ **Superseded 2026-09-30**: the student role
goes and every account is a customer (`12-customers.md`). Admin and Expert
stay, so the pivot decision in `02-courses-events.md` holds.

## Open questions

**All answered 2026-10-08**; the answers and what they changed are in *The
client's answers* at the top. What is left from them is #47–49 there.

- ~~1. Is the Bildung tier in scope, with its Nachweis?~~ **No**: EDU licences
  are hidden variants, ordered by mail or phone and entered by VIAK. No proof
  flow, no enquiry variant.
- ~~2. (OQ 33) Prices and article numbers~~: delivered, net.
- ~~3. (OQ 34) The 3-year term~~: not a product, a note on the product.
- ~~4. (OQ 35) Demos~~: a free order.
- ~~5. (OQ 36) Hidden products~~: never on the site; VIAK enters the order. The
  blank line is wanted back.
- ~~6. (OQ 37) Updates and upgrades~~: variants in the dropdown, consistently.
- ~~7. (OQ 38) "Nur zusammen mit Neulizenz"~~: a note.
- ~~8. (OQ 39) Minimum and maximum quantities~~: none beyond the Teams' 3.

Not blocking:

- **Pricing rules** — none yet (asked 2026-09-17). Whether discount codes apply
  to licences, and whether students pay differently, can be decided once the
  catalogue exists.
- **Payment methods** — both software mockups advertise TWINT, credit card and
  invoice. Belongs to chunk 03.
- **Copy at launch.** The mockups are wireframes and their words are filler —
  `Twinmotion-Lizenzen.html` currently promises "Lieferung sofort per E-Mail",
  which a human-in-the-loop process cannot honour, and *Meine Lizenzen* shows
  validity we do not track. Nothing to decide, but the real copy has to say that
  a licence arrives by email once VIAK has ordered it.
