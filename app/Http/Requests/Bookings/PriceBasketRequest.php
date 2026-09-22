<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookings;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

/**
 * What the customer *selected* — never what it costs ([[06-bookings]]).
 *
 * There is deliberately no price field in these rules. `00-foundation.md` says
 * the basket is priced server-side and the client never supplies a price; this
 * is the shape that makes it impossible to try.
 */
class PriceBasketRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user() !== null;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'items' => ['required', 'array', 'min:1'],
			'items.*.event' => ['required', 'uuid', 'exists:events,uuid'],
			'items.*.rental' => ['sometimes', 'boolean'],
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
			->whereIn('uuid', collect($this->input('items'))->pluck('event'))
			->with(['course', 'dates', 'location', 'experts'])
			->get()
			->keyBy('uuid');

		return collect($this->input('items'))
			->map(fn (array $item) => [
				'event' => $events[$item['event']],
				'rental' => (bool) ($item['rental'] ?? false),
			])
			->values()
			->all();
	}

	public function code(): ?string
	{
		$code = $this->string('code')->trim()->value();

		return $code === '' ? null : $code;
	}
}
