<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\Invoice;
use App\Models\UserDocument;

/**
 * An invoice's PDF: the one already made, or made now ([[RenderInvoice]]).
 * What a mail attaches ([[10-mail]]), so a confirmation sent twice attaches
 * the same file rather than rendering a second.
 */
class InvoiceDocument
{
	public function __construct(private readonly RenderInvoice $render) {}

	public function execute(Invoice $invoice): UserDocument
	{
		return UserDocument::query()
			->where('documentable_type', $invoice->getMorphClass())
			->where('documentable_id', $invoice->getKey())
			->where('type', DocumentType::Invoice)
			->first() ?? $this->render->execute($invoice);
	}
}
