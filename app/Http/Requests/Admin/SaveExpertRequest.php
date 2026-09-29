<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Forms\ExpertSchema;
use App\Forms\Schema;
use App\Models\User;
use App\Support\EditorHtml;

/**
 * The dashboard's expert form ([[07-dashboard]], step 6), the shape
 * [[ExpertFormResource]] hands out: the person and the roles
 * ([[SavePersonRequest]]), the bio and the two flags on `expert_profiles`.
 */
class SaveExpertRequest extends SavePersonRequest
{
	protected function schema(): Schema
	{
		return new ExpertSchema;
	}

	protected function person(): ?User
	{
		return $this->route('expert');
	}

	/** @return array<string, mixed> */
	public function profileAttributes(): array
	{
		$data = $this->validated();

		return [
			'title' => ($data['title'] ?? '') === '' ? null : $data['title'],
			'description' => EditorHtml::sanitize($data['description'] ?? null),
			'visible' => (bool) ($data['visible'] ?? false),
			'publish' => (bool) ($data['publish'] ?? false),
		];
	}
}
