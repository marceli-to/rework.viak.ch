<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Media\DeleteMedia;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveExpertRequest;
use App\Http\Resources\Admin\ExpertFormResource;
use App\Http\Resources\Admin\ExpertRowResource;
use App\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * *Experten* ([[07-dashboard]], step 6) — legacy's `Dashboard/ExpertController`.
 *
 * An expert is anyone holding the Expert role; `{expert}` binds nobody else.
 * Twenty today, so the list is whole and searched in the browser, as *Kurse*.
 */
class ExpertController extends Controller
{
	/** In the Experten page's order; the list splits them into active and not. */
	public function index(): AnonymousResourceCollection
	{
		$experts = User::query()
			->withRole(Role::Expert)
			->with('expertProfile')
			->get()
			->sortBy([
				fn (User $a, User $b) => ($a->expertProfile->order ?? PHP_INT_MAX) <=> ($b->expertProfile->order ?? PHP_INT_MAX),
				fn (User $a, User $b) => strnatcasecmp($a->name, $b->name),
			])
			->values();

		return ExpertRowResource::collection($experts);
	}

	public function show(User $expert): ExpertFormResource
	{
		return new ExpertFormResource($expert->load('expertProfile'));
	}

	/** Invited to set their own password ([[CreateAccount]]). */
	public function store(SaveExpertRequest $request, CreateAccount $create): JsonResponse
	{
		$expert = DB::transaction(function () use ($request, $create): User {
			$expert = $create->execute($request->userAttributes(), $request->roles());

			// The end of the Experten page, as a new testimonial goes to the end of its list.
			$expert->expertProfile()->create([
				...$request->profileAttributes(),
				'order' => (int) ExpertProfile::max('order') + 1,
			]);

			return $expert;
		});

		return (new ExpertFormResource($expert->load('expertProfile')))->response()->setStatusCode(201);
	}

	/**
	 * An address the admin changes stays verified, as legacy has it: the
	 * admin is not an unproven session, which is what the portal's own
	 * e-mail change guards against ([[UpdateProfileRequest]]).
	 */
	public function update(SaveExpertRequest $request, User $expert): ExpertFormResource
	{
		DB::transaction(function () use ($request, $expert): void {
			$expert->update($request->userAttributes());
			$expert->expertProfile()->updateOrCreate([], $request->profileAttributes());
			$expert->syncRoles($request->roles());
		});

		return new ExpertFormResource($expert->load('expertProfile'));
	}

	/** Legacy's drag on *Aktive Experten*; the list arrives whole, so 1…n. */
	public function order(Request $request): JsonResponse
	{
		$uuids = $request->validate([
			'experts' => ['required', 'array'],
			'experts.*' => ['string', Rule::exists('users', 'uuid')],
		])['experts'];

		DB::transaction(function () use ($uuids): void {
			foreach (array_values($uuids) as $position => $uuid) {
				$user = User::query()->withRole(Role::Expert)->where('uuid', $uuid)->first();
				$user?->expertProfile()->updateOrCreate([], ['order' => $position + 1]);
			}
		});

		return response()->json(status: 204);
	}

	/**
	 * **Only someone nothing points at** — four of today's twenty. Anyone who
	 * has taught a date, booked, or been invoiced is deactivated instead (#16):
	 * *Experte aktiv* off, or the role taken away. Legacy soft-deleted, which
	 * kept the address taken; this removes the account, portraits and all, so
	 * the address can be used again.
	 */
	public function destroy(Request $request, User $expert, DeleteMedia $delete): JsonResponse
	{
		abort_if($expert->is($request->user()), 422, 'Du kannst dein eigenes Konto nicht löschen.');
		abort_if($expert->hasHistory(), 422, 'Diese Person hat Kursdaten, Buchungen oder Rechnungen und kann nicht gelöscht werden.');

		DB::transaction(function () use ($expert, $delete): void {
			$expert->media->each(fn ($media) => $delete->execute($media));
			$expert->forceDelete();
		});

		return response()->json(status: 204);
	}
}
