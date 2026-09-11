<?php

declare(strict_types=1);

use App\Enums\Role;

it('treats roles as a hierarchy', function () {
	expect(Role::Admin->atLeast(Role::Expert))->toBeTrue()
		->and(Role::Admin->atLeast(Role::Student))->toBeTrue()
		->and(Role::Expert->atLeast(Role::Student))->toBeTrue()
		->and(Role::Expert->atLeast(Role::Admin))->toBeFalse()
		->and(Role::Student->atLeast(Role::Expert))->toBeFalse();
});

it('lets every role satisfy itself', function (Role $role) {
	expect($role->atLeast($role))->toBeTrue();
})->with(Role::cases());
