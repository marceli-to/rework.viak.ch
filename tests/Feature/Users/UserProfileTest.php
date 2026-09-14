<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Enums\OperatingSystem;
use App\Models\ExpertProfile;
use App\Models\User;

/**
 * Legacy stored the surname in a column called `name`, so `$user->name` meant
 * "Müller" on User and "the whole name" on every other model. The columns are
 * split now and `name` composes them — the one job the old column did.
 */
it('composes a display name from the two columns', function () {
	$user = User::factory()->create(['first_name' => 'Antonia', 'last_name' => 'Haller']);

	expect($user->name)->toBe('Antonia Haller');
});

it('does not leave a stray space when half the name is missing', function () {
	$user = User::factory()->create(['first_name' => '', 'last_name' => 'Haller']);

	expect($user->name)->toBe('Haller');
});

it('reads several operating systems off one user', function () {
	$user = User::factory()->create([
		'operating_systems' => [OperatingSystem::MacOS, OperatingSystem::Windows],
	]);

	expect($user->fresh()->operating_systems)->toHaveCount(2)
		->and($user->fresh()->operating_systems->first())->toBe(OperatingSystem::MacOS);
});

it('casts gender to the enum', function () {
	$user = User::factory()->create(['gender' => Gender::Female]);

	expect($user->fresh()->gender)->toBe(Gender::Female);
});

/**
 * The Experten page needs the role AND both flags. 17 people in production
 * have a bio and only 10 are listed, so the profile existing is not the gate.
 */
it('lists only experts whose profile is published and visible', function () {
	$listed = ExpertProfile::factory()->create(['order' => 1])->user;
	$hidden = ExpertProfile::factory()->hidden()->create(['order' => 2])->user;
	$unpublished = ExpertProfile::factory()->create(['publish' => false, 'order' => 3])->user;
	$noProfile = User::factory()->expert()->create();

	$experts = User::publiclyListedExperts()->pluck('id');

	expect($experts)->toContain($listed->id)
		->and($experts)->not->toContain($hidden->id)
		->and($experts)->not->toContain($unpublished->id)
		->and($experts)->not->toContain($noProfile->id);
});

it('keeps a listed expert out of the page when they lose the expert role', function () {
	$profile = ExpertProfile::factory()->create();
	DB::table('role_user')->where('user_id', $profile->user_id)->delete();

	expect(User::publiclyListedExperts()->pluck('id'))->not->toContain($profile->user_id);
});

it('orders the expert page by hand, not by id', function () {
	$third = ExpertProfile::factory()->create(['order' => 30])->user;
	$first = ExpertProfile::factory()->create(['order' => 10])->user;
	$second = ExpertProfile::factory()->create(['order' => 20])->user;

	expect(User::publiclyListedExperts()->pluck('id')->all())
		->toBe([$first->id, $second->id, $third->id]);
});
