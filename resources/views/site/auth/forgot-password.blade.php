<x-layout.site title="Passwort vergessen">
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden text-3xl leading-[1.3] text-teal sm:block">Passwort vergessen</h1>
		</x-slot:aside>

		@if (session('status'))
			<x-site.toast variant="success">{{ session('status') }}</x-site.toast>
		@elseif ($errors->any())
			<x-site.toast>Es ist ein Fehler aufgetreten.</x-site.toast>
		@endif

		<p class="mb-32">
			Geben Sie Ihre E-Mail-Adresse ein. Wir senden Ihnen einen Link, mit dem Sie ein
			neues Passwort setzen können.
		</p>

		<form method="POST" action="{{ route('password.email') }}">
			@csrf

			<x-site.field name="email" type="email" label="E-Mail" required autocomplete="username" />

			<x-site.button type="submit" class="w-full">Link senden</x-site.button>
		</form>

		<a href="{{ route('login') }}"
			class="mt-16 inline-block text-md leading-[1.3] italic hover:text-teal sm:text-lg lg:text-xl">
			Zurück zum Login
		</a>
	</x-site.article>
</x-layout.site>
