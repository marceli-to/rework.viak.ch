@props(['name', 'value' => '1', 'checked' => false, 'id' => null])

@php($id = $id ?? $name)

{{--
	`form/_input.scss:8`. 12×12, 14×14 from `sm`, a 1px black square that fills
	**solid teal** when checked — there is no tick. `appearance: none` is what
	makes that possible, and it is also why the box needs its own border.

	The label sits 8px away, 12 from `sm`, with 2px of top padding below `bp-md`
	to sit on the text's baseline.
--}}
<div class="flex items-start">
	<input
		type="checkbox"
		id="{{ $id }}"
		name="{{ $name }}"
		value="{{ $value }}"
		@checked($checked)
		{{ $attributes->class([
			'mt-2 size-12 shrink-0 appearance-none border border-black bg-white outline-hidden checked:border-teal checked:bg-teal sm:size-14 lg:mt-4',
		]) }}
	>
	<label for="{{ $id }}" class="ml-8 cursor-pointer pt-2 text-md leading-[1.3] sm:ml-12 sm:text-lg lg:pt-0 lg:text-xl">
		{{ $slot }}
	</label>
</div>
