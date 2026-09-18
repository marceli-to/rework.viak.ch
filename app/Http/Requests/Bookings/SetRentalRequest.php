<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookings;

use Illuminate\Foundation\Http\FormRequest;

class SetRentalRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->can('update', $this->route('booking')) ?? false;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'rental' => ['required', 'boolean'],
		];
	}

	public function rental(): bool
	{
		return $this->boolean('rental');
	}
}
