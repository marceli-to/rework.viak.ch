<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ParticipantThreshold;
use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A course changed participant band ([[ParticipantThreshold]]).
 *
 * Carries both bands, because what VIAK is told depends on the direction: a
 * course reaching its minimum is good news, and one dropping back below it is a
 * decision to make.
 *
 * The mail itself belongs to the notifications chunk. This exists now so the
 * *detection* — the part legacy got wrong, and the part worth testing — is built
 * and correct before anything renders a template against it.
 */
class ParticipantThresholdCrossed
{
	use Dispatchable;

	public function __construct(
		public readonly Event $event,
		public readonly ParticipantThreshold $from,
		public readonly ParticipantThreshold $to,
	) {}

	/** Has the course just become viable, or just stopped being? */
	public function becameViable(): bool
	{
		return $this->from === ParticipantThreshold::BelowMinimum
			&& $this->to !== ParticipantThreshold::BelowMinimum;
	}

	public function fellBelowMinimum(): bool
	{
		return $this->to === ParticipantThreshold::BelowMinimum
			&& $this->from !== ParticipantThreshold::BelowMinimum;
	}

	public function becameFull(): bool
	{
		return $this->to === ParticipantThreshold::Full;
	}
}
