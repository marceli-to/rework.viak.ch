<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Forms\Schema;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\EditorHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A fixed page's editable copy ([[Page]], `content`), one form per page that
 * has any: the homepage's About teaser so far, edited in *Seiteninhalte →
 * Startseite* beside the page's images ([[HomeAboutSchema]] says what is
 * valid). `uuid` is the page's key, as the image section addresses a page.
 */
class PageContentController extends Controller
{
	public function show(string $page): JsonResponse
	{
		return response()->json(['data' => $this->record(Page::for($page))]);
	}

	public function update(Request $request, string $page): JsonResponse
	{
		$page = Page::for($page);
		$schema = $this->schema($page);

		$data = $request->validate($schema->rules(), $schema->messages(), $schema->attributes());

		$page->update(['content' => collect($schema->defaults())
			->map(fn ($default, string $field) => $this->clean($schema, $field, $data[$field] ?? $default))
			->all()]);

		return response()->json(['data' => $this->record($page)]);
	}

	/** @return array<string, mixed> */
	private function record(Page $page): array
	{
		$this->schema($page);

		return ['uuid' => $page->key, 'label' => $page->label(), ...$page->copy()];
	}

	private function schema(Page $page): Schema
	{
		abort_unless(isset(Page::FORMS[$page->key]), 404);

		return new (Page::FORMS[$page->key]);
	}

	/** Rich text through the editor's sanitiser, as a course's is. */
	private function clean(Schema $schema, string $field, mixed $value): mixed
	{
		// The fields as the dashboard receives them, where each says its type.
		$type = collect(json_decode(json_encode($schema->fields()), true))->firstWhere('name', $field)['type'] ?? null;

		return $type === 'richtext' ? EditorHtml::sanitize($value) : $value;
	}
}
