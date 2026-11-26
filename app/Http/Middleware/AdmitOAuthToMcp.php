<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Mcp\McpAccess;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * The OAuth half of the MCP server's door ([[17-mcp]]).
 *
 * `/mcp` takes two kinds of bearer token. A personal token (Sanctum, Claude
 * Code) is checked before Sanctum accepts it ([[McpAccess::admits]]). An OAuth
 * token (Passport, the claude.ai connector) is checked here, after Passport
 * has resolved its user: it must carry the `mcp:use` scope, and its owner must
 * still be an admin whose account is not deactivated. Anyone can *sign in*
 * through the connector; only the office gets past this.
 */
class AdmitOAuthToMcp
{
	public function handle(Request $request, Closure $next): Response
	{
		$user = $request->user();
		$token = $user?->currentAccessToken();

		if ($token instanceof AccessToken) {
			abort_unless(McpAccess::admitsOAuth($token, $user), Response::HTTP_FORBIDDEN);
		}

		return $next($request);
	}
}
