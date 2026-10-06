<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTestimonials;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * A fixed page that picks testimonials ([[HasTestimonials]]) without being a
 * course or a software: Firmenschulung, later the homepage. Found by its
 * `key` and made on first use, so there is nothing to seed.
 */
class Page extends Model
{
	use HasTestimonials;
	use HasUuid;

	/** The pages there are, and what the dashboard calls them. */
	public const LABELS = [
		'firmenschulung' => 'Firmenschulung',
	];

	protected $fillable = ['key'];

	public static function for(string $key): self
	{
		abort_unless(isset(self::LABELS[$key]), 404);

		return self::query()->firstOrCreate(['key' => $key]);
	}

	public function label(): string
	{
		return self::LABELS[$this->key] ?? $this->key;
	}
}
