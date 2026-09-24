@props([
	'name',
	'label' => null,
	'value' => null,
	'required' => false,
])

{{--
	The message composer's editor: legacy's TinyMCE 5.10.9 box, drawn over a
	`<textarea>` and driven by tiptap (`js/site/components/editor.js`)
	([[09-public-site]]).

	**Three buttons where legacy has eight.** None of legacy's 255 messages uses
	any formatting at all — the port copies bodies verbatim and every tag in
	them is a `<p>` — so the toolbar keeps bold, a bullet list and a link
	(Marcel, 2026-09-23). The schema matches, so ⌘I or a heading pasted from
	Word cannot bring back what the toolbar left out
	(`js/shared/editor.js`).

	**TinyMCE's proportions, the site's clothes** (Marcel, 2026-09-23). The
	box keeps what was measured on legacy at 1482px — 320px tall, a 39px
	toolbar, 34px buttons with 24px icons, groups `0 4px` — but is drawn the
	way every other control here is: 1px black lines instead of the skin's
	`#ccc`, Effra instead of the system stack, and teal for a hovered or active
	button where the skin greys the background. The first cut matched
	TinyMCE's colours exactly and looked like a different site's widget.

	**The text inside is what the student will see**: 14/16/18px, the size the
	thread's collapsible gives a message ([[x-row.message]]), and
	`x-ui.rich-text`'s own rules for paragraphs, lists, bold and links. The
	icons are TinyMCE 6.8's, which is MIT — 5.x, the version legacy runs, is
	LGPL.

	Legacy's link button opens a modal with four fields. This asks for the one
	that matters, in a bar under the toolbar.

	Without JavaScript the box stays cloaked and the textarea is the field,
	styled as the site's inputs are but with the body's 1.3 line height — legacy's
	normalize sets `input { line-height: normal }` and leaves `textarea` alone.
	The server tells the two apart by `{name}_format`.
--}}
<div class="relative mb-16 lg:mb-32" x-data="editor">
	@if ($label)
		<label id="{{ $name }}-label" for="{{ $name }}" class="mb-4 block text-md sm:text-lg lg:text-xl"
			x-on:click="$refs.content.querySelector('[contenteditable]')?.focus()">
			{{ $label }}@if ($required) *@endif
		</label>
	@endif

	<input type="hidden" name="{{ $name }}_format" value="text" x-ref="format">

	<textarea
		id="{{ $name }}"
		name="{{ $name }}"
		rows="6"
		x-ref="field"
		x-show="!ready"
		@if ($required) required @endif
		@error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
		@class([
			'block w-full resize-y border-b border-black bg-transparent py-4 text-lg font-bold text-teal outline-hidden sm:text-xl lg:text-3xl',
			'border-danger' => $errors->has($name),
		])
	>{{ old($name, $value) }}</textarea>

	<div x-cloak x-show="ready" @class([
		'relative flex h-320 flex-col border bg-white',
		'border-danger' => $errors->has($name),
		'border-black' => ! $errors->has($name),
	])>
		<div role="toolbar" aria-label="Formatierung" aria-controls="{{ $name }}"
			class="flex h-39 shrink-0 border-b border-black bg-white">
			@foreach ([
				['bold', 'toggleBold()', 'Fett', 'editor-bold'],
				['bulletList', 'toggleBulletList()', 'Aufzählung', 'editor-bullet-list'],
				['link', 'openLink()', 'Link einfügen/bearbeiten', 'editor-link'],
			] as [$state, $action, $title, $icon])
				<div @class(['flex items-start px-4', 'border-r border-black' => ! $loop->last])>
					{{-- `mousedown.prevent`: the click must not take focus
					     from the text, or the selection it acts on is gone. --}}
					<button type="button" title="{{ $title }}" aria-label="{{ $title }}"
						x-on:mousedown.prevent
						x-on:click="{{ $action }}"
						x-bind:aria-pressed="{{ $state }}"
						x-bind:data-active="{{ $state }} ? '' : null"
						class="mt-2 mb-3 flex size-34 items-center justify-center text-black transition-colors outline-hidden hover:text-teal focus-visible:text-teal data-active:text-teal">
						<x-dynamic-component :component="'icon.'.$icon" />
					</button>
				</div>
			@endforeach
		</div>

		{{-- The link bar: over the text, under the toolbar, so the box keeps
		     its 320px. Enter applies, Escape leaves. --}}
		<div x-show="linking" x-on:keydown.escape.prevent.stop="cancelLink()"
			class="absolute inset-x-0 top-39 z-10 flex flex-wrap items-center gap-x-16 gap-y-8 border-b border-black bg-white px-16 py-8 text-md sm:text-lg">
			<label for="{{ $name }}-url" class="sr-only">Adresse</label>
			<input id="{{ $name }}-url" type="text" x-ref="url" x-model="url" placeholder="www.beispiel.ch oder name@beispiel.ch"
				x-on:keydown.enter.prevent="applyLink()"
				class="min-w-0 flex-1 border-b border-black bg-transparent py-4 text-teal outline-hidden placeholder:text-gray-400">
			<button type="button" x-on:click="applyLink()" class="transition-colors hover:text-teal">Übernehmen</button>
			<button type="button" x-show="link" x-on:click="removeLink()" class="transition-colors hover:text-teal">Entfernen</button>
			<button type="button" x-on:click="cancelLink()" class="transition-colors hover:text-teal">Abbrechen</button>
		</div>

		{{-- ProseMirror's own element goes in here, styled with
		     `x-ui.rich-text`'s rules so the composer shows the message as
		     the thread will. `[&_li>p]:mb-0` is in both: tiptap wraps a list
		     item's text in a paragraph, and the paragraph margin would space
		     the list out. --}}
		<div x-ref="content" x-on:click.self="$el.querySelector('[contenteditable]')?.focus()"
			class="min-h-0 flex-1 cursor-text overflow-y-auto p-16 text-md text-black sm:text-lg lg:text-xl [&_.ProseMirror]:min-h-full [&_p]:mb-12 lg:[&_p]:mb-16 [&_p:last-child]:mb-0 [&_li>p]:mb-0 [&_strong]:font-bold [&_ul]:mb-12 [&_ul]:list-disc lg:[&_ul]:mb-16 [&_ul:last-child]:mb-0 [&_li]:ml-20 [&_a]:underline [&_a]:decoration-1 [&_a]:underline-offset-[3px]"></div>
	</div>

	@error($name)
		<div id="{{ $name }}-error" class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
	@enderror
</div>
