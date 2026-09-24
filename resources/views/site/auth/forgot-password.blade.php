<x-layout.site title="Passwort vergessen" auth>
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Passwort vergessen</h1>
		</x-slot:aside>

		@if (session('status'))
			<x-ui.toast variant="success">{{ session('status') }}</x-ui.toast>
		@elseif ($errors->any())
			<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
		@endif

		<p class="mb-32">
			Geben Sie Ihre E-Mail-Adresse ein. Wir senden Ihnen einen Link, mit dem Sie ein
			neues Passwort setzen können.
		</p>

		<form method="POST" action="{{ route('password.email') }}" class="mb-16 lg:mb-32">
			@csrf

			<x-form.field name="email" type="email" label="E-Mail" required autocomplete="username" />

			<x-ui.button type="submit" class="w-full">Link senden</x-ui.button>
		</form>

		<a href="{{ route('login') }}"
			class="inline-block text-md italic hover:underline hover:underline-offset-1 sm:text-lg lg:text-xl">
			Zurück zum Login
		</a>
	</x-layout.article>
</x-layout.site>
