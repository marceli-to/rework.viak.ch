<x-layout.site title="E-Mail bestätigen" auth>
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden leading-[1.3] font-bold text-teal sm:block">E-Mail bestätigen</h1>
		</x-slot:aside>

		@if (session('status') === 'verification-link-sent')
			<x-site.toast variant="success">Wir haben Ihnen einen neuen Bestätigungslink gesendet.</x-site.toast>
		@endif

		<p class="mb-32">
			Wir haben Ihnen einen Link an {{ auth()->user()?->email }} gesendet. Bitte bestätigen
			Sie damit Ihre E-Mail-Adresse, bevor Sie fortfahren.
		</p>

		<form method="POST" action="{{ route('verification.send') }}">
			@csrf
			<x-site.button type="submit" class="w-full">Link erneut senden</x-site.button>
		</form>
	</x-site.article>
</x-layout.site>
