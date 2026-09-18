# Styling conventions

Applies to **both** halves of the app — the Blade public site and the Vue
dashboard. The *why* is in `.rework/00-foundation.md` under *Frontend
conventions*; this is the working reference.

Three of the four differ from stock Tailwind, so a class copied from the
documentation, an AI answer or another project will compile and be **silently
wrong**. That is the reason this file exists.

---

## 0. Where a style goes

In this order. Drop a rung only when the one above genuinely cannot express it.

1. **Utilities on the element**, in the Blade template or Vue component.
   Including descendants — `[&_p]:mb-12` styles paragraphs the editor produced,
   which is the case that usually sends people to a stylesheet.
2. **`@apply`, in a CSS partial.** For selectors that have no element to hang a
   class on: `[x-cloak]`, `::-webkit-scrollbar`.
3. **Vanilla CSS.** Last resort. There is currently none.

The whole of `app.css` is now imports plus one `@apply` rule, and every partial
is `@theme` tokens. If you are about to write a declaration, check rung 1 first —
arbitrary variants and arbitrary values (`bg-[#f9f9f9]`) cover most of what looks
like it needs a stylesheet.

---

## 1. Spacing: 1 unit = 1px

`partials/spacing.css`. The number in a class is the number of pixels.

| Write | Get | Stock Tailwind would give |
|---|---|---|
| `p-16` | 16px | 64px |
| `gap-8` | 8px | 32px |
| `mt-24` | 24px | 96px |

**Multiply any value from Tailwind's docs by four.** Applies to `p* m* gap*
space-* w h size min-* max-* inset top right bottom left leading`.

Why: Tailwind's 4px unit forces every value into a multiple of four, and the
design is full of values that are not — a 70px card header, a 13px label.

## 2. Type: a named scale, no `text-base`

`partials/type.css`. The nine sizes the legacy stylesheet actually uses.

| Class | px | | Class | px |
|---|---|---|---|---|
| `text-xxs` | 10 | | `text-xl` | 18 |
| `text-xs` | 12 | | `text-2xl` | 20 |
| `text-sm` | 13 | | `text-3xl` | 24 |
| `text-md` | 14 | | `text-4xl` | 28 |
| `text-lg` | 16 | | | |

**`text-base` does not exist**, and the names above `xs` do not mean what stock
Tailwind means by them:

| Stock | px | Here, for the same px |
|---|---|---|
| `text-sm` | 14 | `text-md` |
| `text-base` | 16 | `text-lg` |
| `text-lg` | 18 | `text-xl` |
| `text-xl` | 20 | `text-2xl` |

This is the one that bites quietly — `text-sm` compiles either way and is 13px
here, 14px everywhere else.

**No line heights are attached.** Legacy sets them per component (1.2 on a card
heading, 1.3 on body, 1.44 on a lead), so set `leading-*` where you need it.

## 3. Colour: the palette, and only the palette

`partials/colors.css`. Five values, from `viak.ch/resources/sass/config/_colors.scss`.

| Class | Hex | Used for |
|---|---|---|
| `black` | `#000000` | body text, the header rule |
| `white` | `#ffffff` | |
| `teal` | `#46baba` | the brand: headings, links, active nav, the hover overlay |
| `gray-200` | `#eeeeee` | fills |
| `gray-400` | `#969696` | hairlines, de-emphasised text |
| `gray-600` | `#505050` | secondary text, the card category label |

Plus `danger` `warning` `success` `info` for notifications.

**Do not add a colour to make something look right.** There is no `teal-dark`
for a hover and no tint for a badge — an earlier pass invented both. Use an
opacity modifier (`bg-danger/10`) or a palette colour solid. If a genuinely new
tone is needed, add it deliberately and say why.

Mapping for reading the legacy SCSS against this:

| Legacy | | Here |
|---|---|---|
| `$color-primary` | `#000000` | `black` |
| `$color-secondary` | `#46baba` | `teal` |
| `$color-tertiary` | `#969696` | `gray-400` |
| `$color-quaternary` | `#505050` | `gray-600` |
| `$color-light` | `#eeeeee` | `gray-200` |

