<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Media\DeleteMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTeamMemberRequest;
use App\Http\Resources\Admin\TeamMemberFormResource;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * *Seiteninhalte → Team* ([[07-dashboard]]) — legacy's `team_member` screens,
 * back for the *Über uns* page. A handful of rows, so the list is whole, and
 * **dragged into the order the page shows**, as legacy's was.
 */
class TeamMemberController extends Controller
{
	public function index(): AnonymousResourceCollection
	{
		return TeamMemberFormResource::collection(TeamMember::query()->ordered()->get());
	}

	public function show(TeamMember $teamMember): TeamMemberFormResource
	{
		return new TeamMemberFormResource($teamMember);
	}

	/** A new one goes to the end of the list. */
	public function store(SaveTeamMemberRequest $request): JsonResponse
	{
		$teamMember = TeamMember::create([
			...$request->teamMemberAttributes(),
			'order' => (int) TeamMember::max('order') + 1,
		]);

		return (new TeamMemberFormResource($teamMember))->response()->setStatusCode(201);
	}

	public function update(SaveTeamMemberRequest $request, TeamMember $teamMember): TeamMemberFormResource
	{
		$teamMember->update($request->teamMemberAttributes());

		return new TeamMemberFormResource($teamMember);
	}

	public function order(Request $request): JsonResponse
	{
		$uuids = $request->validate([
			'team_members' => ['required', 'array'],
			'team_members.*' => ['string', Rule::exists('team_members', 'uuid')],
		])['team_members'];

		DB::transaction(function () use ($uuids): void {
			foreach (array_values($uuids) as $position => $uuid) {
				TeamMember::query()->where('uuid', $uuid)->update(['order' => $position + 1]);
			}
		});

		return response()->json(status: 204);
	}

	/** The portrait goes with them, file and all. */
	public function destroy(TeamMember $teamMember, DeleteMedia $delete): JsonResponse
	{
		DB::transaction(function () use ($teamMember, $delete): void {
			$teamMember->media->each(fn ($media) => $delete->execute($media));
			$teamMember->delete();
		});

		return response()->json(status: 204);
	}
}
