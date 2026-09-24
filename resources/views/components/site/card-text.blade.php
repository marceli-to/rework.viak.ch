@props(['aside' => null, 'privacy' => false])

{{--
	`components/cards/_text.scss` — `article.card-text`, the rule-topped row the
	Kontakt page's collapsibles are made of: a heading in a `span-4` aside
	against a `span-8` column of copy. Measured against production on
	2026-09-24.

	A 1px black rule at every width (the collapsible's own rule is the one that
	thickens at sm), 16px above it and 8px inside it, 32 and 16 from sm. The
	line height is its own: 1.5, then 1.4 from sm, against the page's 1.3.

	Links are `.container-about`'s: black, underlined 2px below the text,
	plain on hover.

	`privacy` is `views/_privacy.scss`, for the one card whose copy has its own
	headings — the Datenschutzerklärung. It spaces them out: 12px under an `h2`
	or a list and 4px under an `h3`, 16 and 8 from `bp-md`.
--}}
<article {{ $attributes->class([
	'relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:leading-[1.4]',
	'[&_h2]:font-bold [&_h3]:font-bold',
	'[&_p]:mb-12 lg:[&_p]:mb-16 [&_p:last-child]:mb-0',
	'[&_ul]:list-disc [&_li]:ml-20',
	'[&_a]:underline [&_a]:underline-offset-2 [&_a:hover]:no-underline',
	'[&_h2]:mb-12 [&_h3]:mb-4 [&_ul]:mb-12 lg:[&_h2]:mb-16 lg:[&_h3]:mb-8 lg:[&_ul]:mb-16' => $privacy,
]) }}>
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<aside class="sm:col-span-4">{{ $aside }}</aside>
		<div class="sm:col-span-8">{{ $slot }}</div>
	</div>
</article>
