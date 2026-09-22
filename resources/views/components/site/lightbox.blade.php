@props(['title' => null, 'show' => 'false', 'close' => ''])

{{--
	`components/_lightbox.scss` — the bigger overlay, the one legacy puts a whole
	form in. `<x-site.modal>` is its sibling: `.notification.is-modal` extends
	the same `%lightbox` and then narrows it to a message with buttons.

	The two differ in exactly three ways, and all three are why this is its own
	component rather than a prop:

	| | modal | lightbox |
	|---|---|---|
	| border | 3px | **2px** |
	| box | 600px flat | **600–900px**, shrink to fit |
	| padding | 24/16, 32/24 from `lg` | **12, 24 from `sm`** |

	The width really is a range here: `max-width: 900px` and `min-width: 600px`
	from `bp-sm` do not collide, so the box grows with its content between the
	two — unlike the modal, where a 480px max-width loses to the same 600px
	min-width and the box is always exactly 600 ([[09-public-site]]).

	`.lightbox-overflow` caps the inner scroller at **90vh** with 4px of side
	padding, so a long form scrolls inside the box rather than pushing it off
	the screen. That is the whole reason legacy has a second overlay.

	Like the modal: an empty `x-data`, because Alpine 3 evaluates a directive
	only inside a component; and the backdrop closes, which is what the
	`%lightbox`'s own `cursor` says it should.
--}}
<div x-data x-cloak x-show="{{ $show }}"
	class="fixed inset-0 z-[200] flex cursor-pointer items-center justify-center overflow-y-auto bg-white/90 leading-[1.3]"
	@if ($close)
		@click.self="{{ $close }}"
		@keydown.escape.window="{{ $close }}"
	@endif
	role="dialog" aria-modal="true">

	<div {{ $attributes->class([
		'max-w-[90%] cursor-default border-2 border-gray-600 bg-white p-12 sm:max-w-900 sm:min-w-600 sm:p-24',
	]) }}>
		<div class="max-h-[90vh] overflow-y-auto px-4">
			@if ($title)
				{{-- `components/headings/_h1.scss`: bold, teal, 12px below it.
				     The close is `.feather-icon` — black, teal on hover — and
				     legacy draws it with feather's `XIcon` at 24 rather than
				     with its own cross, the one place on the site it does.
				     Ours is legacy's own cross at 22, which is the same thin X
				     and keeps the icon set to one source. --}}
				<header class="flex justify-between">
					<h1 class="mb-12 font-bold text-teal">{{ $title }}</h1>

					@if ($close)
						<button type="button" @click="{{ $close }}"
							class="h-fit transition-colors hover:text-teal"
							aria-label="Schliessen">
							<x-icon.cross />
						</button>
					@endif
				</header>
			@endif

			{{ $slot }}
		</div>
	</div>
</div>
