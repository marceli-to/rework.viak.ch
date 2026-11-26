<?php

declare(strict_types=1);

use App\Http\Middleware\AdmitOAuthToMcp;
use App\Http\Middleware\LogMcpCall;
use App\Mcp\Servers\ViakServer;
use App\Support\Mcp\McpAccess;
use Laravel\Mcp\Facades\Mcp;

/*
 * The MCP server ([[17-mcp]]). Two kinds of bearer token, never a session or a
 * cookie: a personal token from *Mein Profil* with the `mcp` ability (Claude
 * Code; [[McpAccess]]), or an OAuth token from Passport with `mcp:use` (the
 * claude.ai connector; [[AdmitOAuthToMcp]]). Either way an active admin's. Sixty calls a minute per account is far more than an assistant
 * answering one question needs, and far less than a loop gone wrong would use.
 */
Mcp::web('/mcp', ViakServer::class)
	->name(McpAccess::ROUTE)
	->middleware(['auth:sanctum,api', AdmitOAuthToMcp::class, LogMcpCall::class, 'throttle:60,1']);

/*
 * OAuth discovery (`/.well-known/oauth-protected-resource`, `-authorization-server`)
 * and dynamic client registration (`POST /oauth/register`, claude.ai's addresses
 * only: `config/mcp.php`). Passport serves `/oauth/authorize` and `/oauth/token`;
 * a 401 on `/mcp` points at the metadata through `WWW-Authenticate`.
 */
Mcp::oauthRoutes();
