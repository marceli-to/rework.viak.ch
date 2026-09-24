<?php

declare(strict_types=1);

namespace App\Forms;

use Illuminate\Database\Eloquent\Model;

/**
 * A dashboard form, declared once ([[Field]], [[07-dashboard]]).
 *
 * The request validates with `rules()`, `messages()` and `attributes()`; the
 * dashboard draws `toArray()`. What a form *does* with the values — German
 * written as `['de' => …]`, HTML cleaned, relations synced — stays in its
 * request and actions, because that genuinely differs from form to form.
 */
abstract class Schema
{
	/** @return array<int, Field> */
	abstract public function fields(): array;

	/**
	 * A new record's starting values — every field the form sends, so the
	 * form's shape is the same on create as on edit.
	 *
	 * @return array<string, mixed>
	 */
	abstract public function defaults(): array;

	/** @return array<string, array<int, mixed>> */
	public function rules(?Model $record = null): array
	{
		return collect($this->fields())->flatMap(fn (Field $field) => $field->validation($record))->all();
	}

	/** @return array<string, string> */
	public function messages(): array
	{
		return array_merge(...array_map(fn (Field $field) => $field->messages(), $this->fields()));
	}

	/** @return array<string, string> */
	public function attributes(): array
	{
		return array_merge(...array_map(fn (Field $field) => $field->attributes(), $this->fields()));
	}

	/** @return array{fields: array<int, Field>, defaults: array<string, mixed>} */
	public function toArray(): array
	{
		return ['fields' => $this->fields(), 'defaults' => $this->defaults()];
	}
}
