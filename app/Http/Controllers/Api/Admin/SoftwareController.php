<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSoftwareRequest;
use App\Http\Resources\Admin\SoftwareFormResource;
use App\Models\Software;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * A software's page ([[05-licences]]): the form behind the pencil in the
 * *Software* list on the dashboard's *Software* page. The list itself, and
 * the `+` beside the product form's select, stay on [[SettingController]],
 * which only knows a name; the slug is made either way ([[Software]]).
 */
class SoftwareController extends Controller
{
	public function show(Software $software): SoftwareFormResource
	{
		return new SoftwareFormResource($software->load('categories'));
	}

	/** A new one goes to the end of the list, as the settings list adds it. */
	public function store(SaveSoftwareRequest $request): JsonResponse
	{
		$software = DB::transaction(function () use ($request): Software {
			$software = Software::create([
				...$request->softwareAttributes(),
				'order' => (int) Software::max('order') + 1,
			]);
			$software->categories()->sync($request->categoryIds());

			return $software;
		});

		return (new SoftwareFormResource($software->load('categories')))->response()->setStatusCode(201);
	}

	public function update(SaveSoftwareRequest $request, Software $software): SoftwareFormResource
	{
		DB::transaction(function () use ($request, $software): void {
			$software->update($request->softwareAttributes());
			$software->categories()->sync($request->categoryIds());
		});

		return new SoftwareFormResource($software->refresh()->load('categories'));
	}

	/** Soft-deleted, and only while no course and no product uses it, as the settings list has it. */
	public function destroy(Software $software): JsonResponse
	{
		abort_if($software->courses()->exists() || $software->products()->exists() || $software->hostOf()->exists(), 422, 'Wird noch verwendet und kann nicht gelöscht werden.');

		$software->delete();

		return response()->json(status: 204);
	}
}
