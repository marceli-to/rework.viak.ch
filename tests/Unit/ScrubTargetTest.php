<?php

declare(strict_types=1);

use App\Support\ScrubTarget;

it('accepts a database whose name marks it as a copy', function (string $database) {
	expect(ScrubTarget::isObviouslyACopy($database))->toBeTrue();
})->with([
	'viak_legacy',
	'viak_prod_copy',
	'VIAK_LEGACY',
	'viak_local',
	'viak_rework_test',
	'scrubbed_dump',
]);

it('refuses a database that could be production', function (string $database) {
	expect(ScrubTarget::isObviouslyACopy($database))->toBeFalse();
})->with([
	'viakch',
	'viak',
	'viak_production',
	'viak_prod',
	'',
]);
