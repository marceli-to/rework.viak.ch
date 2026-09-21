<x-layout.site title="Login">
	{{--
		`auth/login.blade.php`, rebuilt 1:1: the heading and a "Nicht
		registriert?" link in the aside, the form in the column.

		**The heading is hidden on a phone** (`xs:hide` in legacy) because the
		header's own title row already shows it there — the same arrangement the
		course list uses.
	--}}
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden text-3xl leading-[1.3] text-teal sm:block">Login</h1>

			<div class="mt-20 lg:mt-40">
				<a href="{{ route('register') }}" class="inline-flex flex-col items-start hover:text-teal">
					<span>Nicht registriert?</span>
					<x-icon.arrow-right class="mt-8" />
				</a>
			</div>
		</x-slot:aside>

		{{-- Legacy shows a toast as well as the inline errors, and its wording is
		     deliberately vague — naming which of email or password was wrong
		     tells an attacker which half they have. --}}
		@if ($errors->any())
			<x-site.toast>Es ist ein Fehler aufgetreten.</x-site.toast>
		@endif

		<form method="POST" action="{{ route('login') }}">
			@csrf

			<x-site.field name="email" type="email" label="E-Mail" required autocomplete="username" />
			<x-site.field name="password" type="password" label="Passwort" required autocomplete="current-password" />

			<x-site.button type="submit" class="w-full">Anmelden</x-site.button>
		</form>

		{{-- `.form-helper` — italic, black, no underline. --}}
		@if (Route::has('password.request'))
			<a href="{{ route('password.request') }}"
				class="mt-16 inline-block text-md leading-[1.3] italic hover:text-teal sm:text-lg lg:text-xl">
				Passwort vergessen?
			</a>
		@endif
	</x-site.article>
</x-layout.site>
