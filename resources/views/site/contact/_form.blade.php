{{--
	The Kontakt form ([[ContactController]]), one of the rows Anreise is made
	of: *Nachricht senden* in the aside, the form in the column. Mailed to the
	office, never stored; Turnstile, a honeypot and a rate limit keep bots out.
--}}
<x-card.text>
	<x-slot:aside>
		<h2>Nachricht senden</h2>
	</x-slot:aside>

	@if (session('contact') === 'sent')
		<x-ui.toast variant="success">Danke, deine Nachricht ist bei uns angekommen.</x-ui.toast>
	@elseif ($errors->any())
		<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
	@endif

	<form method="POST" action="{{ \App\Support\SiteUrl::contact() }}">
		@csrf

		<x-form.field name="name" label="Name" required autocomplete="name" :value="auth()->user()?->name" />
		<x-form.field name="email" label="E-Mail" type="email" required autocomplete="email" :value="auth()->user()?->email" />
		<x-form.textarea name="message" label="Nachricht" required />

		{{-- The honeypot ([[ContactController]]): off screen, out of the
		     tab order, and hidden from screen readers, so only a bot
		     fills it in. --}}
		<div class="absolute -left-[9999px]" aria-hidden="true">
			<label for="website">Website</label>
			<input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
		</div>

		<x-form.turnstile action="contact" />

		<x-ui.button type="submit">Nachricht senden</x-ui.button>
	</form>
</x-card.text>
