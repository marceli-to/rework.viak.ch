<?php

declare(strict_types=1);

namespace App\Actions\CompanyCourses;

use App\Actions\Documents\RenderInvoice;
use App\Actions\Invoices\IssueInvoice;
use App\Mail\CompanyCourseInvoice;
use App\Models\CompanyCourse;
use App\Models\CompanyCourseLine;
use App\Models\Invoice;
use App\Support\InvoiceLine;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * *Rechnung stellen* on a Firmenkurs ([[16-firmenkurse]]).
 *
 * Its lines become the invoice's as they stand, billed to the address typed
 * on it, due in the period every invoice has. The PDF is
 * made and mailed to the Firmenkurs's e-mail. From here the Firmenkurs is
 * locked ([[CompanyCourse::isLocked]]) until the invoice is withdrawn
 * ([[WithdrawCompanyCourseInvoice]]).
 */
class IssueCompanyCourseInvoice
{
	public function __construct(
		private readonly IssueInvoice $issue,
		private readonly RenderInvoice $render,
	) {}

	public function execute(CompanyCourse $course): Invoice
	{
		$course->loadMissing('lines', 'invoice');

		if ($course->isLocked()) {
			throw new RuntimeException("Firmenkurs {$course->uuid} already has invoice {$course->invoice->number}.");
		}

		$invoice = $this->issue->execute(
			customer: $course,
			lines: $course->lines->map(fn (CompanyCourseLine $line) => new InvoiceLine(
				type: $line->type(),
				description: $line->description,
				net: (string) $line->net,
				itemable: $course,
			))->all(),
			invoiceAddress: $course->toSnapshot(),
		);

		$course->forceFill(['invoice_id' => $invoice->id])->save();
		$this->render->forCompany($invoice);

		Mail::to($course->email)->send(new CompanyCourseInvoice($course, $invoice));

		return $invoice;
	}
}
