<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTestimonials;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\IsTaxonomy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Software extends Model
{
	use HasTestimonials;
	use HasTranslations;
	use HasUuid;
	use IsTaxonomy;
	use SoftDeletes;

	protected $table = 'software';

	protected $fillable = ['title', 'order', 'publish'];

	public $translatable = ['title'];

	protected function casts(): array
	{
		return ['publish' => 'boolean'];
	}
}
