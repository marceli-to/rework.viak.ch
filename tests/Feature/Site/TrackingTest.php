<?php

declare(strict_types=1);

/**
 * GTM and consent, step 1 of [[11-tracking]]: Consent Mode v2 on every site
 * page, the container in production only and only when configured.
 */
it('defaults all four consent signals to denied, before anything else loads', function () {
	$this->get('/de')
		->assertOk()
		->assertSeeInOrder(["gtag('consent', 'default'", 'ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage'], false);
});

it('loads no container outside production, even with an id', function () {
	config(['services.gtm.id' => 'GTM-M3L7WVP']);

	$this->get('/de')->assertOk()->assertDontSee('googletagmanager.com', false);
});

it('loads the configured container in production, with its noscript', function () {
	config(['services.gtm.id' => 'GTM-M3L7WVP']);
	app()->detectEnvironment(fn () => 'production');

	$this->get('/de')->assertOk()
		->assertSee('googletagmanager.com/gtm.js', false)
		->assertSee("'GTM-M3L7WVP'", false)
		->assertSee('ns.html?id=GTM-M3L7WVP', false);

	app()->detectEnvironment(fn () => 'testing');
});

it('loads nothing in production without an id', function () {
	config(['services.gtm.id' => '']);
	app()->detectEnvironment(fn () => 'production');

	$this->get('/de')->assertOk()->assertDontSee('googletagmanager.com', false);

	app()->detectEnvironment(fn () => 'testing');
});

it('offers the cookie settings again in the privacy policy', function () {
	$this->get('/de/kontakt')->assertOk()->assertSee('Cookie-Einstellungen ändern');
});
