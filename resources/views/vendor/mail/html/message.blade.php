{{-- Legacy's own, ported as it is: ../viak.ch/resources/views/vendor/mail/html/message.blade.php ([[10-mail]]). --}}
@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
<div class="copy">© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')</div>
@endcomponent
@endslot
@endcomponent
