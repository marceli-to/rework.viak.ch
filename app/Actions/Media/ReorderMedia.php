<?php

declare(strict_types=1);

namespace App\Actions\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * An owner's images in a new order — the order a page shows its visuals in
 * ([[07-dashboard]]). The list arrives whole, so the positions are 0…n-1, and
 * only rows of this owner are touched whatever the request names.
 */
class ReorderMedia
{
	/** @param  array<int, string>  $uuids */
	public function execute(Model $owner, array $uuids): void
	{
		DB::transaction(function () use ($owner, $uuids): void {
			foreach (array_values($uuids) as $position => $uuid) {
				$owner->media()->where('uuid', $uuid)->update(['sort_order' => $position]);
			}
		});
	}
}
