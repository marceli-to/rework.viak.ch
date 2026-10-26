<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Support\Mcp\McpAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * *MCP-Zugang* on *Mein Profil* ([[17-mcp]]): the signed-in admin's own
 * tokens for the MCP server, and nobody else's. One per AI client is the idea
 * (*Claude, Laptop Büro*), so one can be revoked without the others.
 *
 * The token itself is shown **once**, in the answer to creating it; Sanctum
 * keeps only its hash. Lost means revoke and create another.
 */
class McpTokenController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$tokens = $request->user()->tokens()->latest('id')->get();

		return response()->json(['data' => $tokens->map($this->row(...))->values()]);
	}

	public function store(Request $request): JsonResponse
	{
		$data = $request->validate(['name' => ['required', 'string', 'max:100']], [], ['name' => 'Bezeichnung']);

		$created = $request->user()->createToken($data['name'], [McpAccess::ABILITY]);

		return response()->json([
			'data' => $this->row($created->accessToken),
			'token' => $created->plainTextToken,
		], 201);
	}

	public function destroy(Request $request, int $token): Response
	{
		$request->user()->tokens()->whereKey($token)->firstOrFail()->delete();

		return response()->noContent();
	}

	private function row(PersonalAccessToken $token): array
	{
		return [
			'id' => $token->id,
			'name' => $token->name,
			'created_at' => $token->created_at?->format('d.m.Y'),
			'last_used_at' => $token->last_used_at?->format('d.m.Y H:i'),
		];
	}
}
