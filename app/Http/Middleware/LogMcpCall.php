<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * One line in `storage/logs/mcp.log` per call to the MCP server ([[17-mcp]]):
 * who (the token's owner and the token's name), what (the JSON-RPC method,
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

		Log::channel('mcp')->info('mcp', [
			'user' => $request->user()?->email,
			'token' => $token?->name,
			'token_id' => $token?->id,
			'method' => $request->json('method'),
			'tool' => $request->json('params.name'),
			'arguments' => $request->json('params.arguments'),
			'status' => $response->getStatusCode(),
		]);

		return $response;
	}
}
