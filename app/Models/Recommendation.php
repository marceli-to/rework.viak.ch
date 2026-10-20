<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One entry of *Das könnte dich auch interessieren* ([[Recommendations]]). */
class Recommendation extends Model
{
	public $timestamps = false;

	protected $fillable = ['owner_type', 'owner_id', 'item_type', 'item_id', 'order'];

	public function owner(): MorphTo
	{
		return $this->morphTo();
	}

	public function item(): MorphTo
	{
		return $this->morphTo();
	}
}
