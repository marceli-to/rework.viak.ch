<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\Schema;
use App\Forms\SoftwareAboutPageSchema;
use App\Forms\SoftwareLicencesPageSchema;
use App\Forms\SoftwareOfferPageSchema;
use App\Support\EditorHtml;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One of a software's pages, saved from its own form (Marcel, 2026-11-17,
 * [[14-angebote]] *The form, by page*): `PUT software/{software}/seite/{page}`.
 * Writes that page's fields and nothing else, German only (`['de' => …]`),
 * so any English stays where it is.
 */
class SaveSoftwarePageRequest extends FormRequest
{
	/** @var array<string, class-string<Schema>> the path's `{page}` => its form */
	public const PAGES = [
		'angebot' => SoftwareOfferPageSchema::class,
		'was-ist' => SoftwareAboutPageSchema::class,
		'lizenzen' => SoftwareLicencesPageSchema::class,
	];

	/** The editor's fields, cleaned before they are stored ([[EditorHtml]]). */
	public const RICH = ['short_description', 'full_description', 'requirements'];

	/** Written as they come; every other text is trimmed. Empty is null either way. */
	private const RAW = ['seo_description', 'seo_tags'];

	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	public function schema(): Schema
	{
		return new (self::PAGES[$this->route('page')]);
	}

	public function rules(): array
	{
		return $this->schema()->rules();
	}

	public function messages(): array
	{
		return $this->schema()->messages();
	}

	public function attributes(): array
	{
		return $this->schema()->attributes();
	}

	/** @return array<string, array{de: ?string}> the page's fields, and only those */
	public function pageAttributes(): array
	{
		$data = $this->validated();

		return collect(array_keys($this->schema()->defaults()))->mapWithKeys(function (string $field) use ($data) {
			$value = $data[$field] ?? null;
			$value = match (true) {
				in_array($field, self::RICH, true) => EditorHtml::sanitize($value),
				in_array($field, self::RAW, true) => $value,
				default => isset($value) ? trim($value) : null,
			};

			return [$field => ['de' => blank($value) ? null : $value]];
		})->all();
	}
}
