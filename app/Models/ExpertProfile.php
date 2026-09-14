<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The public half of an expert: bio, and the two flags that decide whether
 * they appear on the Experten page. Separate from `users` because 17 of 578
 * people have one, and because the public page should not query the table
 * holding password hashes.
 */
class ExpertProfile extends Model
{
	use HasFactory;

	protected $fillable = ['user_id', 'title', 'description', 'order', 'publish', 'visible'];

	protected function casts(): array
	{
		return [
			'publish' => 'boolean',
			'visible' => 'boolean',
			'order' => 'integer',
		];
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
