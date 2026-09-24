<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Media\AttachMedia;
use App\Actions\Media\CropMedia;
use App\Actions\Media\DeleteMedia;
use App\Actions\Media\ReorderMedia;
use App\Actions\Media\SetMediaRole;
use App\Actions\Media\UploadMedia;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\MediaResource;
use App\Models\Course;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * The dashboard's image section — legacy's `shared/modules/images`, on the
 * media subsystem ported from `forrerzimmermann.ch` ([[07-dashboard]],
 * [[08-accounts]]).
 *
 * **Each action saves at once**, as legacy's does: an image is uploaded,
 * cropped or deleted on its own button, not with the form around it. So the
 * course form never sends its images, and what it loads is still what it saves.
 *
 * What legacy had and this does not: the **publish toggle** — one of legacy's
 * 333 images was ever hidden (checked 2026-09-24), so an image that should not
 * show is deleted; and the **art-directed mobile variant** forrerzimmermann
 * offers, which nothing on this site asks for.
 */
class MediaController extends Controller
{
	public function index(Course $course): AnonymousResourceCollection
	{
		return MediaResource::collection($course->media()->get()->filter->isImage()->values());
	}

	/**
	 * Uploaded and attached in one step: the course exists, so the file goes
	 * straight to it. The first image a course gets becomes its teaser, as a
	 * card needs one.
	 */
	public function store(Request $request, Course $course, UploadMedia $upload, AttachMedia $attach, SetMediaRole $role): MediaResource
	{
		$request->validate([
			'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:16384'],
		], [
			'file.mimes' => 'Nur JPG, PNG oder WebP.',
			'file.max' => 'Höchstens 16 MB.',
		]);

		[$media] = $attach->execute([$upload->execute($request->file('file'))], $course);

		if (! $course->media()->where('is_teaser', true)->exists()) {
			$media = $role->execute($media, 'teaser');
		}

		return new MediaResource($media);
	}

	/** Alt text and caption — legacy's *Bildbeschreibung* and *Bildlegende*. */
	public function update(Request $request, Media $media): MediaResource
	{
		$media->update($request->validate([
			'alt' => ['nullable', 'string', 'max:255'],
			'caption' => ['nullable', 'string', 'max:255'],
		]));

		return new MediaResource($media);
	}

	public function role(Request $request, Media $media, SetMediaRole $role): MediaResource
	{
		$data = $request->validate(['role' => ['required', Rule::in(SetMediaRole::ROLES)]]);

		return new MediaResource($role->execute($media, $data['role']));
	}

	/** In the file's pixels, as Glide takes them; all four null clears it. */
	public function crop(Request $request, Media $media, CropMedia $crop): MediaResource
	{
		$data = $request->validate([
			'x' => ['nullable', 'integer', 'min:0'],
			'y' => ['nullable', 'integer', 'min:0'],
			'w' => ['nullable', 'integer', 'min:1'],
			'h' => ['nullable', 'integer', 'min:1'],
		]);

		return new MediaResource($crop->execute($media, $data));
	}

	public function order(Request $request, Course $course, ReorderMedia $reorder): JsonResponse
	{
		$data = $request->validate(['media' => ['required', 'array'], 'media.*' => ['string']]);

		$reorder->execute($course, $data['media']);

		return response()->json(status: 204);
	}

	public function destroy(Media $media, DeleteMedia $delete): JsonResponse
	{
		$delete->execute($media);

		return response()->json(status: 204);
	}
}
