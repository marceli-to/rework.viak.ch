<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\EventState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetEventStateRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->can('setState', $this->route('event')) ?? false;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'state' => ['required', Rule::enum(EventState::class)],
		];
	}

	public function state(): EventState
	{
		return EventState::from($this->string('state')->value());
	}
}
