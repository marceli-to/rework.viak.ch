{{--
	*Danke für deine Nachricht*, to the sender of the Kontakt form
	([[ContactMessageConfirmation]]). New: legacy's Kontakt had no form. It
	promises an answer and no time frame; the mockup's *innerhalb eines
	Werktages* is VIAK's to promise.
--}}
@component('mail::message')
<p>Hallo {{ $name }}</p>
<p>Danke für deine Nachricht. Sie ist bei uns angekommen, und wir melden uns bei dir.</p>
<p>Du hast uns geschrieben:</p>
<p>{!! nl2br(e($text)) !!}</p>
@include('mail.partials.signature')
@endcomponent
