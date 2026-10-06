<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\TeamMemberSchema;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The dashboard's team member form ([[07-dashboard]]), in the shape
 * [[TeamMemberFormResource]] hands out. The role is written as `['de' => …]`,
 * so any English stays where it is.
 */
class SaveTeamMemberRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	/** From [[TeamMemberSchema]], the form's one declaration. */
	public function rules(): array
	{
		return (new TeamMemberSchema)->rules();
	}

	public function attributes(): array
	{
		return (new TeamMemberSchema)->attributes();
	}

	/** @return array<string, mixed> */
	public function teamMemberAttributes(): array
	{
		$data = $this->validated();

		return [
			'name' => $data['name'],
			'role' => ['de' => ($data['role'] ?? '') === '' ? null : $data['role']],
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}
}
