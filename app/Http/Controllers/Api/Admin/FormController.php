<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Forms\CourseSchema;
use App\Forms\CustomerSchema;
use App\Forms\DiscountCodeSchema;
use App\Forms\EventSchema;
use App\Forms\ExpertSchema;
use App\Forms\InvoiceSchema;
use App\Forms\LicenceProductSchema;
use App\Forms\LicenceVariantSchema;
use App\Forms\LocationSchema;
use App\Forms\ProfileSchema;
use App\Forms\ProjectSchema;
use App\Forms\Schema;
use App\Forms\TeamMemberSchema;
use App\Forms\TermSchema;
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
		'team-member' => TeamMemberSchema::class,
		'project' => ProjectSchema::class,
		'event' => EventSchema::class,
		'expert' => ExpertSchema::class,
		'customer' => CustomerSchema::class,
		'discount-code' => DiscountCodeSchema::class,
		'term' => TermSchema::class,
		'location' => LocationSchema::class,
		'profile' => ProfileSchema::class,
		'invoice' => InvoiceSchema::class,
		'licence' => LicenceProductSchema::class,
		'licence-variant' => LicenceVariantSchema::class,
	];

	public function show(string $form): JsonResponse
	{
		abort_unless(isset(self::FORMS[$form]), 404);

		return response()->json(['data' => (new (self::FORMS[$form]))->toArray()]);
	}
}
