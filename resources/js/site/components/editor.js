/**
 * The message composer's editor — legacy's TinyMCE, rebuilt on tiptap
 * ([[09-public-site]]).
 *
 * **tiptap is imported when an editor starts, not with the site.** It is the
 * only heavy dependency on the public site and one screen uses it, so Vite
 * splits it into its own chunk and every other page never downloads it.
 *
 * **The `<textarea>` stays the field.** Without JavaScript it is what the
 * visitor types into and the server turns blank lines into paragraphs. With
 * it, the textarea is hidden, receives the editor's HTML on every change, and
 * `body_format` says so — which is how the controller knows to sanitise
 * rather than escape ([[ExpertPortalController::storeMessage]]).
 *
 * **The Editor instance is kept out of Alpine's state.** Alpine wraps what it
 * holds in a Proxy, and ProseMirror compares its own objects by identity; a
 * proxied editor fails in ways that look like tiptap bugs. So it lives in this
 * closure, and only plain booleans are reactive.
 */
export default () => {
	let editor = null;

	return {
		ready: false,
		bold: false,
		bulletList: false,
		link: false,
		linking: false,
		// Nothing selected and not inside a link: the link needs words of its own.
		needsText: false,
		url: '',
		text: '',
		urlError: null,

		async init() {
			const [{ Editor }, { default: extensions }] = await Promise.all([
				import('@tiptap/core'),
				import('../../shared/editor'),
			]);

			const field = this.$refs.field;

			editor = new Editor({
				element: this.$refs.content,
				extensions: extensions(),
				content: field.value,
				editorProps: {
					attributes: {
						class: 'min-h-full outline-hidden',
						'aria-labelledby': field.id + '-label',
						'aria-multiline': 'true',
						role: 'textbox',
					},
				},
				onUpdate: () => this.write(),
				onTransaction: () => this.sync(),
			});

			// A hidden `required` control blocks the submit with a message the
			// browser cannot show, since it cannot focus it. The server checks.
			field.required = false;
			this.$refs.format.value = 'html';
			this.write();
			this.ready = true;
		},

		destroy() {
			editor?.destroy();
		},

		write() {
			this.$refs.field.value = editor.isEmpty ? '' : editor.getHTML();
		},

		sync() {
			this.bold = editor.isActive('bold');
			this.bulletList = editor.isActive('bulletList');
			this.link = editor.isActive('link');
		},

		toggleBold() {
			editor.chain().focus().toggleBold().run();
		},

		toggleBulletList() {
			editor.chain().focus().toggleBulletList().run();
		},

		// The lightbox, as the dashboard's `LinkDialog.vue`: keep the two in step.
		openLink() {
			this.url = this.link ? (editor.getAttributes('link').href ?? '') : '';
			this.text = '';
			this.urlError = null;
			this.needsText = !this.link && editor.state.selection.empty;
			this.linking = true;
		},

		applyLink() {
			const typed = this.url.trim();

			if (typed === '') {
				this.urlError = 'Bitte eine Adresse eingeben.';
				return;
			}

			const href = this.normalise(typed);

			// A link on nothing selected would be invisible; the text typed in
			// the lightbox, else the address, goes in linked.
			if (this.needsText) {
				editor.chain().focus().insertContent({ type: 'text', text: this.text.trim() || typed, marks: [{ type: 'link', attrs: { href } }] }).run();
			} else {
				editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
			}

			this.linking = false;
		},

		removeLink() {
			editor.chain().focus().extendMarkRange('link').unsetLink().run();
			this.linking = false;
		},

		cancelLink() {
			if (!this.linking) return;
			this.linking = false;
			editor.commands.focus();
		},

		// `www.example.ch` and `name@example.ch` are what people type; neither
		// is a URL until it has a scheme.
		normalise(url) {
			if (url === '' || /^(https?:|mailto:)/i.test(url)) return url;
			if (/^[^\s@/]+@[^\s@/]+\.[^\s@/]+$/.test(url)) return 'mailto:' + url;

			return 'https://' + url.replace(/^\/+/, '');
		},
	};
};
