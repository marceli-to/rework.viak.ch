<x-layout.site title="Neues Passwort" auth>
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden leading-[1.3] font-bold text-teal sm:block">Neues Passwort</h1>
		</x-slot:aside>

		@if ($errors->any())
			<x-site.toast>Es ist ein Fehler aufgetreten.</x-site.toast>
		@endif

		<form method="POST" action="{{ route('password.update') }}">
			@csrf
			<input type="hidden" name="token" value="{{ $request->route('token') }}">

			<x-site.field name="email" type="email" label="E-Mail" :value="$request->email" required autocomplete="username" />
			<x-site.field name="password" type="password" label="Neues Passwort" required autocomplete="new-password" />
			<x-site.field name="password_confirmation" type="password" label="Passwort wiederholen" required autocomplete="new-password" />

			<x-site.button type="submit" class="w-full">Passwort speichern</x-site.button>
		</form>
	</x-site.article>
</x-layout.site>
