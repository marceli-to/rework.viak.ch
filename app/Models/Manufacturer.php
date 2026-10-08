<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * *Hersteller* — McNeel, Chaos, Maxon… ([[05-licences]]). On the product, not on
 * the software group: the Rhino group alone sells plugins by five makers. The
 * taxonomies' shape, so *Einstellungen* edits it, but not one of them: no
 * course is filed under a manufacturer.
 */
class Manufacturer extends Model
{
	use HasTranslations;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = ['title', 'order', 'publish'];

	public $translatable = ['title'];

	protected function casts(): array
	{
		return ['publish' => 'boolean'];
	}

	public function products(): HasMany
	{
		return $this->hasMany(LicenceProduct::class);
	}

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order');
	}
}
