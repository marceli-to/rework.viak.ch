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
			// Not `closed`: that comes with the attendance, through
			// [[EventPageController::close]], or nobody would get a confirmation.
			'state' => ['required', Rule::enum(EventState::class)->except([EventState::Closed])],
		];
	}

	public function state(): EventState
	{
		return EventState::from($this->string('state')->value());
	}
}
