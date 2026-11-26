<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\AccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * One line in `storage/logs/mcp.log` per call to the MCP server ([[17-mcp]]):
 * who (the token's owner and the token's name, or `oauth` and the client id
 * for the claude.ai connector), what (the JSON-RPC method,
 * and for a tool call the tool and its arguments), and the HTTP status.
 *
 * The arguments are logged, the answer is not: the answer is customer data,
 * and a log of it would be a second copy to protect. A year of files.
 */
class LogMcpCall
{
	public function handle(Request $request, Closure $next): Response
	{
		$response = $next($request);

		$token = $request->user()?->currentAccessToken();
		$oauth = $token instanceof AccessToken;

		Log::channel('mcp')->info('mcp', [
			'user' => $request->user()?->email,
			// A personal token by its name; an OAuth token by its client (claude.ai's registration).
			'token' => $oauth ? 'oauth' : $token?->name,
			'token_id' => $oauth ? $token->oauth_client_id : $token?->id,
			'method' => $request->json('method'),
			'tool' => $request->json('params.name'),
			'arguments' => $request->json('params.arguments'),
			'status' => $response->getStatusCode(),
		]);

		return $response;
	}
}
