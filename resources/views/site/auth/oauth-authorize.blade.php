<x-layout.site title="Mit Claude verbinden" auth>
	{{--
		OAuth consent for the MCP server's claude.ai connector ([[17-mcp]]),
		Passport's authorization view. Not a legacy screen: it takes the login
		page's arrangement (heading in the aside, the form in the column).

		Anyone with an account can sign in through the connector; **only an
		active admin may connect** ([[McpAccess]]). Anyone else is told so and
		can only cancel, and `/mcp` would refuse their token anyway
		([[AdmitOAuthToMcp]]).
	--}}
	@php($allowed = \App\Support\Mcp\McpAccess::isAdmitted($user))

	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">{{ $allowed ? 'Verbinden' : 'Nicht verfügbar' }}</h1>
		</x-slot:aside>

		@if ($allowed)
			<p class="mb-32">
				{{ $client->name }} möchte mit deinem Konto ({{ $user->email }}) auf die Daten der
				Visualisierungs-Akademie zugreifen. Es kann Veranstaltungen, Buchungen, offene
				Rechnungen und Lizenzbestellungen lesen, aber nichts ändern.
			</p>

			<form method="POST" action="{{ route('passport.authorizations.approve') }}" class="mb-16 lg:mb-32">
				@csrf
				<input type="hidden" name="state" value="{{ $request->state }}">
				<input type="hidden" name="client_id" value="{{ $client->getKey() }}">
				<input type="hidden" name="auth_token" value="{{ $authToken }}">
				<x-ui.button type="submit" class="w-full">Zugriff erlauben</x-ui.button>
			</form>
		@else
			<p class="mb-32">
				KI-Programme können nur vom Team der Visualisierungs-Akademie verbunden werden.
			</p>
		@endif

		<form method="POST" action="{{ route('passport.authorizations.deny') }}">
			@csrf
			@method('DELETE')
			<input type="hidden" name="state" value="{{ $request->state }}">
			<input type="hidden" name="client_id" value="{{ $client->getKey() }}">
			<input type="hidden" name="auth_token" value="{{ $authToken }}">
			<button type="submit" class="inline-block cursor-pointer text-md italic hover:underline hover:underline-offset-1 sm:text-lg lg:text-xl">
				Abbrechen
			</button>
		</form>
	</x-layout.article>
</x-layout.site>
