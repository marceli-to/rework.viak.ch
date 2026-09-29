<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\Schema;
use App\Forms\StudentSchema;
use App\Models\User;

/**
 * The dashboard's student form ([[07-dashboard]], step 6), the shape
 * [[StudentFormResource]] hands out: the person and the roles
 * ([[SavePersonRequest]]), and the invoice addresses.
 */
class SaveStudentRequest extends SavePersonRequest
{
	protected function schema(): Schema
	{
		return new StudentSchema;
	}

	protected function person(): ?User
	{
		return $this->route('student');
	}

	/**
	 * The addresses as the form holds them, a saved one by its uuid.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function addresses(): array
	{
		$optional = fn (array $row, string $key) => ($row[$key] ?? '') === '' ? null : $row[$key];

		return collect($this->validated('addresses') ?? [])->map(fn (array $row) => [
			'uuid' => $row['uuid'] ?? null,
			'first_name' => $optional($row, 'first_name'),
			'last_name' => $optional($row, 'last_name'),
			'company' => $optional($row, 'company'),
			'street' => $row['street'],
			'street_no' => $optional($row, 'street_no'),
			'zip' => $row['zip'],
			'city' => $row['city'],
			'country_code' => $row['country'],
		])->all();
	}
}
