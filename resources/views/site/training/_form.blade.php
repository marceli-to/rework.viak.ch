{{--
	Firmenschulung's enquiry ([[TrainingController]]), the fields as Kontakt's
	form has them (`contact/_form`): the mockup's four, Firma and
	Ansprechperson side by side from sm. No card of its own: it stands in the
	intro's column, under *Anfragen* ([[training._intro]]).
--}}
@if (session('training') === 'sent')
	<x-ui.toast variant="success">Danke, deine Anfrage ist bei uns angekommen.</x-ui.toast>
@elseif ($errors->any())
	<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
@endif

<form method="POST" action="{{ \App\Support\SiteUrl::training() }}">
	@csrf

	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<div class="sm:col-span-6">
			<x-form.field name="company" label="Firma" required autocomplete="organization" />
		</div>
		<div class="sm:col-span-6">
			<x-form.field name="name" label="Ansprechperson" required autocomplete="name" />
		</div>
	</div>
	<x-form.field name="email" label="E-Mail" type="email" required autocomplete="email" />
	<x-form.textarea name="message" label="Nachricht" required placeholder="Gewünschte Software, Teamgrösse, Zeitrahmen …" />

	{{-- The honeypot, as on Kontakt. --}}
	<div class="absolute -left-[9999px]" aria-hidden="true">
		<label for="website">Website</label>
		<input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
	</div>

	<x-form.turnstile action="training" />

	<x-ui.button type="submit">Anfrage senden</x-ui.button>
</form>
