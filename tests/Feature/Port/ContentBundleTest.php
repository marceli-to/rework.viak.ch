<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\LicenceOrder;
use App\Models\LicenceOrderItem;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\News;
use App\Models\Project;
use App\Models\Software;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * **Content from staging** ([[ContentBundle]], `content:export` and
 * `content:import`): the Angebote, licences and Seiteninhalte travel with
 * staging's ids; their links to courses find the course by uuid on the
 * target, and a link to a course the target lacks is dropped.
 */
beforeEach(function () {
	Storage::fake('public');
	$this->path = storage_path('framework/testing/content-'.uniqid());
});

afterEach(function () {
	File::deleteDirectory($this->path);
});

it('carries the content across, re-pointing course links by uuid and dropping a missing course', function () {
	$kept = Course::factory()->create();
	$gone = Course::factory()->create();

	$software = Software::create(['title' => ['de' => 'Rhinoceros'], 'publish' => true, 'order' => 0, 'logo' => 'rhino.png']);
	Storage::disk('public')->put('logos/rhino.png', 'png');
	$variant = LicenceVariant::factory()->for(LicenceProduct::factory()->for($software), 'product')->create(['sku' => 'RHN-1003']);
	$kept->software()->attach($software);
	$gone->software()->attach($software);
	Testimonial::factory()->create(['subject_type' => Course::class, 'subject_id' => $kept->id]);
	$project = Project::factory()->create();
	DB::table('course_project')->insert([['project_id' => $project->id, 'course_id' => $kept->id, 'order' => 0], ['project_id' => $project->id, 'course_id' => $gone->id, 'order' => 1]]);
	News::factory()->create(['title' => 'Von Staging']);

	$this->artisan('content:export', ['path' => $this->path])->assertSuccessful();

	// Production: the same course under another id, the other one absent,
	// and the catalogue as the ports left it.
	DB::table('course_taxonomy')->delete();
	DB::table('course_project')->delete();
	DB::table('testimonials')->delete();
	$uuid = $kept->uuid;
	$kept->forceDelete();
	$gone->forceDelete();
	$here = Course::factory()->create();
	DB::table('courses')->where('id', $here->id)->update(['uuid' => $uuid]);
	Software::create(['title' => ['de' => 'Vom Port'], 'order' => 0]);
	Storage::disk('public')->delete('logos/rhino.png');

	$this->artisan('content:import', ['path' => $this->path, '--dry-run' => true])->assertSuccessful();
	expect(Software::where('title->de', 'Vom Port')->exists())->toBeTrue();

	$this->artisan('content:import', ['path' => $this->path])->assertSuccessful();

	expect(Software::pluck('id')->all())->toBe([$software->id])
		->and(LicenceVariant::sole()->id)->toBe($variant->id)
		->and(Software::find($software->id)->courses()->pluck('courses.id')->all())->toBe([$here->id])
		->and(Testimonial::sole()->subject_id)->toBe($here->id)
		->and(DB::table('course_project')->pluck('course_id')->all())->toBe([$here->id])
		->and(News::sole()->getTranslation('title', 'de'))->toBe('Von Staging');

	Storage::disk('public')->assertExists('logos/rhino.png');
});

it('refuses to import over licence orders', function () {
	$this->artisan('content:export', ['path' => $this->path])->assertSuccessful();
	$order = LicenceOrder::create(['number' => '000101', 'user_id' => User::factory()->create()->id]);
	LicenceOrderItem::create(['licence_order_id' => $order->id, 'title' => 'Rhino 9', 'price' => '995.00', 'quantity' => 1, 'position' => 1]);

	$this->artisan('content:import', ['path' => $this->path])->assertFailed();
});
