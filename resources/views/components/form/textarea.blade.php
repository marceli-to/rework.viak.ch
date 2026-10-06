@props([
	'name',
	'label' => null,
	'value' => null,
	'required' => false,
	'rows' => 6,
])

{{--
	The text input's multi-line twin ([[form.field]]): the same label, group
	margin and error, and legacy's `textarea` rules from `form/_global.scss` —
	bold, teal, a black rule under it and 4px above and below, like an input,
	but **one step smaller**: 14/16/18, the size legacy gives `textarea` and
	`select` against an input's 16/18/24. `is-large`'s 175px is the floor, so a
	message has room before it scrolls.
--}}
<div class="relative mb-16 lg:mb-32">
	@if ($label)
		<label for="{{ $name }}" class="mb-4 block text-md sm:text-lg lg:text-xl">
			{{ $label }}@if ($required) *@endif
		</label>
	@endif

	<textarea
		id="{{ $name }}"
		name="{{ $name }}"
		rows="{{ $rows }}"
		@if ($required) required @endif
		@error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
		{{ $attributes->class([
			'block min-h-175 w-full resize-y border-b border-black bg-transparent py-4 text-md font-bold text-teal outline-hidden sm:text-lg lg:text-xl',
			'border-danger' => $errors->has($name),
		]) }}
	>{{ old($name, $value) }}</textarea>

	@error($name)
		<div id="{{ $name }}-error" class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
	@enderror
</div>
