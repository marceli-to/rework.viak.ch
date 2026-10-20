{{--
	Consent first, then Google Tag Manager ([[11-tracking]], step 1).

	Legacy's `web/partials/footer.blade.php`, moved into `<head>` where Google
	asks for it, and with two of chunk 11's findings fixed:

	- **Consent Mode v2**: all four signals default to `denied`. Legacy set
	  only `ad_storage` and `analytics_storage`; since March 2024 Google needs
	  `ad_user_data` and `ad_personalization` too for visitors from Switzerland
	  and the EU (finding 4). The banner grants them per category
	  (`site/components/cookie-consent.js`), including the marketing one
	  legacy never offered (finding 2).
	- **The container id is config** (`GTM_ID`), not written into the page.

	`dataLayer` and `gtag` always exist, so the banner can report a choice on
	a laptop too. The container itself loads **in production only**, as on
	legacy, and only with an id: a prototype never counts as VIAK's traffic.
--}}
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('consent', 'default', {
		ad_storage: 'denied',
		ad_user_data: 'denied',
		ad_personalization: 'denied',
		analytics_storage: 'denied',
		wait_for_update: 500,
	});
</script>
@if (app()->isProduction() && ($gtm = config('services.gtm.id')))
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@js($gtm));</script>
@endif
