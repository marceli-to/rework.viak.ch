<?php

declare(strict_types=1);

use App\Enums\Role;

it('maps to and from the legacy roles table ids', function (Role $role, int $legacyId) {
	expect($role->legacyId())->toBe($legacyId)
		->and(Role::fromLegacyId($legacyId))->toBe($role);
})->with([
	[Role::Admin, 1],
	[Role::Expert, 2],
	[Role::Student, 3],
]);

it('falls back to student for an unknown legacy id', function () {
	expect(Role::fromLegacyId(99))->toBe(Role::Student);
});
