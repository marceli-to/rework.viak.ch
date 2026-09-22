@props([
	'name',
	'label' => null,
	'options' => [],
	'placeholder' => null,
	'value' => null,
	'required' => false,
])

{{--
	`form/_select.scss` and `form/_global.scss:81`, measured on the live
	registration form.

	**A form select is teal where the filter's is black** — the global rule paints
	`.select-wrapper select` with `$color-secondary`, and the filter overrides it
	back. Both are bold, because every form control is.

	The wrapper carries the rule underneath (the filter's does not: there the
	item's own border does that job) and the chevron, which is
	`.select-chevron` in `app.css`.

	14/16/18, against the input's 16/18/24 — legacy sizes selects and textareas a
	step below text inputs.
--}}
<div class="relative mb-16 lg:mb-32">
	@if ($label)
		<label for="{{ $name }}" class="mb-4 block text-md sm:text-lg lg:text-xl">
			{{ $label }}@if ($required) *@endif
		</label>
	@endif

	<div @class([
		'select-chevron relative flex w-full items-center border-b py-8',
		'border-black' => ! $errors->has($name),
		'border-danger' => $errors->has($name),
	])>
		<select
			id="{{ $name }}"
			name="{{ $name }}"
			@if ($required) required @endif
			class="block w-full cursor-pointer appearance-none bg-transparent pr-16 text-md font-bold text-teal outline-hidden sm:text-lg lg:text-xl"
		>
			@if ($placeholder)
				<option value="">{{ $placeholder }}</option>
			@endif
			@foreach ($options as $optionValue => $optionLabel)
				<option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
			@endforeach
		</select>
	</div>

	@error($name)
		<div class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
	@enderror
</div>
