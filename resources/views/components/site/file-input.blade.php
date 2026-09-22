@props([
	'name',
	'label' => null,
	'hint' => null,
	'multiple' => true,
])

{{--
	The upload control, where legacy has a drag-and-drop uploader
	(`shared/modules/files/Index.vue`, `vue-dropzone`) ([[09-public-site]]).

	**A deliberate departure, and the smaller half of it is the widget.** The
	larger half is when the file moves: legacy posts each dropped file to
	`/api/file` immediately and sends a list of uuids with the form, so every
	abandoned draft leaves a row and a file behind — 11 of its 44 files are
	attached to nothing at all. A `<input type="file">` in the form that needs it
	cannot do that, because nothing is uploaded until the form is
	([[ExpertPortalController::storeMessage]]).

	What is lost is the thumbnail strip and the per-file remove, both of which
	matter for the dashboard's image field and neither of which matters for two
	PDFs on a course. The field that field needs is chunk 04's, and it is Vue.

	Styled as the other controls are: the label above, the rule under, the
	control itself the teal that `form/_global.scss` gives every value. `file:`
	is Tailwind's variant for the button the browser draws inside the input, and
	it is the only way to reach it.
--}}
<div class="relative mb-16 lg:mb-32">
	@if ($label)
		<label for="{{ $name }}" class="mb-4 block text-md sm:text-lg lg:text-xl">{{ $label }}</label>
	@endif

	<input
		id="{{ $name }}"
		name="{{ $multiple ? $name.'[]' : $name }}"
		type="file"
		@if ($multiple) multiple @endif
		@error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
		{{ $attributes->class([
			'block w-full border-b border-black bg-transparent py-8 text-md text-teal outline-hidden file:mr-16 file:border file:border-teal file:bg-white file:px-16 file:py-4 file:text-md file:text-teal hover:file:border-black hover:file:text-black sm:text-lg sm:file:text-lg',
			'border-danger' => $errors->has($name) || $errors->has($name.'.*'),
		]) }}
	>

	@if ($hint)
		<p class="pt-8 text-md lg:text-lg">{{ $hint }}</p>
	@endif

	{{-- Both keys, because a per-file rule fails as `files.0` rather than as
	     `files` and one `@error` would miss it. --}}
	@error($name)
		<div id="{{ $name }}-error" class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
	@enderror
	@error($name.'.*')
		<div class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
	@enderror
</div>
