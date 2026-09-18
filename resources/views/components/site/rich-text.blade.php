{{--
	Editor HTML from the database ([[RichText]]).

	The tags come out of a WYSIWYG field, so there is no element to hang a class
	on — which is exactly the case Tailwind's arbitrary variants are for. Styling
	descendants from the wrapper keeps this out of a stylesheet, where it would be
	the only selector-based rule in the project.

	Values from legacy: `typo/_helpers.scss` (p 12px, 16px at desktop; strong is
	bold, not 600), `components/lists/_global.scss` (ul has no padding, the
	indent is a 20px margin on the item) and `layout/_article.scss` (links teal
	and underlined, 3px offset / 1px thick, 5px / 2px at desktop, no underline on
	hover).
--}}
@props(['html'])

<div {{ $attributes->class([
	'[&_p]:mb-12 lg:[&_p]:mb-16 [&_p:last-child]:mb-0',
	'[&_strong]:font-bold [&_b]:font-bold [&_em]:italic [&_i]:italic',
	'[&_ul]:m-0 [&_ul]:list-disc [&_ul]:p-0 [&_ol]:m-0 [&_ol]:list-decimal [&_ol]:p-0',
	'[&_li]:ml-20 [&_li]:list-item',
	'[&_h2]:font-bold [&_h3]:font-bold [&_h2]:mb-8 [&_h3]:mb-8',
	'[&_a]:text-teal [&_a]:underline [&_a]:decoration-1 [&_a]:underline-offset-[3px]',
	'lg:[&_a]:decoration-2 lg:[&_a]:underline-offset-[5px] [&_a:hover]:no-underline',
]) }}>{!! \App\Support\RichText::render($html) !!}</div>
