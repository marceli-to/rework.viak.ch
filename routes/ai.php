<?php

declare(strict_types=1);

use App\Http\Middleware\LogMcpCall;
use App\Mcp\Servers\ViakServer;
use App\Support\Mcp\McpAccess;
use Laravel\Mcp\Facades\Mcp;

/*
 * The MCP server ([[17-mcp]]). A bearer token from *Mein Profil*, an admin's,
 * with the `mcp` ability, is the only way in ([[McpAccess]]); no session, no
 * cookie. Sixty calls a minute per account is far more than an assistant
 * answering one question needs, and far less than a loop gone wrong would use.
 */
Mcp::web('/mcp', ViakServer::class)
	->name(McpAccess::ROUTE)
	->middleware(['auth:sanctum', LogMcpCall::class, 'throttle:60,1']);
