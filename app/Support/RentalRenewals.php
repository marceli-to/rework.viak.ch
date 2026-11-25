<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\LicenceType;
use App\Models\LicenceOrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * *Mietlizenzen, die bald auslaufen* (#83, the client 2026-11-25: "build the
 * list, maybe even with the reminder process"; Marcel chose a button per
 * line, no automatic mail). `05-licences.md`, *Rentals due for renewal*.
 *
 * **Nothing about a licence's lifespan is stored.** A rental is still a line
 * on an order ([[05-licences]], *Meine Lizenzen is history*); its year is
 * worked out here, the order's date plus one year, as the old shop's
 * customers knew it. Imported shop orders count alike.
 *
 * - **A rental** is a line whose variant is a *Jahresmietlizenz*, or, for a
 *   line with no variant (the shop's old versions, a free line), whose title
 *   says *miet*. A line at price 0 is a demo and never one.
 * - **Due**: its year ends between {@see BEHIND} days ago and {@see AHEAD}
 *   days from now, soonest first.
 * - **Renewed** and so left out: the same customer ordered the same variant,
 *   or a line of the same title, after it, on an order not cancelled.
 */
final class RentalRenewals
{
	/** Days past the end a line still shows, for a customer who missed it. */
	public const BEHIND = 30;

	/** Days before the end a line starts to show. */
	public const AHEAD = 60;

	/** @return Collection<int, LicenceOrderItem> */
	public static function due(?CarbonImmutable $today = null): Collection
	{
		$today = ($today ?? CarbonImmutable::now())->startOfDay();

		return LicenceOrderItem::query()
			->where('price', '>', 0)
			->where(fn (Builder $query) => $query
				->whereHas('variant', fn (Builder $variant) => $variant->where('licence_type', LicenceType::Subscription))
				->orWhere(fn (Builder $free) => $free->whereNull('licence_variant_id')->where('title', 'like', '%miet%')))
			->whereHas('order', fn (Builder $order) => $order
				->whereNull('cancelled_at')
				->whereBetween('created_at', [
					$today->subYear()->subDays(self::BEHIND),
					$today->subYear()->addDays(self::AHEAD)->endOfDay(),
				]))
			->with(['order.user', 'reminder', 'variant.product.software'])
			->get()
			->reject(fn (LicenceOrderItem $item) => self::renewed($item))
			->sortBy(fn (LicenceOrderItem $item) => [$item->order->created_at, $item->id])
			->values();
	}

	/** The day the rental's year ends: the order's date plus one year. */
	public static function endsOn(LicenceOrderItem $item): CarbonImmutable
	{
		return $item->order->created_at->toImmutable()->addYear()->startOfDay();
	}

	/** Ordered again by the same customer, after this one, and not cancelled. */
	public static function renewed(LicenceOrderItem $item): bool
	{
		return LicenceOrderItem::query()
			->whereKeyNot($item->getKey())
			->when(
				$item->licence_variant_id !== null,
				fn (Builder $query) => $query->where('licence_variant_id', $item->licence_variant_id),
				fn (Builder $query) => $query->where('title', $item->title),
			)
			->whereHas('order', fn (Builder $order) => $order
				->where('user_id', $item->order->user_id)
				->whereNull('cancelled_at')
				->where('created_at', '>', $item->order->created_at))
			->exists();
	}
}
