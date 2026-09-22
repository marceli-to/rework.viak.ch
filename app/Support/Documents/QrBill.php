<?php

declare(strict_types=1);

namespace App\Support\Documents;

use App\Models\Invoice;
use RuntimeException;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\HtmlOutput;
use Sprain\SwissQrBill\QrBill as SprainQrBill;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

/**
 * The Swiss QR bill at the foot of an invoice ([[03-invoices]]).
 *
 * ## What this replaces
 *
 * Legacy hand-builds the whole payment part: 150 lines in `Services/Pdf/Invoice/Qr.php`
 * that assemble the reference number by string concatenation and compute its
 * modulo-10 check digit from a lookup table, plus **251 lines of Blade** laying
 * the slip out by hand in millimetres. The library does both, and does them to
 * the specification.
 *
 * **Legacy's reference number is correct**, which is worth recording because it
 * does not look it: `'00 00000' . ' 00000 ' . …` reads like an ESR reference
 * from the orange payment slips, and it is in fact a valid 27-digit QR
 * reference in the conventional 2-5-5-5-5-5 grouping, with the same check digit
 * `QrPaymentReferenceGenerator` produces. Verified against the library for
 * three invoice numbers before any of this was written. So the simplification
 * below changes 400 lines of code and not one character of output.
 *
 * ## What it does change
 *
 * - **`StructuredAddress`, not `CombinedAddress`.** The standard withdrew the
 *   combined form and v5 of the library removed the class, so the creditor's
 *   street number and postal code are separate fields now
 *   ([[config/documents.php]]).
 * - **The bill is validated.** `getViolations()` is asked before anything is
 *   rendered, so a malformed IBAN or an amount over the 999 999 999.99 ceiling
 *   is an exception here rather than a slip a bank rejects. Legacy never calls
 *   it.
 * - **The debtor is filled in.** Legacy sets no `ultimateDebtor` at all, so the
 *   *Zahlbar durch* field is printed from the invoice address into its own
 *   hand-built HTML while the QR code itself carries nothing. A scanned bill
 *   therefore arrives at the bank with no payer.
 *
 * ## The one number the slip cannot round
 *
 * `PaymentAmountInformation` takes a float and the invoice stores a
 * `decimal:2` string. Cast once, here, rather than letting each caller decide —
 * and the grand total is the only figure on the bill, so a centime lost in the
 * cast is a centime the customer pays.
 */
final class QrBill
{
	public function __construct(private readonly PaymentSlipStyles $styles = new PaymentSlipStyles) {}

	/** The payment part as HTML, ready to be included in the invoice template. */
	public function html(Invoice $invoice): string
	{
		$output = new HtmlOutput($this->build($invoice), 'de');

		// SVG survives dompdf and stays sharp at any print resolution; the
		// library's PNG alternative rasterises at a fixed size. Verified by
		// rendering both and scanning the result.
		$output->setQrCodeImageFormat('svg');

		return $output->getPaymentPart().$this->styles->forDompdf();
	}

	/**
	 * The reference as it is printed and as it is encoded — one string, so the
	 * two cannot disagree.
	 */
	public function reference(Invoice $invoice): string
	{
		return QrPaymentReferenceGenerator::generate(null, $invoice->number);
	}

	private function build(Invoice $invoice): SprainQrBill
	{
		$creditor = config('documents.creditor');

		$bill = SprainQrBill::create();

		$bill->setCreditor(StructuredAddress::createWithStreet(
			$creditor['name'],
			$creditor['street'],
			$creditor['building_number'],
			$creditor['postal_code'],
			$creditor['city'],
			$creditor['country'],
		));

		$bill->setCreditorInformation(CreditorInformation::create(
			// The IBAN is written with spaces for people and must reach the
			// library without them.
			str_replace(' ', '', (string) config('documents.qr_iban')),
		));

		$bill->setPaymentAmountInformation(PaymentAmountInformation::create(
			(string) config('documents.currency'),
			(float) $invoice->grand_total,
		));

		$bill->setPaymentReference(PaymentReference::create(
			PaymentReference::TYPE_QR,
			$this->reference($invoice),
		));

		$bill->setAdditionalInformation(AdditionalInformation::create(
			'Rechnung '.$invoice->number,
		));

		if ($debtor = $this->debtor($invoice)) {
			$bill->setUltimateDebtor($debtor);
		}

		$violations = $bill->getViolations();

		if (count($violations) > 0) {
			// A programming or configuration error — a bad IBAN, an address
			// longer than the standard allows — and not something a customer
			// can cause. Loud, because the alternative is a payment slip that
			// looks right and cannot be paid.
			throw new RuntimeException(sprintf(
				'QR bill for invoice %s is invalid: %s',
				$invoice->number,
				collect($violations)->map(fn ($v) => $v->getPropertyPath().' — '.$v->getMessage())->implode('; '),
			));
		}

		return $bill;
	}

	/**
	 * Who is paying, for the QR code's own *Zahlbar durch* field.
	 *
	 * The frozen invoice address where there is one, and the customer's own
	 * otherwise — the same precedence the invoice's own address block uses
	 * ([[Invoice]]). **Null rather than a guess** when the address cannot be
	 * split into the structured fields the standard wants: an empty debtor is
	 * valid and prints an outlined box for it to be written into by hand, while
	 * a wrong one is a payment that arrives from the wrong person.
	 */
	private function debtor(Invoice $invoice): ?StructuredAddress
	{
		$address = $invoice->invoice_address ?: null;

		if ($address === null) {
			$user = $invoice->user;

			if ($user === null || blank($user->zip) || blank($user->city)) {
				return null;
			}

			return StructuredAddress::createWithStreet(
				$this->clamp($user->name),
				$this->clamp((string) $user->street, 70),
				blank($user->street_no) ? null : (string) $user->street_no,
				(string) $user->zip,
				$this->clamp((string) $user->city, 35),
				strtoupper((string) ($user->country_code ?: 'CH')),
			);
		}

		if (blank($address['zip'] ?? null) || blank($address['city'] ?? null)) {
			return null;
		}

		$name = trim(($address['company'] ?? '').' '.($address['first_name'] ?? '').' '.($address['last_name'] ?? ''));

		if ($name === '') {
			return null;
		}

		return StructuredAddress::createWithStreet(
			$this->clamp($name),
			$this->clamp((string) ($address['street'] ?? ''), 70),
			blank($address['street_no'] ?? null) ? null : (string) $address['street_no'],
			(string) $address['zip'],
			$this->clamp((string) $address['city'], 35),
			strtoupper((string) ($address['country_code'] ?? 'CH')),
		);
	}

	/**
	 * The standard caps a name at 70 characters and a town at 35, and the
	 * library rejects anything longer. Truncating is the lesser of the two
	 * failures: a slightly shortened name still reaches the right account,
	 * where a thrown exception stops an invoice being issued at all.
	 */
	private function clamp(string $value, int $length = 70): string
	{
		return mb_substr(trim($value), 0, $length);
	}
}
