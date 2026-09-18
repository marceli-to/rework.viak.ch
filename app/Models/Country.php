<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Reference data. Keyed by ISO 3166-1 alpha-2, so `country_code` is readable
 * without a join and survives a re-import of the list.
 */
class Country extends Model
{
	use HasFactory;
	use HasTranslations;

	public $incrementing = false;

	public $timestamps = false;

	protected $primaryKey = 'code';

	protected $keyType = 'string';

	protected $fillable = ['code', 'name', 'order'];

	/** @var array<int, string> */
	public array $translatable = ['name'];

	public function getRouteKeyName(): string
	{
		return 'code';
	}
}
