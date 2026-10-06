<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Someone on the team, shown on *Über uns* under the experts ([[04-content]]).
 * Not an account: the team is people VIAK introduces, not people who sign in.
 */
class TeamMember extends Model
{
	use HasFactory;
	use HasMedia;
	use HasTranslations;
	use HasUuid;

	protected $fillable = ['name', 'role', 'publish', 'order'];

	/** @var array<int, string> */
	public $translatable = ['role'];

	protected function casts(): array
	{
		return [
			'publish' => 'boolean',
			'order' => 'integer',
		];
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order')->orderBy('id');
	}
}
