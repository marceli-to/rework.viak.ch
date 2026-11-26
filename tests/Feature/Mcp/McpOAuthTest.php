<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Mcp\McpAccess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * OAuth for the MCP server's claude.ai connector ([[17-mcp]]): discovery,
 * registration limited to claude.ai, the code + PKCE round trip, the gate
 * ([[AdmitOAuthToMcp]]: active admins only), the consent screen, and
 * *Verbundene Apps* ([[ConnectedAppController]]).
 */
const CLAUDE_CALLBACK = 'https://claude.ai/api/mcp/auth_callback';

beforeEach(function () {
	if (! file_exists(storage_path('oauth-private.key'))) {
		Artisan::call('passport:keys');
	}
});

function oauthMcp(array $headers = [], string $method = 'tools/list', array $params = []): TestResponse
{
	return test()->withHeaders(['Accept' => 'application/json, text/event-stream', ...$headers])
		->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
}

/** Registers a client the way claude.ai does (dynamic client registration). */
function registerClaude(): string
{
	return test()->postJson('/oauth/register', [
		'client_name' => 'Claude',
		'redirect_uris' => [CLAUDE_CALLBACK],
	])->assertCreated()->json('client_id');
}

function authorizeQuery(string $clientId, string $challenge): string
{
	return http_build_query([
		'client_id' => $clientId,
		'redirect_uri' => CLAUDE_CALLBACK,
		'response_type' => 'code',
		'scope' => 'mcp:use',
		'state' => 'xyz',
		'code_challenge' => $challenge,
		'code_challenge_method' => 'S256',
	]);
}

it('publishes the OAuth metadata and points a 401 at it', function () {
	$this->getJson('/.well-known/oauth-protected-resource/mcp')
		->assertOk()
		->assertJsonPath('resource', url('/mcp'));

	$this->getJson('/.well-known/oauth-authorization-server')
		->assertOk()
		->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'))
		->assertJsonPath('registration_endpoint', route('mcp.oauth.register'))
		->assertJsonPath('code_challenge_methods_supported', ['S256']);

	$header = oauthMcp()->assertUnauthorized()->headers->get('WWW-Authenticate');
	expect($header)->toContain('resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp').'"');
});

it('registers claude.ai clients and no other redirect domain', function () {
	registerClaude();
	$this->postJson('/oauth/register', ['redirect_uris' => ['https://claude.com/api/mcp/auth_callback']])->assertCreated();

	$this->postJson('/oauth/register', ['redirect_uris' => ['https://evil.example/callback']])
		->assertStatus(400)
		->assertJsonPath('error', 'invalid_redirect_uri');
	$this->postJson('/oauth/register', ['redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect']])
		->assertStatus(400);
});

it('runs the full code + PKCE flow, calls a tool with the token, and refreshes it', function () {
	$admin = User::factory()->admin()->create();
	$clientId = registerClaude();
	$verifier = Str::random(64);
	$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
	$query = authorizeQuery($clientId, $challenge);

	// Signed out: Passport sends the browser to the login page first.
	$this->get('/oauth/authorize?'.$query)->assertRedirect(route('login'));

	$this->actingAs($admin);
	$consent = $this->get('/oauth/authorize?'.$query)->assertOk()->assertSee('Zugriff erlauben');
	preg_match('/name="auth_token" value="([^"]+)"/', $consent->getContent(), $match);

	$redirect = $this->post('/oauth/authorize', ['state' => 'xyz', 'client_id' => $clientId, 'auth_token' => $match[1]])
		->assertRedirect()
		->headers->get('Location');
	expect($redirect)->toStartWith(CLAUDE_CALLBACK);
	parse_str((string) parse_url($redirect, PHP_URL_QUERY), $params);

	$tokens = $this->postJson('/oauth/token', [
		'grant_type' => 'authorization_code',
		'client_id' => $clientId,
		'redirect_uri' => CLAUDE_CALLBACK,
		'code_verifier' => $verifier,
		'code' => $params['code'],
	])->assertOk()->json();
	expect($tokens)->toHaveKeys(['access_token', 'refresh_token']);

	// A fresh app, as for claude.ai's own request: no session.
	auth()->forgetGuards();
	$this->app['session']->flush();

	oauthMcp(['Authorization' => 'Bearer '.$tokens['access_token']], 'tools/call', [
		'name' => 'open-invoices',
		'arguments' => [],
	])->assertOk()->assertJsonPath('result.isError', false);

	// The same token opens nothing but /mcp.
	$this->withToken($tokens['access_token'])->getJson('/api/admin/courses')->assertUnauthorized();
	$this->withToken($tokens['access_token'])->getJson('/api/profile')->assertUnauthorized();
	$this->flushHeaders();

	$this->postJson('/oauth/token', [
		'grant_type' => 'refresh_token',
		'client_id' => $clientId,
		'refresh_token' => $tokens['refresh_token'],
	])->assertOk()->assertJsonStructure(['access_token', 'refresh_token']);
});

