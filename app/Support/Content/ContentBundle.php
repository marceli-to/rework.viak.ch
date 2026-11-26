<?php

declare(strict_types=1);

namespace App\Support\Content;

use App\Models\Category;
use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\Software;
use App\Models\TeamMember;
use App\Models\Testimonial;

/**
 * **What VIAK edits on staging and production takes from there** ([[01-schema]]
 * *Content from staging*, Marcel 2026-11-26): the Angebote with their
 * licences, the Seiteninhalte and the five categories. None of it exists on
 * the live site, so at the cutover it comes from staging, on top of the ports.
 * Kurse, Veranstaltungen, users, bookings, invoices and experts come from the
 * live database and are not touched.
 *
 * The tables are copied whole, **with staging's ids**: after the import they
 * hold staging's rows and nothing else, so no id can meet another. What they
 * point at outside the bundle is a course, and a course is found on the
 * target by its uuid (legacy's, carried by `port:courses`), else by its slug.
 * A link to a course the target does not have is dropped and reported.
 *
 * Images are the one shared table: `media` also holds the courses', events'
 * and messages' files. Only the rows of the bundle's own models are replaced,
 * and they get new ids.
 */
final class ContentBundle
{
	/**
	 * Copied whole, in an order that inserts parents before children.
	 * Each entry: table => course reference columns, as [id column, type column|null].
	 *
	 * @var array<string, list<array{0: string, 1: string|null}>>
	 */
	public const TABLES = [
		'categories' => [],
		'manufacturers' => [],
		'software' => [],
		'software_category' => [],
		'software_host' => [],
		'licence_products' => [],
		'licence_product_category' => [],
		'licence_product_host' => [],
		'licence_variants' => [],
		'projects' => [],
		'project_software' => [],
		'course_project' => [['course_id', null]],
		'recommendations' => [['owner_id', 'owner_type'], ['item_id', 'item_type']],
		'testimonials' => [['subject_id', 'subject_type']],
		'testimonial_placements' => [['placeable_id', 'placeable_type']],
		'news' => [],
		'team_members' => [],
		'pages' => [],
	];

	/**
	 * Only these rows of `course_taxonomy`: a course's Angebote and
	 * categories, which are set on the Angebot side and point at bundle
	 * tables. Levels, languages and tags stay the live site's.
	 */
	public const COURSE_TAXONOMY_TYPES = [Software::class, Category::class];

	/** The models whose images travel with the bundle. */
	public const MEDIA_OWNERS = [
		Software::class, Page::class, TeamMember::class, Project::class,
		News::class, Testimonial::class, LicenceProduct::class,
	];

	public const COURSE = Course::class;
}
