<?php

declare(strict_types=1);

use App\Enums\LicenceAccess;
use App\Enums\LicenceType;
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
