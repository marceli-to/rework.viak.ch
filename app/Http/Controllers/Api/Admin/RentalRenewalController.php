<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RentalRenewalReminder;
use App\Models\LicenceOrderItem;
use App\Support\RentalRenewals;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * *Mietlizenzen, die bald auslaufen* under *Bestellungen* ([[RentalRenewals]],
 * #83): the rentals whose year ends soon, and *Erinnerung senden* per line.
 * Sending again is allowed; the row says when the last one went.
 */
class RentalRenewalController extends Controller
{
	public function index(): JsonResponse
	{
		return response()->json(['data' => RentalRenewals::due()->map(fn (LicenceOrderItem $item) => $this->row($item))->all()]);
	}

	public function remind(Request $request, LicenceOrderItem $item): JsonResponse
	{
		abort_if($item->order->isCancelled(), 422, 'Die Bestellung ist storniert.');

		Mail::to($item->order->user)->send(new RentalRenewalReminder($item));

		$item->forceFill(['reminded_at' => now(), 'reminded_by' => $request->user()->id])->save();

		return response()->json(['data' => $this->row($item->load(['order.user', 'reminder']))]);
	}

	/** @return array<string, mixed> */
	private function row(LicenceOrderItem $item): array
	{
		return [
			'uuid' => $item->uuid,
			'line' => $item->description(),
			'ends' => RentalRenewals::endsOn($item)->toDateString(),
			'order' => ['uuid' => $item->order->uuid, 'number' => $item->order->number],
			'customer' => ['name' => $item->order->user->name, 'email' => $item->order->user->email],
			'reminded_at' => $item->reminded_at?->toDateString(),
			'reminded_by' => $item->reminder?->name,
		];
	}
}
