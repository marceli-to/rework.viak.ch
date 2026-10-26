<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\CompanyCourse;
use App\Models\Invoice;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A Firmenkurs's invoice, to the e-mail typed on it ([[16-firmenkurse]]). New:
 * legacy had no Firmenkurse. Addressed with *Sie*, as a company is, where the
 * shop's mails say *Du* to a student.
 *
 * QR bill only: the company has no account, and the card page is for an
 * invoice's signed-in owner ([[PaymentController]]).
 */
class CompanyCourseInvoice extends VIAKMail
{
	public function __construct(
		public readonly CompanyCourse $course,
		public readonly Invoice $invoice,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Rechnung '.$this->invoice->number.' – '.$this->course->name());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.company-course.invoice', with: [
			'course' => $this->course,
			'invoice' => $this->invoice,
			'dates' => $this->course->loadMissing('dates')->dates->map(fn ($day) => $day->date->format('d.m.Y'))->implode(', '),
		]);
	}

	/** @return array<int, Attachment> */
	public function attachments(): array
	{
		$path = $this->invoice->companyPdfPath();

		return $path ? [Attachment::fromStorageDisk('documents', $path)->as($this->invoice->filename)->withMime('application/pdf')] : [];
	}
}
