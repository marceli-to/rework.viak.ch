@props(['message'])

@php
	// `truncate(body, 35, '...')`, with the tags taken off first. Legacy hands
	// the raw HTML to a character-count truncate and prints the result with
	// `v-html`, so a message whose 35th character lands inside `<strong>` ships
	// a broken tag to the browser. `Str::limit(strip_tags(…))` is the same
	// preview and cannot do that.
	$preview = \Illuminate\Support\Str::limit(strip_tags($message->body), 35);
@endphp

{{--
	One note in a course's thread — `shared/modules/messages/components/Item.vue`
	([[08-accounts]], [[09-public-site]]).

	**A row and a lightbox, not the message.** The row carries the date, the
	sender, 35 characters of the body and an *Anzeigen*; the message itself opens
	in an overlay. Both portals show the thread through this same module, so
	there is one component here as there is one there.

	**It replaces an inline rendering that was not legacy's.** The student's
	booked-event screen printed the subject, the date and the whole body straight
	into the collapsible when it was built (2026-09-22) — readable, and a design
	nobody chose: `Item.vue` is what production draws on both portals. Found
	while building the expert screen, which needed the same list.

	Geometry, all of it legacy's own:

	| | |
	|---|---|
	| row | `%stacked-list` — the row `x-row.event` draws |
	| columns | date `span-2`, sender `span-3`, preview `span-4`/`md:5`, button `span-3`/`md:2` |
	| on a phone | one *date – sender* line instead of the first two, 24px above the button |
	| overlay | `%lightbox`, white at 90 % |
	| box | 2px `#505050`, **700 max / 480 min**, 8px of padding and 16 from `sm` |
	| header | a `#505050` rule under it, 12/8 then 24/8 from `sm` |
	| footer | the same rule above it, only when there are attachments |

	**The box is the third of three and that is why it is here rather than a
	variant of `x-ui.lightbox`.** `.message__inner` restates the padding, the
	max-width and the min-width of `%lightbox > div` at a higher specificity —
	700/480 against 900/600, 8/16 against 12/24 — so a `variant` prop would be
	three overrides deep on a component whose own docblock already explains why
	the modal is not one.
--}}
{{--
	**The wrapper is load-bearing, and it is legacy's.** `Item.vue` renders a
	`<div>` holding the row *and* the overlay as siblings — so the overlay is
	inside the collapsible, which gives it the 16/18px type, but **outside**
	`.stacked-list-item`, which is the only thing on the page setting line height
	to 1.4.

	Built with the overlay inside the `<article>` first, and the box came out at
	1.4 against production's 1.3 — 1.8px on every line of a message that can run
	to a screenful. Measured against the live stylesheet with legacy's own markup
	in both nestings, 2026-09-22 ([[09-public-site]]).
--}}
<div x-data="{ open: false }">
<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		{{-- `.sm\:hide` — the two columns above, as one line, where there are no
		     columns. 8px under it. --}}
		<div class="mb-8 sm:hidden">
			{{ $message->created_at->format('d.m.Y') }} – {{ $message->author?->name }}
		</div>

		{{-- `xs:hide`, so these two are the desktop form of the line above.
		     4px under each, which is legacy's `mb-1x`. --}}
		<div class="mb-4 max-sm:hidden sm:col-span-2">{{ $message->created_at->format('d.m.Y') }}</div>
		<div class="mb-4 max-sm:hidden sm:col-span-3">{{ $message->author?->name }}</div>

		<div class="mb-4 sm:col-span-4 lg:col-span-5">{{ $preview }}</div>

		{{-- `.xs\:mt-6x` — 24px above the button on a phone, where the columns
		     have stacked; nothing from `sm`. --}}
		<div class="mt-24 sm:col-span-3 sm:mt-0 lg:col-span-2">
			<x-ui.button @click="open = true" title="Nachricht anzeigen">Anzeigen</x-ui.button>
		</div>
	</div>
</article>

	{{--
		The message itself.

		Escape and the backdrop both close it: `.message.is-visible` sets
		`cursor: pointer` on the overlay and `cursor: default` on the box, which
		is the backdrop advertising a close that `Notification.vue`'s dead
		`addListeners()` never wired ([[x-ui.modal]]). Honoured here, as it is
		there.
	--}}
	<div x-cloak x-show="open"
		class="fixed inset-0 z-[200] flex cursor-pointer items-center justify-center overflow-y-auto bg-white/90"
		@click.self="open = false"
		@keydown.escape.window="open = false"
		role="dialog" aria-modal="true">

		<div class="max-h-full w-[90%] cursor-default overflow-y-auto border-2 border-gray-600 bg-white p-8 sm:w-auto sm:max-w-700 sm:min-w-480 sm:p-16">
			{{-- `.icon-lightbox-close` is `position: absolute` 32px in from the
			     corner of the *overlay*, 16 on a phone — not of the box. It is
			     the one control legacy draws with feather's `XIcon`; ours is the
			     site's own cross, as `x-ui.lightbox` already does. --}}
			<button type="button" @click="open = false"
				class="absolute top-16 right-16 z-[3001] block transition-colors hover:text-teal sm:top-32 sm:right-32"
				aria-label="Schliessen">
				<x-icon.cross />
			</button>

			{{-- `.text-xsmall` is 12/14/16, and the label column is `span-2`
			     against the value's `span-10`. 4px between the two rows, 12/8
			     then 24/8 around the rule. --}}
			<header class="mb-12 border-b border-gray-600 pb-8 sm:mb-24">
				{{-- `pr-3x` is on the **first row**, not on its label — legacy's
				     `header > div:first-of-type`, so the 12px comes off the grid's
				     right edge rather than out of the label cell. --}}
				<div class="mb-4 grid grid-cols-12 pr-12">
					<div class="col-span-2 text-xs sm:text-md lg:text-lg">Datum</div>
					<div class="col-span-10 text-xs sm:text-md lg:text-lg">{{ $message->created_at->format('d.m.Y') }}</div>
				</div>
				<div class="mb-4 grid grid-cols-12">
					<div class="col-span-2 text-xs sm:text-md lg:text-lg">Absender</div>
					<div class="col-span-10 text-xs sm:text-md lg:text-lg">{{ $message->author?->name }}</div>
				</div>
			</header>

			{{-- A bare `<p>` in legacy, so it takes the global paragraph margin —
			     12px, 16 from `lg`, the same pair `x-ui.rich-text` gives the
			     body below it. --}}
			@if ($message->subject)
				<p class="mb-12 lg:mb-16">{{ $message->subject }}</p>
			@endif

			{{-- The body is editor HTML from either side — the dashboard's, or
			     the expert composer's ([[x-form.editor]], cleaned on the way in
			     by [[MessageHtml]]) — and goes out through [[RichText]]'s
			     allowlist either way. --}}
			<x-ui.rich-text :html="$message->body" />

			@if ($message->media->isNotEmpty())
				{{-- *Anhänge* — grey, going lighter on hover, which is the one
				     place on the site a link hovers to `#969696` rather than to
				     teal. Legacy links straight at `/storage/uploads/{name}`;
				     here it is the policy-gated route, because a message's
				     attachment is as private as the message
				     ([[MediaPolicy]]). --}}
				<footer class="mt-12 border-t border-gray-600 pt-8 sm:mt-24">
					<div class="mb-4 text-xs sm:text-md lg:text-lg">Anhänge</div>
					@foreach ($message->media as $attachment)
						<a href="{{ route('media.download', $attachment->uuid) }}"
							class="block text-gray-600 underline transition-colors hover:text-gray-400 hover:no-underline">{{ $attachment->original_name }}</a>
					@endforeach
				</footer>
			@endif
		</div>
	</div>
</div>