## 4. Breakpoints: stock Tailwind

`sm` 640, `md` 768, `lg` 1024, `xl` 1280. Legacy's 700/1132/1240 are gone
(Marcel, 2026-09-18), so its `sm` reads as `sm` and its `md` as `lg`.

The only one of the four that needs no translation.

---

## Rich text from the editor

`<x-site.rich-text :html="$html" />`. Course descriptions come out of a WYSIWYG
field, so there is no element to put a class on — the component styles the tags
from its wrapper with arbitrary variants, and the values are legacy's
(`typo/_helpers.scss`, `components/lists/_global.scss`, `layout/_article.scss`).

## The scrollbar

Styled, in `partials/scrollbar.css` — 7px, `#f9f9f9` track, `#bbb` thumb, from
legacy's `scrollbar(7px, #bbb)` mixin, written with `@apply`. Those two colours are **not** palette
entries and should not become any: they are arguments to that mixin and appear
nowhere else.

`overflow-y-scroll` goes with it and lives on `<html>` in the layouts, not in the
stylesheet — so a short page and a long one put the content column in the same
place.

**Do not add `scrollbar-color`.** Chrome 121+ honours it in preference to
`::-webkit-scrollbar`, so setting both silently replaces the design with the
browser's own. Firefox has never had the styling, on the live site or here.

## Layout

Written inline. There is no `.inner-block` or `.grid-12` helper class — the
container sits on `body` and grids use Tailwind's own `grid-cols-12`, so adding
a grid does not mean adding a class first.

## Icons

The legacy set, ported from both of legacy's own — `web/partials/icons/` for
Blade and `shared/components/ui/icons/` for Vue.

- **Blade** — `<x-icon.arrow-right />`, in `resources/views/components/icon/`
- **Vue** — `<ArrowRight />`, in `resources/js/app/components/icons/`

**Deliberately duplicated.** An SFC and a Blade component cannot be one file, and
a build step to generate both would cost more than a dozen small duplicates. The
two sets are not identical: Blade has `basket`, `burger`, `facebook`, `instagram`
and `mail`, which only the public site needs; Vue has `Download`, `Edit`, `Plus`
and `Trash`, which only the dashboard does.

Two normalisations were applied when porting, because legacy's set is
inconsistent:

- **`fill="currentColor"`.** Legacy hardcodes `#1E1E1E`, `#000000` and `#46baba`
  in places, which defeats `text-*`.
- **No `width`/`height` attributes.** Size comes from a class, like everything
  else. Each icon carries its legacy width as a default — `w-26` on the basket,
  `w-24` on the small right arrow — and **the height is left to the `viewBox`**.
  That matters: most of this set is not square, so `size-*` would squash it,
  while a width alone lets the browser derive the height from the intrinsic
  ratio exactly. `w-30` on a 29.924×18 box renders 30×18.

To use a different size, pass a width and let the height follow: `class="w-32!"`.
The `!` is needed because the component's own `w-26` is still in the class list
and CSS order would otherwise decide which wins.

The `viewBox` and every path are untouched. Adding an icon means adding a file;
there is no package and no generator.

Two carry legacy behaviour worth knowing about. `<x-icon.arrow-right />` is a
**pair** of SVGs with different artwork for small and large screens, shown by
media query. `<x-icon.profile />` draws a different figure for a signed-in
visitor — filled rather than outlined — which is the one place the header says
whether you are logged in. `<x-icon.cross size="large" />` takes legacy's size
argument.

## Which half am I in?

| | Public site | Dashboard |
|---|---|---|
| Rendering | Blade | Vue 3 SPA |
| Behaviour | Alpine | Vue + Pinia |
| Entry | `resources/js/site/` | `resources/js/app/` |
| Design target | **the current site, 1:1** | free, it is new |

The public site is a rebuild of the live design on a new stack, so a value there
should trace back to `viak.ch/resources/sass/`. The dashboard has no legacy
design worth keeping and is not held to that.
