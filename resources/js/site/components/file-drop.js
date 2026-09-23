/**
 * The drop box around a file input — legacy's `shared/modules/files`
 * (`vue2-dropzone`) on the expert portal's two upload forms ([[09-public-site]]).
 *
 * **The box is a skin; the `<input type="file">` is still the field.** Legacy
 * posts each dropped file to `/api/file` the moment it lands, which is how 11
 * of its 44 files came to be attached to nothing. Here a dropped file is written
 * into the input's own `files` through a `DataTransfer`, so nothing leaves the
 * browser until the form is submitted and the controller sees exactly the
 * request it saw before.
 *
 * Choosing through the dialog *adds* to the list rather than replacing it, as
 * the dropzone did — a native input forgets the first pick when you make a
 * second. Setting `input.files` fires no `change`, so writing the list back
 * cannot loop.
 *
 * `accept` is enforced here as well as on the input, because the attribute only
 * filters the dialog: a drop ignores it.
 */
export default ({ accept = '', maxSize = 0, maxFiles = 0 }) => ({
	files: [],
	dragging: false,
	error: '',

	init() {
		// Without JavaScript the plain, styled input is what the visitor gets;
		// the box is `x-cloak`ed. Once Alpine runs the input steps aside and
		// the box — a `<label>` for it — takes its clicks and keyboard focus.
		this.$refs.input.className = 'sr-only';
	},

	picked() {
		this.add(this.$refs.input.files);
	},

	dropped(event) {
		this.dragging = false;
		this.add(event.dataTransfer.files);
	},

	add(list) {
		this.error = '';

		for (const file of list) {
			if (!this.allowed(file)) {
				this.error = this.rejection();
				continue;
			}

			if (this.files.some((f) => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified)) {
				continue;
			}

			if (maxFiles && this.files.length >= maxFiles) {
				this.error = `Höchstens ${maxFiles} Dateien.`;
				break;
			}

			this.files.push(file);
		}

		this.write();
	},

	remove(index) {
		this.files.splice(index, 1);
		this.error = '';
		this.write();
	},

	write() {
		const transfer = new DataTransfer();
		this.files.forEach((file) => transfer.items.add(file));
		this.$refs.input.files = transfer.files;
	},

	allowed(file) {
		if (maxSize && file.size > maxSize * 1024 * 1024) return false;
		if (!accept) return true;

		const extension = '.' + file.name.split('.').pop().toLowerCase();

		return accept.split(',').map((a) => a.trim().toLowerCase()).includes(extension);
	},

	// Legacy's sentence, `files/components/Upload.vue` → `uploadError`.
	rejection() {
		const formats = accept.replace(/\./g, '').replace(/,/g, ', ').toUpperCase();

		return `Ungültiges Format oder Datei zu gross. Erlaubt sind: ${formats} | max. ${maxSize} MB`;
	},

	size(file) {
		const mb = file.size / 1024 / 1024;

		return mb >= 1 ? `${mb.toFixed(1)} MB` : `${Math.max(1, Math.round(file.size / 1024))} KB`;
	},
});