it('lets an admin in with an OAuth token', function () {
	Passport::actingAs(User::factory()->admin()->create(), ['mcp:use']);

	oauthMcp()->assertOk()->assertJsonCount(4, 'result.tools');
});

it('refuses an OAuth token of a customer, of a deactivated admin, or without the mcp scope', function (string $case) {
	match ($case) {
		'customer' => Passport::actingAs(User::factory()->create(), ['mcp:use']),
		'deactivated' => Passport::actingAs(User::factory()->admin()->create(['deactivated_at' => now()]), ['mcp:use']),
		'no scope' => Passport::actingAs(User::factory()->admin()->create(), []),
	};

	oauthMcp()->assertForbidden();
})->with(['customer', 'deactivated', 'no scope']);

it('does not let an OAuth token into the rest of the API', function () {
	// Passport's guard holds the admin, as for a real OAuth token; the API asks Sanctum only.
	Passport::actingAs(User::factory()->admin()->create(), ['mcp:use']);

	$this->getJson('/api/admin/courses')->assertUnauthorized();
	$this->getJson('/api/admin/profile/connected-apps')->assertUnauthorized();
});

it('tells anyone but an admin on the consent screen that connecting is for the team', function () {
	$clientId = registerClaude();
	$this->actingAs(User::factory()->create());

	$this->get('/oauth/authorize?'.authorizeQuery($clientId, str_repeat('a', 43)))
		->assertOk()
		->assertSee('Nicht verfügbar')
		->assertDontSee('Zugriff erlauben');
});

it('keeps personal MCP tokens working next to OAuth', function () {
	$token = User::factory()->admin()->create()->createToken('Claude Code', [McpAccess::ABILITY])->plainTextToken;

	oauthMcp(['Authorization' => "Bearer {$token}"])->assertOk();
});

/** A live claude.ai connection: an expired access token, renewable through its refresh token. */
function connectClaude(User $user, string $name = 'Claude'): Client
{
	$client = app(ClientRepository::class)->createAuthorizationCodeGrantClient($name, [CLAUDE_CALLBACK], confidential: false);
	$token = Token::forceCreate([
		'id' => Str::random(80), 'user_id' => $user->id, 'client_id' => $client->id,
		'scopes' => ['mcp:use'], 'revoked' => false, 'expires_at' => now()->subMinute(),
	]);
	RefreshToken::forceCreate([
		'id' => Str::random(80), 'access_token_id' => $token->id,
		'revoked' => false, 'expires_at' => now()->addDays(30),
	]);

	return $client;
}

it('lists and disconnects the admin\'s own connected apps, and only theirs', function () {
	$admin = User::factory()->admin()->create();
	$client = connectClaude($admin);
	$other = connectClaude(User::factory()->admin()->create(), 'Jemand anders');

	$this->actingAs($admin)->getJson('/api/admin/profile/connected-apps')
		->assertOk()
		->assertJsonCount(1, 'data')
		->assertJsonPath('data.0.name', 'Claude');

	$this->deleteJson("/api/admin/profile/connected-apps/{$other->id}")->assertNotFound();
	$this->deleteJson("/api/admin/profile/connected-apps/{$client->id}")->assertNoContent();

	$this->getJson('/api/admin/profile/connected-apps')->assertOk()->assertJsonCount(0, 'data');
	expect(RefreshToken::where('revoked', false)->count())->toBe(1);
});

it('keeps connected apps to admins', function () {
	$this->actingAs(User::factory()->create())->getJson('/api/admin/profile/connected-apps')->assertForbidden();
});
