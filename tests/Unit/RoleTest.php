<?php

declare(strict_types=1);

use App\Enums\Role;

it('maps to and from the legacy roles table ids', function (Role $role, int $legacyId) {
	expect($role->legacyId())->toBe($legacyId)
		->and(Role::fromLegacyId($legacyId))->toBe($role);
})->with([
	[Role::Admin, 1],
	[Role::Expert, 2],
]);

it('has no role for legacy\'s Student, or for an unknown id', function () {
	expect(Role::fromLegacyId(3))->toBeNull()
		->and(Role::fromLegacyId(99))->toBeNull();
});
