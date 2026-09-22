@props([
	'name',
	'label' => null,
	'value' => null,
	'required' => false,
	'hint' => null,
	'rows' => 10,
])

{{--
	`x-site.field` with a `<textarea>` in it ([[09-public-site]]).

	Same `.form-group`, same label, same rule under the control, same error — the
	only difference is the tag, and legacy's `form/_global.scss` styles `textarea`
	beside `input[type=text]` in every one of its rules.

	**Except the line height.** Legacy's normalize sets `input { line-height:
	normal }` and says nothing about `textarea`, so a textarea inherits the body's
	1.3 where an input does not — which is why this has no `leading-[normal]` and
	`x-site.field` does ([[x-site.field]]).

	`resize-y`: a textarea is the one control on the site somebody may genuinely
	need bigger, and Tailwind's preflight does not take the grabber away.
	Horizontal resizing would break the column, so only one axis.
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
			'block w-full resize-y border-b border-black bg-transparent py-4 text-lg font-bold text-teal outline-hidden sm:text-xl lg:text-3xl',
			'border-danger' => $errors->has($name),
		]) }}
	>{{ old($name, $value) }}</textarea>

	@if ($hint)
		<p class="pt-8 text-md lg:text-lg">{{ $hint }}</p>
	@endif

	@error($name)
		<div id="{{ $name }}-error" class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
	@enderror
</div>
