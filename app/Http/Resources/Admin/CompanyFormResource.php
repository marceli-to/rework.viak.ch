<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A company, as [[CompanySchema]] draws it ([[16-firmenkurse]]). `title` is
 * what the Firmenkurs's picker lists, so one added in its lightbox can be
 * picked at once ([[FormNode]]).
 *
 * @mixin Company
 */
class CompanyFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'company' => $this->company,
			'first_name' => $this->first_name ?? '',
			'last_name' => $this->last_name ?? '',
			'street' => $this->street,
			'street_no' => $this->street_no ?? '',
			'zip' => $this->zip,
			'city' => $this->city,
			'country' => $this->country_code,

			// Read, never sent ([[useResourceForm]]).
			'title' => $this->summary(),
			'courses' => $this->courses_count ?? $this->courses()->count(),
			'invoices' => $this->invoices_count ?? $this->invoices()->count(),
		];
	}
}
