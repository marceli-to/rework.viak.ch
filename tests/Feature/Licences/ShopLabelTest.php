<?php

declare(strict_types=1);

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;

/**
 * The shop dropdown's label for a variant ([[LicenceVariant::shopLabel]],
 * [[05-licences]]): Lizenztyp and Nutzung in German, after what is left of
 * the client's name once the vendor's words for them are gone.
 */
$label = fn (string $title, ?LicenceType $type, ?LicenceAccess $access) => (new LicenceVariant(['title' => ['de' => $title], 'licence_type' => $type, 'access' => $access]))->shopLabel();

it('says type and use where the name is only the vendor\'s word', function () use ($label) {
	expect($label('floating', LicenceType::Subscription, LicenceAccess::Floating))->toBe('Jahresmietlizenz, Netzwerk (floating)')
		->and($label('node-locked', LicenceType::Perpetual, LicenceAccess::Named))->toBe('Dauerlizenz, Einzelplatz (named)')
		->and($label('Jahresmietlizenz', LicenceType::Subscription, LicenceAccess::Named))->toBe('Jahresmietlizenz, Einzelplatz (named)');
});

it('keeps the rest of the name in front', function () use ($label) {
	expect($label('Teams, named user', LicenceType::Subscription, LicenceAccess::Named))->toBe('Teams, Jahresmietlizenz, Einzelplatz (named)')
		->and($label('Update, floating', LicenceType::Perpetual, LicenceAccess::Floating))->toBe('Update, Dauerlizenz, Netzwerk (floating)')
		->and($label('University 0-14 seats', LicenceType::Subscription, LicenceAccess::Floating))->toBe('University 0-14 seats, Jahresmietlizenz, Netzwerk (floating)');
});

it('leaves a demo its name', function () use ($label) {
	expect($label('Pro Demoversion', null, LicenceAccess::Named))->toBe('Pro Demoversion');
});

/** The licence page's blocks (#71, flat since 2026-11-17): the product's name and the licence's, the rest under it. */
it('titles a licence block with the product and what is left of the licence\'s name', function () {
	$product = new LicenceProduct(['title' => ['de' => 'Corona']]);
	$variant = fn (string $title, ?LicenceType $type, ?LicenceAccess $access) => (new LicenceVariant(['title' => ['de' => $title], 'licence_type' => $type, 'access' => $access]))
		->setRelation('product', $product);

	$solo = $variant('Solo, named', LicenceType::Subscription, LicenceAccess::Named);

	expect($solo->blockTitle())->toBe('Corona Solo (named)')
		->and($solo->blockTerms())->toBe('Jahresmietlizenz, Einzelplatz')
		->and($variant('floating', LicenceType::Subscription, LicenceAccess::Floating)->blockTitle())->toBe('Corona (floating)')
		->and($variant('1 Stück', LicenceType::Subscription, LicenceAccess::Floating)->blockTitle())->toBe('Corona 1 Stück (floating)')
		->and($variant('Vollversion', LicenceType::Perpetual, LicenceAccess::Either)->blockTitle())->toBe('Corona Vollversion')
		->and($variant('Vollversion', LicenceType::Perpetual, LicenceAccess::Either)->blockTerms())->toBe('Dauerlizenz, Einzelplatz oder Netzwerk')
		->and($variant('Demoversion', null, LicenceAccess::Named)->blockTitle())->toBe('Corona Demoversion')
		->and($variant('Demoversion', null, LicenceAccess::Named)->blockTerms())->toBe('');
});
