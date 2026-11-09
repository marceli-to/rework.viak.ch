{{-- Roxy, the client's feedback widget while the rework is reviewed on
     staging (2026-11-05). Staging only: never on the live site, and not
     locally, so our own clicks don't land in the client's feedback.
     `app()->environment()`, not the Blade directive: the guard test reads
     that as an env call (`EnvOnlyInConfigTest`). --}}
@if (app()->environment('staging'))
	<script src="https://roxy.kimiyu.ch/widget.js" data-site="fmgj35mxfnp4kcgrczkgpvky" defer></script>
@endif
