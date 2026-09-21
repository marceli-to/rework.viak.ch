@props(['aside' => null])

{{--
	`layout/_article.scss:116` — `article.content-text`, the two-column text
	page. A `span-4` aside and a `span-8` column, and legacy uses it for login,
	registration, the password screens and most of the static pages.

	**The grid starts at `sm`.** Below it the two stack as blocks and the
	*column* carries the 24px, which is why this is `sm:grid` rather than a grid
	with a row gap — a grid at every width would add its gap to that margin.

	The aside sticks from `sm` (`%sm\:sticky-t-20`), so a long form scrolls past
	a heading that stays put.

	Type comes from `body`, which is already `text-lg sm:text-xl lg:text-3xl` —
	legacy restates it here and it is the same scale.
--}}
<article {{ $attributes->class(['relative']) }}>
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<aside class="sm:col-span-4">
			<div class="sm:sticky sm:top-20">{{ $aside }}</div>
		</aside>

		<div class="mt-24 sm:col-span-8 sm:mt-0">{{ $slot }}</div>
	</div>
</article>
