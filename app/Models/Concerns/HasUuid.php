<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Public-facing identifier. Every legacy table carries one and every URL and
 * API payload uses it instead of the auto-increment id — a convention worth
 * keeping, so it carries over unchanged.
 */
trait HasUuid
{
	protected static function bootHasUuid(): void
	{
		static::creating(function (self $model): void {
			if (blank($model->uuid)) {
				$model->uuid = (string) Str::uuid();
			}
		});
	}

	public function getRouteKeyName(): string
	{
		return 'uuid';
	}
}
