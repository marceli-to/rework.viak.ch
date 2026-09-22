@props([
	'name',
	'label' => null,
	'type' => 'text',
	'value' => null,
	'required' => false,
	'hint' => null,
])

{{--
	`form/_layout.scss:7` (`.form-group`), `form/_label.scss`, `form/_global.scss`
	and `form/_validation.scss:38`, measured on the live login page.

	The input is **bold and teal with a single rule under it** — that is the
	global form rule, not something this field decides, and it is also where the
	missing focus ring comes from ([[09-public-site]]).

	**Teal, and it was black here until 2026-09-22.** `form/_global.scss` sets
	the colour twice and the second one wins:

	    button, input[type=text], select, textarea  { color: $color-primary }
	    .select-wrapper, input[type=text], textarea { color: $color-secondary }

	Read from the stylesheet the first rule is the answer; read from the browser
	the second is. Measured on the live `/de/registration`:
	`rgb(70, 186, 186)` on every text input, matching the selects beside them —
	which `x-site.select` had right all along, because `.select-wrapper select`
	is the one place the teal is stated only once. So the labels are black and
	the **values** are teal, which is the whole visual logic of these forms and
	was inverted on every screen: login, registration, password reset, the
	checkout's address dialog and the portal.

	Sizes, all measured: label 14/16/18, input 16/18/24, the group's own margin
	16 below `lg` and 32 above, and the error 14/16 with 8px above it.

	**`leading-[normal]`, not `1.3`.** Legacy's normalize sets
	`input { line-height: normal }` — the old Firefox fix at
	`helpers/_normalize.scss:336` — and says nothing about `select`, which is why
	a select inherits the body's 1.3 and an input does not. Measured: 37.5px on
	the live login field against 39.2 for 1.3.
--}}
<div class="relative mb-16 lg:mb-32">
	@if ($label)
		<label for="{{ $name }}" class="mb-4 block text-md sm:text-lg lg:text-xl">
			{{ $label }}@if ($required) *@endif
		</label>
	@endif

	<input
		id="{{ $name }}"
		name="{{ $name }}"
		type="{{ $type }}"
		value="{{ $type === 'password' ? '' : old($name, $value) }}"
		@if ($required) required @endif
		@error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
		{{ $attributes->class([
			'block w-full border-b border-black bg-transparent py-4 text-lg leading-[normal] font-bold text-teal outline-hidden sm:text-xl lg:text-3xl',
			'border-danger' => $errors->has($name),
		]) }}
	>

	@if ($hint)
		<p class="pt-8 text-md lg:text-lg">{{ $hint }}</p>
	@endif

	@error($name)
		<div id="{{ $name }}-error" class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
	@enderror
</div>
