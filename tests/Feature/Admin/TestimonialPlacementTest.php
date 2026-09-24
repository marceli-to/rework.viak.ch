<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Software;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Where a testimonial stands — `testimonial_placements` (Marcel, 2026-09-24).
 * A page picks its testimonials; one quote can stand on several pages, each
 * in its own order.
 */
it('stands on several pages at once, in each page’s own order', function () {
	$course = Course::factory()->create();
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros']]);
	[$a, $b] = Testimonial::factory()->count(2)->create();

	$course->testimonials()->attach([$a->id => ['order' => 2], $b->id => ['order' => 1]]);
	$rhino->testimonials()->attach([$a->id => ['order' => 1], $b->id => ['order' => 2]]);

	expect($course->testimonials->pluck('id')->all())->toBe([$b->id, $a->id])
		->and($rhino->testimonials->pluck('id')->all())->toBe([$a->id, $b->id])
		->and($a->courses->pluck('id')->all())->toBe([$course->id])
		->and($a->software->pluck('id')->all())->toBe([$rhino->id]);
});

it('tells the dashboard where each one is used', function () {
	$course = Course::factory()->create(['number' => 14, 'title' => ['de' => 'SketchUp Kurs']]);
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros']]);
	$placed = Testimonial::factory()->create();
	$course->testimonials()->attach($placed);
	$rhino->testimonials()->attach($placed);
	$loose = Testimonial::factory()->create();

	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);

	expect(array_column($this->actingAs($admin)->getJson("/api/admin/testimonials/{$placed->uuid}")->json('data.placements'), 'label'))
		->toBe(['14 SketchUp Kurs', 'Rhinoceros'])
		->and($this->getJson("/api/admin/testimonials/{$loose->uuid}")->json('data.placements'))->toBe([]);
});

it('comes off every page when it is deleted', function () {
	$course = Course::factory()->create();
	$testimonial = Testimonial::factory()->create();
	$course->testimonials()->attach($testimonial);

	$this->actingAs(User::factory()->admin()->create(['email_verified_at' => now()]))
		->deleteJson("/api/admin/testimonials/{$testimonial->uuid}")->assertNoContent();

	expect(DB::table('testimonial_placements')->count())->toBe(0);
});

/**
 * No foreign key reaches a morph, so a page that is really gone detaches
 * itself; a soft-deleted one keeps its placements, and the relation hides it.
 */
it('drops a page’s placements when the page is gone for good, and keeps them through a soft delete', function () {
	$course = Course::factory()->create();
	$testimonial = Testimonial::factory()->create();
	$course->testimonials()->attach($testimonial);

	$course->delete();
	expect(DB::table('testimonial_placements')->count())->toBe(1)
		->and($testimonial->fresh()->courses)->toHaveCount(0);

	$course->forceDelete();
	expect(DB::table('testimonial_placements')->count())->toBe(0);
});

it('places a testimonial on a page only once', function () {
	$course = Course::factory()->create();
	$testimonial = Testimonial::factory()->create();

	$course->testimonials()->attach($testimonial);

	expect(fn () => $course->testimonials()->attach($testimonial))->toThrow(QueryException::class);
});
