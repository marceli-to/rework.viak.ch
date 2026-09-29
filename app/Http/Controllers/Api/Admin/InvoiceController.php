<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Invoices\ChangeInvoiceAddress;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveInvoiceAddressRequest;
use App\Http\Resources\Admin\InvoiceFormResource;
use App\Http\Resources\Admin\InvoiceRowResource;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * *Rechnungen* ([[07-dashboard]], step 7) — legacy's `Dashboard/InvoiceController`.
 *
 * Legacy sent all four lists whole on every visit. The paid one is 542 rows
 * and grows by about two hundred a year, so each list is its own request here,
 * **searched and paged on the server**, as *Studenten* is. The other three are
 * a few rows each and come as one page.
 */
class InvoiceController extends Controller
{
	private const PER_PAGE = 50;

	/** The list's German name in the query, as *Studenten*'s `?deaktiviert` is. */
	private const STATUSES = [
		'offen' => InvoiceStatus::Open,
		'faellig' => InvoiceStatus::Overdue,
		'bezahlt' => InvoiceStatus::Paid,
		'storniert' => InvoiceStatus::Cancelled,
	];

	/** Newest number first, as legacy has it. */
	public function index(Request $request): AnonymousResourceCollection
	{
		$status = self::STATUSES[(string) $request->query('status')] ?? abort(404);

		$invoices = Invoice::query()
			->inStatus($status)
			->with(['user', 'document'])
			->tap(fn (Builder $query) => $this->search($query, (string) $request->query('suche', '')))
			->orderByDesc('number')
			->paginate(self::PER_PAGE);

		return InvoiceRowResource::collection($invoices);
	}

	public function show(Invoice $invoice): InvoiceFormResource
	{
		return new InvoiceFormResource($invoice->load(['user', 'document']));
	}

	/** Refused, not ignored, once it is paid or cancelled ([[ChangeInvoiceAddress]]). */
	public function update(SaveInvoiceAddressRequest $request, Invoice $invoice, ChangeInvoiceAddress $change): InvoiceFormResource
	{
		abort_unless($invoice->isPending(), 409, 'Eine bezahlte oder stornierte Rechnung wird nicht mehr geändert.');

		return new InvoiceFormResource($change->execute($invoice, $request->snapshot())->load(['user', 'document']));
	}

	/**
	 * Legacy's search: every word has to match the number or the student's
	 * name. The student's city and company too, since the row shows the city.
	 */
	private function search(Builder $query, string $search): void
	{
		foreach (preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
			$like = '%'.addcslashes($word, '%_\\').'%';

			$query->where(fn (Builder $query) => $query
				->where('number', 'like', $like)
				->orWhereHas('user', fn (Builder $user) => $user
					->where('first_name', 'like', $like)
					->orWhere('last_name', 'like', $like)
					->orWhere('city', 'like', $like)
					->orWhere('company', 'like', $like)));
		}
	}
}
