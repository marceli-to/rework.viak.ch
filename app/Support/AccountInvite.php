<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * The link in *Dein VIAK-Zugang* that lets a person an admin created set their
 * own password ([[InviteController]], [[10-mail]]).
 *
 * **Signed, for 72 hours, and good once.** Legacy's equivalent (`/expert/finish`)
 * took a token and an address from the form and trusted them, which is the
 * account-takeover path `Todo.md` records. Here the signature covers the
 * account and a fingerprint of its current password hash: setting a password
 * changes the hash, and the link stops working.
 */
final class AccountInvite
{
	public const HOURS = 72;

	public static function url(User $user): string
	{
		return URL::temporarySignedRoute('invite.show', now()->addHours(self::HOURS), [
			'uuid' => $user->uuid,
			'check' => self::fingerprint($user),
		]);
	}

	/** Changes whenever the password does. */
	public static function fingerprint(User $user): string
	{
		return substr(hash_hmac('sha256', (string) $user->password, (string) config('app.key')), 0, 16);
	}
}
