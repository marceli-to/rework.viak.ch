@props(['title', 'expanded' => true, 'count' => null, 'last' => false])

{{--
	`components/_collapsible.scss` and `components/lists/_global.scss:53`
	(`%content-list-collapsible`), rebuilt 1:1 — the rule-and-heading blocks the
	course page is made of: Aktuelle Kurse, Videos, Facts, Detailbeschrieb,
	Weitere Informationen.

	The measurements, against production on 2026-09-21: a `#505050` rule 1px on
	a phone and 2px from `sm`, a 14/16/18px bold label in the same grey with
	16px above it and 24px below, 4px between the heading and the body, and
	**64px to the next block**.

	**The block sets its own type size, and that is not decoration.**
	`%content-list-collapsible` is `sm:fs-16x md:fs-18x`, so everything inside —
	the facts columns, the video caption, the further information — is 18px
	where the page around it is 24. Left to inherit, Facts alone rendered 450px
	taller than production.

	It used to carry a `leading-[1.3]` beside that size, because Tailwind's own
	`--text-xl--line-height` of 1.4 landed **on this element** where the body's
	inherited 1.3 could not reach — every list item under Facts 1.8px taller,
	54px over the section. The scale stopped pairing line heights with sizes on
	2026-09-22, so the size is all this says now
	(`resources/css/README.md`, *The scale said no line heights*).

	The 4px looks like it does nothing and does not always: the event list's
	first card has a 32px top margin, which is adjacent to it and collapses it
	away. Under Facts, whose grid has no margin, it is the whole gap.

	**A `<button>` where legacy has `<a href="javascript:;">`.** A disclosure is
	a button — it takes focus, it answers the space bar, and `aria-expanded`
	means something on it. Nothing visual changes.
--}}
{{-- `last` is legacy's `.container:last-of-type { margin-bottom: 0 }` — the
     final block on the page takes no 64px under it. A prop rather than a
     `last-of-type:` variant, because on the course page the last collapsible
     is followed by the browse pair, and there it keeps its margin. --}}
<section {{ $attributes->class(['border-t border-gray-600 sm:border-t-2 sm:text-lg lg:text-xl', $last ? 'mb-0' : 'mb-64']) }} x-data="{ open: @js($expanded) }">
	<h2 class="mb-4 text-md leading-none font-bold text-gray-600 sm:text-lg lg:text-xl">
		<button type="button"
			class="relative block w-full py-8 pb-12 text-left transition-colors hover:text-gray-400 sm:py-16 sm:pb-24"
			@click="open = ! open"
			:aria-expanded="open">
			{{ $title }}

			{{--
				How many are inside, **while it is shut** — legacy's
				`Count.vue`, shown by `Collapsible.vue` on `items.length > 0 &&
				!isOpen`. The portal's four lists are the only callers; the
				course page passes nothing and renders as before.

				`<strong>` and a plain space, which is what production draws.
				`%content-list-collapsible` styles `> h2 a span` with a 12px
				margin and regular weight — and `Count.vue` renders a `strong`,
				so **that rule has never matched anything**. Measured on the
				live stylesheet: 18px, bold, `#505050`, separated from the title
				by the template's own whitespace. Ported as it renders, not as
				it was meant to.
			--}}
			@if ($count)
				<strong x-show="! open" class="font-bold">({{ $count }})</strong>
			@endif

			{{-- Legacy's chevron is a CSS triangle rather than an icon
			     (`%icon-chevron-up` / `-down`): 12×9, borders only. --}}
			<span aria-hidden="true"
				class="absolute top-1/2 right-0 block size-0 -translate-y-1/2 border-x-[6px] border-x-transparent"
				:class="open ? 'border-b-[9px] border-b-current' : 'border-t-[9px] border-t-current'"></span>
		</button>
	</h2>

	{{-- An open block has no `x-cloak`: there is nothing to hide before Alpine
	     starts, and without JavaScript it stays open, which is the readable
	     outcome. **A shut one does**, or it paints open and then snaps closed —
	     which on Kontakt is the whole Datenschutzerklärung, 14,000px of it,
	     for a frame. --}}
	<div x-show="open" @unless ($expanded) x-cloak @endunless>{{ $slot }}</div>
</section>
