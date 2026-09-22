@props(['html', 'hero' => false])

{{--
	Editor HTML from the database ([[RichText]]).

	The tags come out of a WYSIWYG field, so there is no element to hang a class
	on — which is exactly the case Tailwind's arbitrary variants are for. Styling
	descendants from the wrapper keeps this out of a stylesheet, where it would be
	the only selector-based rule in the project.

	Values from legacy: `typo/_helpers.scss` (p 12px, 16px at desktop; strong is
	bold, not 600) and `components/lists/_global.scss` (ul has no padding, the
	indent is a 20px margin on the item).

	## Links are styled twice in legacy, and this used to port the wrong one

	`layout/_article.scss:46` gives **teal** links with a 3px/1px underline that
	steps to 5px/2px from `bp-sm` — but that rule is
	`article.content-text-media .text-media__body > div a`, which is the course
	page's **teal hero** and nothing else. The collapsibles below it are
	`.container-course .text-item a`, which is `%link-underline`: a flat
	**3px offset, 1px thick, at every width**, and no colour of its own.

	The hero's treatment was applied to both, so every link in *Detailbeschrieb*
	and *Weitere Informationen* came out teal with a heavy underline where
	production has plain black (Marcel, 2026-09-22). Measured on the live page:
	`rgb(0, 0, 0)`, `1px`, `3px`. That is 60 links across two fields against the
	hero's four.

	**The colour is not set here at all**, in either mode, because it does not
	need to be: the hero article is `text-teal` and the collapsibles are black,
	so a link that says nothing inherits the right answer in both places.
	Legacy states `color: $color-secondary` on the hero rule and reaches the
	same result the long way round.
--}}
<div {{ $attributes->class([
	'[&_p]:mb-12 lg:[&_p]:mb-16 [&_p:last-child]:mb-0',
	'[&_strong]:font-bold [&_b]:font-bold [&_em]:italic [&_i]:italic',
	'[&_ul]:m-0 [&_ul]:list-disc [&_ul]:p-0 [&_ol]:m-0 [&_ol]:list-decimal [&_ol]:p-0',
	'[&_li]:ml-20 [&_li]:list-item',
	'[&_h2]:font-bold [&_h3]:font-bold [&_h2]:mb-8 [&_h3]:mb-8',
	'[&_a]:underline [&_a]:decoration-1 [&_a]:underline-offset-[3px] [&_a:hover]:no-underline',
	'sm:[&_a]:decoration-2 sm:[&_a]:underline-offset-[5px]' => $hero,
]) }}>{!! \App\Support\RichText::render($html) !!}</div>
