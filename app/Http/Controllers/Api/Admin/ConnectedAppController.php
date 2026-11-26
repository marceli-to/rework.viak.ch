<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * *Verbundene Apps* on *Mein Profil* ([[17-mcp]]): the signed-in admin's own
 * OAuth connections, in practice the claude.ai connector. A connection is a
 * client the admin still holds a live token for: an unexpired access token, or
 * an unexpired refresh token claude.ai can renew one with. Trennen revokes
 * both, so the next use asks for consent again.
 */
class ConnectedAppController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$apps = $this->liveTokens($request)->with('client')->get()
			->groupBy('client_id')
			// Access tokens are renewed hourly while in use, so the newest is the last activity.
			->sortByDesc(fn (Collection $tokens) => $tokens->max('created_at'))
			->map(fn (Collection $tokens) => [
				'id' => $tokens->first()->client_id,
				'name' => $tokens->first()->client?->name ?? 'KI-Programm',
				'connected_at' => $tokens->min('created_at')?->format('d.m.Y'),
				'last_used_at' => $tokens->max('created_at')?->format('d.m.Y H:i'),
			])
			->values();

		return response()->json(['data' => $apps]);
	}

	public function destroy(Request $request, string $client): Response
	{
		$ids = Token::query()
			->where('user_id', $request->user()->id)
			->where('client_id', $client)
			->where('revoked', false)
			->pluck('id');

		abort_if($ids->isEmpty(), 404);

		Token::query()->whereIn('id', $ids)->update(['revoked' => true]);
		RefreshToken::query()->whereIn('access_token_id', $ids)->update(['revoked' => true]);

		return response()->noContent();
	}

	/** @return Builder<Token> */
	private function liveTokens(Request $request): Builder
	{
		return Token::query()
			->where('user_id', $request->user()->id)
			->where('revoked', false)
			->where(fn (Builder $query) => $query
				->where('expires_at', '>', now())
				->orWhereExists(fn ($sub) => $sub->from('oauth_refresh_tokens')
					->whereColumn('oauth_refresh_tokens.access_token_id', 'oauth_access_tokens.id')
					->where('oauth_refresh_tokens.revoked', false)
					->where('oauth_refresh_tokens.expires_at', '>', now())));
	}
}
