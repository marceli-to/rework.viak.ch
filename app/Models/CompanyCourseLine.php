<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line a Firmenkurs is invoiced with, typed by VIAK: *Firmenschulung Rhino,
 * 2 Tage, pauschal*, *Spesen*. Exempt as a course is, unless `taxed`
 * ([[InvoiceItemType]]).
 */
class CompanyCourseLine extends Model
{
	protected $fillable = ['position', 'description', 'net', 'taxed'];

	protected function casts(): array
	{
		return ['net' => 'decimal:2', 'taxed' => 'boolean'];
	}

	public function companyCourse(): BelongsTo
	{
		return $this->belongsTo(CompanyCourse::class);
	}

	public function type(): InvoiceItemType
	{
		return $this->taxed ? InvoiceItemType::Other : InvoiceItemType::Course;
	}
}
