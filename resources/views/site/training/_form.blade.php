{{--
	Firmenschulung's enquiry ([[TrainingController]]), drawn as Kontakt's form
	is (`contact/_form`): *Anfrage senden* in the aside, the mockup's four
	fields in the column, Firma and Ansprechperson side by side from sm.
--}}
<x-card.text>
	<x-slot:aside>
		<h2>Anfrage senden</h2>
	</x-slot:aside>

	@if (session('training') === 'sent')
		<x-ui.toast variant="success">Danke, deine Anfrage ist bei uns angekommen.</x-ui.toast>
	@elseif ($errors->any())
		<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
	@endif

	<form method="POST" action="{{ \App\Support\SiteUrl::training() }}">
		@csrf

		<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<div class="sm:col-span-6">
				<x-form.field name="company" label="Firma" required autocomplete="organization" :value="auth()->user()?->company" />
			</div>
			<div class="sm:col-span-6">
				<x-form.field name="name" label="Ansprechperson" required autocomplete="name" :value="auth()->user()?->name" />
			</div>
		</div>
		<x-form.field name="email" label="E-Mail" type="email" required autocomplete="email" :value="auth()->user()?->email" />
		<x-form.textarea name="message" label="Nachricht" required placeholder="Gewünschte Software, Teamgrösse, Zeitrahmen …" />

		{{-- The honeypot, as on Kontakt. --}}
		<div class="absolute -left-[9999px]" aria-hidden="true">
			<label for="website">Website</label>
			<input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
		</div>

		<x-form.turnstile action="training" />

		<x-ui.button type="submit">Anfrage senden</x-ui.button>
	</form>
</x-card.text>
