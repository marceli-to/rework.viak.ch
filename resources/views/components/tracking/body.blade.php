{{-- GTM's `<noscript>` fallback, first in `<body>` as Google asks; production only, as `tracking.head`. --}}
@if (app()->isProduction() && ($gtm = config('services.gtm.id')))
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
