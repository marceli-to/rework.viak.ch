@props(['file', 'action' => null])

@php
	/*
	 * `Vue.filter('fileSize')` — **base 1000, not 1024**, two decimals, and the
	 * trailing zeros dropped by `parseFloat`. So 1,536,000 bytes reads *1.54 MB*
	 * rather than *1.46 MiB*, which is the convention a file manager uses and
	 * the one the number beside it should agree with.
	 *
	 * The student's booked-event screen divided by 1024 twice and rounded to one
	 * decimal, which is neither ([[09-public-site]]).
	 */
	$size = function (?int $bytes): string {
		if (! $bytes) {
			return '0 Bytes';
		}

		$units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		$step = min((int) floor(log($bytes, 1000)), count($units) - 1);

		return rtrim(rtrim(number_format($bytes / 1000 ** $step, 2, '.', ''), '0'), '.').' '.$units[$step];
	};
@endphp

{{--
	One course document — `shared/modules/files/components/ListItem.vue`
	([[08-accounts]], [[09-public-site]]).

	Four columns on `%stacked-list`: the name, when it was uploaded, how big it
	is, and the buttons. Both portals list an event's materials through this same
	module, so there is one component here as there is one there — the expert's
	screen passes a *Löschen* into the `action` slot and the student's passes
	nothing.

	**It replaces a two-column rendering that was not legacy's.** The student's
	screen printed the name, the size in parentheses and a *Download* when it was
	built (2026-09-22); `ListItem.vue` is what production draws on both. Found
	while building the expert screen, which needed the same list.

	Legacy truncates the name at 35 characters and links it at
	`/storage/uploads/{name}` — a direct path on the public disk, which is
	finding 3. Here the link is the policy-gated route ([[MediaController]]).
--}}
<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		{{-- `caption (original_name)` where there is a caption, which is
		     legacy's `description` column under its own name ([[Media]]). --}}
		<div class="sm:col-span-4">
			{{ \Illuminate\Support\Str::limit(
				$file->caption ? $file->caption.' ('.$file->original_name.')' : (string) $file->original_name,
				35,
			) }}
		</div>

		{{-- `d.m.Y, H:i` — legacy's `getUploadedAtAttribute()`, and the only
		     place on the public site a time appears beside a date. --}}
		<div class="sm:col-span-3">{{ $file->created_at?->format('d.m.Y, H:i') }}</div>

		<div class="sm:col-span-2">{{ $size($file->size) }}</div>

		{{-- `.flex.justify-end` around a column of buttons, 8px apart — legacy's
		     `mb-2x` on the download. `.xs\:mt-6x` is not on this row in legacy;
		     the buttons are full width on a phone and follow the size line
		     directly. --}}
		<div class="mt-24 sm:col-span-3 sm:mt-0 sm:flex sm:justify-end">
			<div>
				<x-site.button href="{{ route('media.download', $file->uuid) }}"
					class="mb-8" title="{{ $file->original_name }}">Download</x-site.button>

				{{ $action }}
			</div>
		</div>
	</div>
</article>
