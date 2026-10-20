<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookings;

use App\Models\Event;
use App\Models\LicenceVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

/**
 * What the customer *selected* — never what it costs ([[06-bookings]]).
 *
 * There is deliberately no price field in these rules. `00-foundation.md` says
 * the basket is priced server-side and the client never supplies a price; this
 * is the shape that makes it impossible to try.
 */
class PriceBasketRequest extends FormRequest
{
	/** @var Collection<string, LicenceVariant>|null */
	private ?Collection $resolvedVariants = null;

	/**
	 * **Open to a guest** since the checkout was rethought ([[13-checkout]]):
	 * the basket is everyone's, and the login comes at *Weiter*. Legacy, and
	 * the rework until 2026-10-20, priced it only for a signed-in student, so a
	 * visitor could fill a basket and not see what it cost. A price is no
	 * secret: the course and software pages print every one of them.
	 */
	public function authorize(): bool
	{
		return true;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'items' => ['required_without:licences', 'array'],
			'items.*.event' => ['required', 'uuid', 'exists:events,uuid'],
			'items.*.rental' => ['sometimes', 'boolean'],
			'licences' => ['required_without:items', 'array'],
			'licences.*.variant' => ['required', 'uuid', 'exists:licence_variants,uuid'],
			'licences.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
			'licences.*.host' => ['nullable', 'string', 'max:255'],
			'code' => ['nullable', 'string', 'max:14'],
		];
	}

	/**
	 * The selections, with each event resolved.
	 *
	 * `location` and `experts` join `course` and `dates` because the **basket
	 * page** draws a full row per item — where it is and with whom — and the
	 * page has nothing else to ask ([[09-public-site]]). `EventResource` guards
	 * both with `whenLoaded`, so an endpoint that does not load them still does
	 * not serialise them.
	 *
	 * @return array<int, array{event: Event, rental: bool}>
	 */
	public function selections(): array
	{
		$events = Event::query()
			->whereIn('uuid', collect((array) $this->input('items', []))->pluck('event'))
			->with(['course', 'dates', 'location', 'experts'])
			->get()
			->keyBy('uuid');

		return collect((array) $this->input('items', []))
			->map(fn (array $item) => [
				'event' => $events[$item['event']],
				'rental' => (bool) ($item['rental'] ?? false),
			])
			->values()
			->all();
	}

	/**
	 * What the software page lets through, checked again here: the licence is
	 * still in the shop, the quantity meets its minimum, and a plugin names
	 * one of its hosts. Each message names the licence, because the basket
	 * drops the line it is about and says why ([[basket]]).
	 *
	 * @return array<int, callable(Validator): void>
	 */
	public function after(): array
	{
		return [function (Validator $validator): void {
			if ($validator->errors()->isNotEmpty()) {
				return;
			}

			foreach ((array) $this->input('licences', []) as $i => $line) {
				$variant = $this->variants()->get($line['variant']);
				if (! $variant?->listed || ! $variant->product?->publish) {
					$validator->errors()->add("licences.{$i}.variant", $variant
						? 'Die Lizenz «'.$variant->product?->getTranslation('title', 'de').', '.$variant->shopLabel().'» ist nicht mehr bestellbar.'
						: 'Eine Lizenz im Warenkorb ist nicht mehr bestellbar.');

					continue;
				}

				$name = $variant->product->getTranslation('title', 'de').', '.$variant->shopLabel();

				if ((int) $line['quantity'] < max(1, (int) $variant->min_quantity)) {
					$validator->errors()->add("licences.{$i}.quantity", '«'.$name.'»: mindestens '.$variant->min_quantity.' Stück.');
				}

				$hosts = $variant->product->hostNames();
				$host = $line['host'] ?? null;

				if ($hosts !== [] && ! in_array($host, $hosts, true)) {
					$validator->errors()->add("licences.{$i}.host", '«'.$name.'»: bitte Hostsoftware wählen.');
				} elseif ($hosts === [] && filled($host)) {
					$validator->errors()->add("licences.{$i}.host", '«'.$name.'» hat keine Hostsoftware.');
				}
			}
		}];
	}

	/**
	 * The licences, each variant resolved.
	 *
	 * @return array<int, array{variant: LicenceVariant, quantity: int, host: ?string}>
	 */
	public function licences(): array
	{
		return collect((array) $this->input('licences', []))
			->map(fn (array $line) => [
				'variant' => $this->variants()->get($line['variant']),
				'quantity' => (int) $line['quantity'],
				'host' => ($line['host'] ?? null) ?: null,
			])
			->values()
			->all();
	}

	/** @return Collection<string, LicenceVariant> */
	private function variants(): Collection
	{
		return $this->resolvedVariants ??= LicenceVariant::query()
			->whereIn('uuid', collect((array) $this->input('licences', []))->pluck('variant'))
			->with('product.hosts')
			->get()
			->keyBy('uuid');
	}

	public function code(): ?string
	{
		$code = $this->string('code')->trim()->value();

		return $code === '' ? null : $code;
	}
}
