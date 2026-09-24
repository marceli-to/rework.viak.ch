<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Forms\CourseSchema;
use App\Forms\Schema;
use App\Forms\TestimonialSchema;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * A form, as the dashboard draws it — the field kit's other half ([[Field]],
 * [[07-dashboard]]). The same schema validates the request that saves it, so
 * what the admin is shown as required is what the server requires.
 */
class FormController extends Controller
{
	/** @var array<string, class-string<Schema>> */
	private const FORMS = [
		'course' => CourseSchema::class,
		'testimonial' => TestimonialSchema::class,
	];

	public function show(string $form): JsonResponse
	{
		abort_unless(isset(self::FORMS[$form]), 404);

		return response()->json(['data' => (new (self::FORMS[$form]))->toArray()]);
	}
}
