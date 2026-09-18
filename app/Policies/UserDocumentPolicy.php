<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserDocument;

/**
 * A document belongs to the customer it was issued to ([[08-accounts]]).
 *
 * The whole reason documents moved off the public disk. Experts are *not*
 * included: teaching a course does not entitle anyone to its students'
 * invoices.
 */
class UserDocumentPolicy
{
	public function view(User $user, UserDocument $document): bool
	{
		return $user->id === $document->user_id || $user->isAdmin();
	}
}
