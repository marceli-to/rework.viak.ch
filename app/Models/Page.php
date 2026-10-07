<?php

declare(strict_types=1);

namespace App\Models;

use App\Forms\HomeAboutSchema;
use App\Forms\Schema;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasTestimonials;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * A fixed page that picks testimonials ([[HasTestimonials]]), carries
 * images ([[HasMedia]]) or editable copy (`content`) without being a course
 * or a software: Firmenschulung; the homepage, whose images are the intro's
 * slider (legacy's `heroes` row `home`); and the homepage's About teaser,
 * its heading, text and image ([[HomeAboutSchema]]). Found by its `key` and
 * made on first use, so there is nothing to seed.
 */
class Page extends Model
{
	use HasMedia;
	use HasTestimonials;
	use HasUuid;

	/** The pages there are, and what the dashboard calls them. */
	public const LABELS = [
		'firmenschulung' => 'Firmenschulung',
		'home' => 'Startseite',
		'home-about' => 'Startseite: Über uns',
	];

	/**
	 * The pages with editable copy, and the form that edits it
	 * ([[PageContentController]]).
	 *
	 * @var array<string, class-string<Schema>>
	 */
	public const FORMS = [
		'home-about' => HomeAboutSchema::class,
	];

	protected $fillable = ['key', 'content'];

	protected function casts(): array
	{
		return ['content' => 'array'];
	}

	public static function for(string $key): self
	{
		abort_unless(isset(self::LABELS[$key]), 404);

		return self::query()->firstOrCreate(['key' => $key]);
	}

	public function label(): string
	{
		return self::LABELS[$this->key] ?? $this->key;
	}

	/**
	 * The page's copy as saved, its form's defaults where nothing is yet.
	 *
	 * @return array<string, mixed>
	 */
	public function copy(): array
	{
		$defaults = (new (self::FORMS[$this->key]))->defaults();

		return [...$defaults, ...array_intersect_key($this->content ?? [], $defaults)];
	}
}
