<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A team member as the dashboard's form and list hold it — the shape
 * [[SaveTeamMemberRequest]] takes back ([[07-dashboard]]).
 *
 * @mixin TeamMember
 */
class TeamMemberFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'name' => $this->name,
			'role' => $this->getTranslation('role', 'de', false) ?: '',
			'publish' => $this->publish,
		];
	}
}
