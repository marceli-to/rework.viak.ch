<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\LicenceVariant;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * An order VIAK enters by hand ([[05-licences]], #36): lines of a variant,
 * hidden ones included, or **free lines** (`free`) with a typed title and price.
 *
 * The minimum quantity is not enforced here: it is the shop's rule for a
 * basket, and an order taken by phone is entered as it was sold. The form
 * starts a line at the minimum.
 */
class StoreLicenceOrderRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	public function rules(): array
	{
		$customer = $this->route('customer');

		return [
			'invoice_address' => ['nullable', 'uuid', Rule::exists('user_addresses', 'uuid')->where('user_id', $customer instanceof User ? $customer->id : 0)],
			'delivery_email' => ['nullable', 'email', 'max:255'],
			'lines' => ['required', 'array', 'min:1', 'max:50'],
			'lines.*.free' => ['boolean'],
			'lines.*.variant' => ['nullable', 'exclude_if:lines.*.free,true', 'required', 'uuid', Rule::exists('licence_variants', 'uuid')->whereNull('deleted_at')],
			'lines.*.title' => ['nullable', 'exclude_unless:lines.*.free,true', 'required', 'string', 'max:255'],
			'lines.*.price' => ['nullable', 'exclude_unless:lines.*.free,true', 'required', 'numeric', 'min:0', 'max:99999999'],
			'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
			'lines.*.host' => ['nullable', 'string', 'max:255'],
		];
	}

	public function messages(): array
	{
		return [
			'lines.required' => 'Bitte mindestens eine Position erfassen.',
			'lines.*.variant.required' => 'Bitte die Software wählen.',
			'lines.*.title.required' => 'Bitte eine Bezeichnung eingeben.',
			'lines.*.price.required' => 'Bitte einen Preis eingeben.',
			'lines.*.price.numeric' => 'Bitte einen Betrag eingeben.',
			'lines.*.quantity.*' => 'Bitte eine Anzahl ab 1 eingeben.',
			'delivery_email.email' => 'Bitte eine gültige E-Mail-Adresse eingeben.',
		];
	}

	public function attributes(): array
	{
		return ['invoice_address' => 'Rechnungsadresse', 'delivery_email' => 'Lizenz an'];
	}

	/** A plugin is ordered for one of its product's hosts, and only a plugin is. */
	public function after(): array
	{
		return [function (Validator $validator): void {
			foreach ((array) $this->input('lines', []) as $index => $line) {
				$variant = empty($line['free']) && filled($line['variant'] ?? null)
					? LicenceVariant::query()->with('product.hosts')->where('uuid', $line['variant'])->first()
					: null;
				$hosts = $variant?->product?->hostNames() ?? [];

				if ($hosts !== [] && ! in_array($line['host'] ?? null, $hosts, true)) {
					$validator->errors()->add("lines.{$index}.host", 'Bitte die Hostsoftware wählen.');
				}
			}
		}];
	}

	/** @return array<int, array{variant: ?LicenceVariant, title: ?string, price: ?string, quantity: int, host: ?string}> */
	public function lines(): array
	{
		$variants = LicenceVariant::query()->with('product.hosts')
			->whereIn('uuid', collect($this->validated('lines'))->pluck('variant')->filter())
			->get()->keyBy('uuid');

		// Sorted: the validated array is built rule by rule, so a line with a
		// `free` key can come out ahead of one before it without.
		return collect($this->validated('lines'))->sortKeys()->values()->map(fn (array $line) => [
			'variant' => filled($line['variant'] ?? null) ? $variants[$line['variant']] : null,
			'title' => $line['title'] ?? null,
			'price' => isset($line['price']) ? (string) $line['price'] : null,
			'quantity' => (int) $line['quantity'],
			'host' => filled($line['variant'] ?? null) && $variants[$line['variant']]->product->hostNames() !== [] ? ($line['host'] ?? null) : null,
		])->all();
	}

	/** @return array<string, string|null>|null */
	public function invoiceAddress(): ?array
	{
		$uuid = $this->validated('invoice_address');

		return $uuid ? UserAddress::query()->where('uuid', $uuid)->firstOrFail()->toSnapshot() : null;
	}
}
