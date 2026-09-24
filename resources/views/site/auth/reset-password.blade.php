<x-layout.site title="Neues Passwort" auth>
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Neues Passwort</h1>
		</x-slot:aside>

		@if ($errors->any())
			<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
		@endif

		<form method="POST" action="{{ route('password.update') }}">
			@csrf
			<input type="hidden" name="token" value="{{ $request->route('token') }}">

			<x-form.field name="email" type="email" label="E-Mail" :value="$request->email" required autocomplete="username" />
			<x-form.field name="password" type="password" label="Neues Passwort" required autocomplete="new-password" />
			<x-form.field name="password_confirmation" type="password" label="Passwort wiederholen" required autocomplete="new-password" />

			<x-ui.button type="submit" class="w-full">Passwort speichern</x-ui.button>
		</form>
	</x-layout.article>
</x-layout.site>
