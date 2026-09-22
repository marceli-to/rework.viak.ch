@props(['name', 'value' => '1', 'checked' => false, 'id' => null])

@php($id = $id ?? $name)

{{--
	`form/_input.scss:8`. 12×12, 14×14 from `sm`, a 1px black square that fills
	**solid teal** when checked — there is no tick. `appearance: none` is what
	makes that possible, and it is also why the box needs its own border.

	The label sits 8px away, 12 from `sm`.

	**The box centres on the label's first line** (Marcel, 2026-09-22, who could
	see it sitting high). Legacy gets that from `vertical-align: middle` on an
	inline input — it has no flex here, so the box simply rides the middle of
	the line it is on, and the 2px of label padding below `bp-md` is a nudge on
	top of that.

	`items-start` has no such notion: it pins the box to the top of a line box
	taller than itself. So the offset is stated, half the difference between the
	line and the box at each step:

	| | line | box | offset |
	|---|---|---|---|
	| base | 14px × 1.3 = 18.2 | 12 | 3 |
	| `sm` | 16px × 1.3 = 20.8 | 14 | 3 |
	| `lg` | 18px × 1.3 = 23.4 | 14 | 5 |

	`items-center` would have been shorter and wrong: it centres against the
	**whole** label, so a two-line one — which the registration form has — would
	drag the box to the middle of both.
--}}
<div class="flex items-start">
	<input
		type="checkbox"
		id="{{ $id }}"
		name="{{ $name }}"
		value="{{ $value }}"
		@checked($checked)
		{{ $attributes->class([
			'mt-3 size-12 shrink-0 appearance-none border border-black bg-white outline-hidden checked:border-teal checked:bg-teal sm:mt-3 sm:size-14 lg:mt-5',
		]) }}
	>
	<label for="{{ $id }}" class="ml-8 cursor-pointer text-md leading-[1.3] sm:ml-12 sm:text-lg lg:text-xl">
		{{ $slot }}
	</label>
</div>
