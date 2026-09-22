<x-layout.site title="Profil" heading="Mein Profil" auth>
	{{--
		*Mein Profil*, an expert's — `backend/expert/views/Index.vue`,
		server-rendered ([[08-accounts]], [[09-public-site]]).

		The student's landing screen with two lists instead of four, and the two
		are the courses this person **teaches** rather than the ones they bought
		([[ExpertPortalController::index]]). Everything above them — the article,
		the pencil, the address block, the *Logout* — is the same markup, because
		legacy's two `Index.vue` files are the same file with different fetches.

		**Teal-gutted**, as every screen behind the login is: legacy's
		`web/pages/user/expert/index.blade.php` sets `is-auth` on `<html>`.

		No JavaScript of its own. The pencil is a link and the two collapsibles
		are the only Alpine on the page.
	--}}
	<x-site.article>
		{{-- `.icon-edit`, `position: absolute; top: 0; right: 0` at 18×18, over
		     the aside rather than over the column — the student's profile has
		     the same one in the same place. --}}
		<a href="{{ \App\Support\SiteUrl::expertProfileEdit() }}"
			class="absolute top-0 right-0 block transition-colors hover:text-teal"
			title="Profil bearbeiten">
			<x-icon.edit class="w-18" />
		</a>

		<x-slot:aside>
			{{-- Hidden below `sm` — legacy's `xs:hide` — because the header
			     already carries the page title on a phone. --}}
			<h1 class="hidden font-bold text-teal sm:block">Mein Profil</h1>

			<x-site.back-link :href="route('logout')" label="Logout" direction="right" method="post" />
		</x-slot:aside>

		@if (session('status'))
			<x-site.toast variant="success">{{ session('status') }}</x-site.toast>
		@endif

		<div>
			@if ($user->company){{ $user->company }}<br>@endif
			{{ $user->name }}<br>
			{{ $user->street }} {{ $user->street_no }}<br>
			{{ $user->zip }} {{ $user->city }}
			@if ($user->country && $user->country_code !== 'ch')<br>{{ $user->country->name }}@endif
		</div>
		<div><a href="mailto:{{ $user->email }}" class="hover:text-teal">{{ $user->email }}</a></div>
	</x-site.article>

	{{-- 48px above, 64 from `lg` — `.collapsible-container`, as on the student's
	     profile. --}}
	<div class="mt-48 lg:mt-64">
		{{--
			**No fee and no expert on these rows**, which is legacy's
			`:showFee="false" :showExperts="false"` and is the difference that
			matters: what a course costs is the student's question, and *mit
			Anna Muster* on the list of courses Anna Muster teaches is noise.

			What takes their place is `showBookings` — *12 / 14 Teilnehmer*, the
			one number an expert opens this page for.
		--}}
		<x-site.collapsible title="Bevorstehende Kurse" :expanded="true" :count="$upcoming->count()">
			@forelse ($upcoming as $event)
				<x-site.event-row :event="$event" :showExperts="false" :showFee="false"
					:bookings="$event->bookings_count">
					<x-slot:action>
						<x-site.button href="{{ \App\Support\SiteUrl::expertEvent($event->uuid) }}"
							title="Detail">Detail</x-site.button>
					</x-slot:action>
				</x-site.event-row>
			@empty
				<p class="mt-16 italic">Du hast keine bevorstehenden Kurse.</p>
			@endforelse
		</x-site.collapsible>

		{{--
			*Vergangene Kurse*, shut — and it keeps its *Detail* button, unlike
			the student's *Absolvierte Kurse*, which loses everything but the
			link. An expert still wants the participant list of a course that has
			run: it is who was in the room.
		--}}
		<x-site.collapsible title="Vergangene Kurse" :expanded="false" :count="$past->count()">
			@forelse ($past as $event)
				<x-site.event-row :event="$event" :showExperts="false" :showFee="false"
					:bookings="$event->bookings_count">
					<x-slot:action>
						<x-site.button href="{{ \App\Support\SiteUrl::expertEvent($event->uuid) }}"
							title="Detail">Detail</x-site.button>
					</x-slot:action>
				</x-site.event-row>
			@empty
				<p class="mt-16 italic">Du hast keine abgeschlossenen Kurse.</p>
			@endforelse
		</x-site.collapsible>
	</div>
</x-layout.site>
