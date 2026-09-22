<x-layout.site title="Login" auth>
	{{--
		`auth/login.blade.php`, rebuilt 1:1: the heading and a "Nicht
		registriert?" link in the aside, the form in the column.

		**The heading is hidden on a phone** (`xs:hide` in legacy) because the
		header's own title row already shows it there — the same arrangement the
		course list uses.

		It carries **no `text-*` class**: legacy's `h1` sets a weight and a
		colour and nothing else (`components/headings/_h1.scss`), so the size
		comes down from the article, and from the body above it — 18px at `sm`,
		24px at `lg`. Spelling `text-3xl` here made it 24 at every width and put
		the heading a size above its own body copy on a tablet.
	--}}
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Login</h1>

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

		{{-- `mb-16 lg:mb-32` is the submit button's `.form-group` bottom margin.
		     Legacy wraps every control, the button included, so that margin collapses
		     through the form and lands exactly here. Carrying it as the helper's
		     `margin-top` instead loses 6px: a top margin on an inline-block swallows
		     the line box's half-leading, a block margin below the form does not. --}}
		<form method="POST" action="{{ route('login') }}" class="mb-16 lg:mb-32">
			@csrf

			<x-site.field name="email" type="email" label="E-Mail" required autocomplete="username" />
			<x-site.field name="password" type="password" label="Passwort" required autocomplete="current-password" />

			<x-site.button type="submit" class="w-full">Anmelden</x-site.button>
		</form>

		{{-- `.form-helper` (`form/_layout.scss:150`) — italic, black, 14/16/18px,
		     and it **underlines on hover rather than turning teal**, at a 1px
		     offset. The teal hover belongs to `.icon-arrow-right:below` above.

		     **No top margin of its own** — legacy's has none either; the gap is
		     the form's, above. --}}
		@if (Route::has('password.request'))
			<a href="{{ route('password.request') }}"
				class="inline-block text-md italic hover:underline hover:underline-offset-1 sm:text-lg lg:text-xl">
				Passwort vergessen?
			</a>
		@endif
	</x-site.article>
</x-layout.site>
