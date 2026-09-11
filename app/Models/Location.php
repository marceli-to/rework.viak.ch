<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Location extends Model
{
	use HasFactory;
	use HasTranslations;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = ['description', 'address', 'map', 'publish'];

	public $translatable = ['description', 'address'];

	protected function casts(): array
	{
		return ['publish' => 'boolean'];
	}

	public function events(): HasMany
	{
		return $this->hasMany(Event::class);
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}
}
