{{--
	*Passwort festlegen*: where *Dein VIAK-Zugang* leads ([[InviteController]]).
	Drawn as the reset-password page is, and posts back to its own signed URL.
--}}
<x-layout.site title="Passwort festlegen" auth>
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Passwort festlegen</h1>
		</x-slot:aside>

		@if ($errors->any())
			<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
		@endif

		<form method="POST" action="{{ request()->fullUrl() }}">
			@csrf
			<x-form.field name="password" type="password" label="Passwort" required autocomplete="new-password" />
			<x-form.field name="password_confirmation" type="password" label="Passwort wiederholen" required autocomplete="new-password" />

			<x-ui.button type="submit" class="w-full">Passwort speichern</x-ui.button>
		</form>
	</x-layout.article>
</x-layout.site>
