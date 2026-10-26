<?php

declare(strict_types=1);

namespace App\Support\Mcp;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Who may use a personal access token, and where ([[17-mcp]]).
 *
 * **A token opens the MCP endpoint and nothing else.** `auth:sanctum` guards
 * the whole `/api`, the dashboard's writes included, and Sanctum accepts any
 * valid bearer token there. Until the MCP server, the app issued no tokens, so
 * that never mattered; a token handed to an AI client must not become a key
 * to *Rechnung stornieren*. So Sanctum asks here before it takes one
 * (`Sanctum::authenticateAccessTokensUsing`, in [[AppServiceProvider]]), and
 * the answer is yes only for:
 *
 * - the `mcp` route;
 * - a token carrying the `mcp` ability (every token the dashboard issues);
 * - an admin whose account is not deactivated. Losing the role, or the
 *   account, shuts the token without anyone having to revoke it.
 *
 * The dashboard's own session is untouched: it never goes through a token.
 */
class McpAccess
{
	public const ABILITY = 'mcp';

	public const ROUTE = 'mcp';

	public static function admits(PersonalAccessToken $token, bool $isValid, Request $request): bool
	{
		if (! $isValid || ! $request->routeIs(self::ROUTE) || ! $token->can(self::ABILITY)) {
			return false;
		}

		$user = $token->tokenable;

		return $user instanceof User && $user->isAdmin() && ! $user->isDeactivated();
	}
}
