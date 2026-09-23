@props([
	'name',
	'label' => null,
	'hint' => null,
	'multiple' => true,
	'accept' => null,
	'restrictions' => null,
	'maxSize' => null,
	'maxFiles' => null,
	'rule' => false,
])

{{--
	The upload control: legacy's drag-and-drop box (`shared/modules/files`,
	`vue-dropzone`) drawn over a plain `<input type="file">` ([[09-public-site]]).

	**The box is legacy's; when the file moves is not.** Legacy posts each
	dropped file to `/api/file` immediately and sends a list of uuids with the
	form, so every abandoned draft leaves a row and a file behind — 11 of its 44
	files are attached to nothing at all. Here a drop only fills the input
	(`js/site/components/file-drop.js`), and nothing is uploaded until the form
	is ([[ExpertPortalController::storeMessage]]).

	The list under the box is new. Legacy's appears once a file has *uploaded*,
	with a download link and a description field that belong to a stored file;
	these are not stored yet, so they get a name, a size and a way back out.

	The box, measured on the live expert upload at 1482px: a 2px dashed
	`gray-400` border going teal on hover, 150px tall, 20px of padding, the
	message at 14/16/18px (`vendor/_dropzone-custom.scss`). Under it
	`.requirements` (`form/_validation.scss`): uppercase `gray-400`, 12/14px,
	4px above.

	**The spacing is legacy's at three widths** (500 / 800 / 1200), measured
	on both expert forms 2026-09-23:

	| | 500 | 800 | 1200 |
	|---|---|---|---|
	| label to box | 8 | 16 | 16 |
	| requirements line to the end of the group | 16 | 32 | 32 |

	The first is the box's own margin and not the label's plus it: the
	label's `mb-4` collapses into it. The second is the top margin of legacy's
	empty file listing, which is why it is there with nothing listed.

	`rule` is `.form-group__upload`'s black rule under all of it, which legacy
	draws on the message form (`form/_layout.scss:33`) and not on the upload
	screen — that one has *Speichern*'s `.line-before` instead.

	Without JavaScript the box stays cloaked and the input below is what the
	visitor gets, styled as the other controls are — `file:` is Tailwind's
	variant for the button the browser draws inside it.
--}}
<div @class(['relative pb-16 sm:pb-32', 'mb-16 border-b border-black lg:mb-32' => $rule])
	x-data="fileDrop({ accept: @js((string) $accept), maxSize: @js((int) $maxSize), maxFiles: @js((int) $maxFiles) })">
	@if ($label)
		<label for="{{ $name }}" class="mb-4 block text-md sm:text-lg lg:text-xl">{{ $label }}</label>
	@endif

	<label for="{{ $name }}" x-cloak
		x-on:dragover.prevent="dragging = true"
		x-on:dragleave.prevent="dragging = false"
		x-on:drop.prevent="dropped($event)"
		x-bind:data-dragging="dragging ? '' : null"
		@class([
			'flex min-h-150 cursor-pointer items-center justify-center border-2 border-dashed p-20 text-center text-md transition-colors hover:border-teal has-[:focus-visible]:border-teal data-dragging:border-teal sm:text-lg lg:text-xl',
			'border-danger' => $errors->has($name) || $errors->has($name.'.*'),
			'border-gray-400' => ! ($errors->has($name) || $errors->has($name.'.*')),
			'mt-8 sm:mt-16' => $label,
		])>
		{{-- `pointer-events-none`, so dragging across the words does not fire a
		     `dragleave` on the box and flicker the border. --}}
		<span class="pointer-events-none">Dateien hierher ziehen oder klicken</span>
	</label>

	<input
		id="{{ $name }}"
		name="{{ $multiple ? $name.'[]' : $name }}"
		type="file"
		x-ref="input"
		x-on:change="picked()"
		@if ($multiple) multiple @endif
		@if ($accept) accept="{{ $accept }}" @endif
		@error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
		{{ $attributes->class([
			'block w-full border-b border-black bg-transparent py-8 text-md text-teal outline-hidden file:mr-16 file:border file:border-teal file:bg-white file:px-16 file:py-4 file:text-md file:text-teal hover:file:border-black hover:file:text-black sm:text-lg sm:file:text-lg',
			'border-danger' => $errors->has($name) || $errors->has($name.'.*'),
		]) }}
	>

	@if ($restrictions)
		<span class="mt-4 inline-block text-xs text-gray-400 uppercase lg:text-md">{{ $restrictions }}</span>
	@endif

	<p x-cloak x-show="error" x-text="error" class="pt-8 text-md text-danger lg:text-lg"></p>

	{{-- `.text-xsmall` (`typo/_helpers.scss`) for the names, 12/14/16px, and
	     the 1px black rule legacy puts above each listed file. --}}
	<ul x-cloak x-show="files.length" class="mt-16 sm:mt-24">
		<template x-for="(file, index) in files" x-bind:key="file.name + file.size + file.lastModified">
			<li class="flex items-center justify-between gap-16 border-t border-black py-8 text-xs sm:text-md lg:text-lg">
				<span class="min-w-0 truncate" x-text="file.name"></span>
				<span class="flex shrink-0 items-center gap-16">
					<span class="text-gray-400" x-text="size(file)"></span>
					<button type="button" x-on:click="remove(index)" class="transition-colors hover:text-teal"
						x-bind:aria-label="'Entfernen: ' + file.name">
						<x-icon.cross class="w-12!" />
					</button>
				</span>
			</li>
		</template>
	</ul>

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
