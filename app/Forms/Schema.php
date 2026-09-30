<?php

declare(strict_types=1);

namespace App\Forms;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\Country;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

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

	/**
	 * *Land*, as every address form offers it: Switzerland first, by the
	 * table's own order. Codes are lowercase (`ch`), and only a listed one passes.
	 *
	 * @return Closure(): array<string, string>
	 */
	protected static function countries(): Closure
	{
		return fn (): array => Country::query()->orderBy('order')->orderBy('name')->get()
			->mapWithKeys(fn (Country $country) => [$country->code => $country->getTranslation('name', 'de')])
			->all();
	}

	/** *Geschlecht*, for the salutation on invoices and confirmations. */
	protected static function genderField(): Field
	{
		return Field::select('gender', collect(Gender::cases())->mapWithKeys(fn (Gender $gender) => [$gender->value => $gender->label()])->all())
			->label('Geschlecht')->required()->with(['placeholder' => 'Bitte wählen']);
	}

	/**
	 * A person's address. Unique across every account, deleted ones too: the
	 * index is, and legacy let two people share one without asking.
	 */
	protected static function emailField(): Field
	{
		return Field::text('email')->label('E-Mail')->required()
			->rules(fn (?Model $user) => ['email', Rule::unique('users', 'email')->ignore($user?->getKey())])
			->message('unique', 'Diese E-Mail-Adresse gehört bereits zu einem Konto.')
			->with(['input' => 'email']);
	}

	/**
	 * *Benutzer-Rollen* — on the person, wherever the person is edited: the
	 * expert form and the customer form. Legacy's expert form was the only place
	 * anyone could be made an admin. The request stops an admin taking their
	 * own Admin role away. Legacy's always-open collapsible around three boxes
	 * is a plain group, four to a row as its `span-3`.
	 *
	 * Admin and Experte only: there is no customer role (`12-customers.md`).
	 * **Required on the expert form, optional on the customer's**, where
	 * none ticked is a customer and nothing more.
	 */
	protected static function roleField(bool $required = true): Field
	{
		$field = Field::checkboxes('roles', fn () => [
			Role::Admin->value => 'Admin',
			Role::Expert->value => 'Experte',
		])->label('Benutzer-Rollen')->with(['columns' => 4]);

		return $required
			? $field->required()
				->message('required', 'Bitte mindestens eine Rolle wählen.')
				->message('min', 'Bitte mindestens eine Rolle wählen.')
			: $field;
	}

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
