<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\CompanyCourse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A Firmenkurs, as [[CompanyCourseSchema]] draws it, and for its row on the
 * list ([[16-firmenkurse]]). The invoice, and whether it locks the form, are
 * read and never sent.
 *
 * @mixin CompanyCourse
 */
class CompanyCourseFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$time = fn (?string $value) => $value === null ? '' : substr($value, 0, 5);
		$invoice = $this->invoice;

		return [
			'uuid' => $this->uuid,
			'company' => $this->company?->uuid ?? '',
			'course' => $this->course?->uuid ?? '',
			'title' => $this->title ?? '',
			'location' => $this->location ?? '',
			'participants' => $this->participants ?? '',
			'dates' => $this->dates->map(fn ($day) => [
				'date' => $day->date->toDateString(),
				'time_start' => $time($day->time_start),
				'time_end' => $time($day->time_end),
			])->all(),
			'email' => $this->email,
			'lines' => $this->lines->map(fn ($line) => [
				'description' => $line->description,
				'net' => (string) $line->net,
				'taxed' => $line->taxed,
			])->all(),
			'remarks' => $this->remarks ?? '',

			// Read, never sent ([[useResourceForm]]).
			'name' => $this->name(),
			'company_name' => $this->company?->company,
			'first_date' => $this->dates->first()?->date->toDateString(),
			'last_date' => $this->dates->last()?->date->toDateString(),
			'locked' => $this->isLocked(),
			'invoice' => $invoice ? [
				'uuid' => $invoice->uuid,
				'number' => $invoice->number,
				'status' => $invoice->status->value,
				'pending' => $invoice->isPending(),
				'grand_total' => (string) $invoice->grand_total,
				'due_at' => $invoice->due_at?->toDateString(),
				'document' => $invoice->pdfUrl(),
			] : null,
		];
	}
}
