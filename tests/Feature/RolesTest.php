<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

it('lets one person hold several roles at once', function () {
	$user = User::factory()->admin()->expert()->student()->create();

	expect($user->hasRole(Role::Admin))->toBeTrue()
		->and($user->hasRole(Role::Expert))->toBeTrue()
		->and($user->hasRole(Role::Student))->toBeTrue();
});

/**
 * The two people at the top of the public Experten page are Admin + Expert.
 * An earlier draft collapsed roles to a single "highest wins" column, which
 * would have dropped both from the page. This is that regression, pinned.
 */
it('keeps an admin who also teaches in the expert list', function () {
	$adminWhoTeaches = User::factory()->admin()->expert()->create();
	$adminOnly = User::factory()->admin()->create();

	expect($adminWhoTeaches->isExpert())->toBeTrue()
		->and($adminOnly->isExpert())->toBeFalse()
		->and($adminWhoTeaches->isAdmin())->toBeTrue();
});

it('does not treat an admin as an expert by implication', function () {
	expect(User::factory()->admin()->create()->isExpert())->toBeFalse();
});

it('does not treat an expert as an admin', function () {
	expect(User::factory()->expert()->create()->isAdmin())->toBeFalse();
});
