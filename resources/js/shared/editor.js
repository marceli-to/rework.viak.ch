import StarterKit from '@tiptap/starter-kit';

/**
 * The tiptap schema, shared by both halves of the app ([[09-public-site]]).
 *
 * The public site's message composer uses it through Alpine
 * (`site/components/editor.js`); the dashboard's `richtext` field will use it
 * through `@tiptap/vue-3` in chunk 04 ([[04-content]]). One schema means both
 * produce the same HTML, and [[MessageHtml]] on the server only has to allow
 * one set of tags.
 *
 * **Only what the toolbar offers is in the schema.** A mark the toolbar cannot
 * set can still arrive by keyboard shortcut or by paste — ⌘I, a heading
 * pasted from Word — and would then be stored, rendered and mailed. Left out
 * of the schema, tiptap drops it on the way in instead.
 *
 * `headings` is for the dashboard; the composer has none (Marcel, 2026-09-23:
 * none of legacy's 255 messages uses any formatting at all, so the composer
 * keeps bold, a bullet list and a link).
 */
export default function extensions({ headings = false } = {}) {
	return [
		StarterKit.configure({
			heading: headings ? { levels: [2, 3] } : false,
			blockquote: false,
			code: false,
			codeBlock: false,
			horizontalRule: false,
			italic: false,
			orderedList: false,
			strike: false,
			underline: false,
			link: {
				openOnClick: false,
				autolink: true,
				defaultProtocol: 'https',
				protocols: ['http', 'https', 'mailto'],
				// No `target`/`rel`: the sanitiser keeps `href` and nothing
				// else, so anything added here would be stripped on save.
				HTMLAttributes: { target: null, rel: null },
			},
		}),
	];
}
