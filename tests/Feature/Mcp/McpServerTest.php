<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Mcp\Servers\ViakServer;
use App\Mcp\Tools\EventBookings;
use App\Mcp\Tools\OpenInvoices;
use App\Mcp\Tools\UpcomingEvents;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Mcp\McpAccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Psr\Log\LoggerInterface;

/**
 * The MCP server ([[17-mcp]]): who gets in ([[McpAccess]]), the admin's own
 * tokens ([[McpTokenController]]), and what the read-only tools answer.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create();
});

function mcpList(string $token): TestResponse
{
	return test()->withToken($token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
}

it('lists the four read-only tools to an admin token', function () {
	$token = $this->admin->createToken('Claude', [McpAccess::ABILITY])->plainTextToken;

	$tools = collect(mcpList($token)->assertOk()->json('result.tools'));

	expect($tools->pluck('name')->sort()->values()->all())
		->toBe(['event-bookings', 'open-invoices', 'outstanding-licence-orders', 'upcoming-events'])
		->and($tools->every(fn (array $tool) => $tool['annotations']['readOnlyHint'] ?? false))->toBeTrue();
});

it('refuses no token, a token without the ability, and a token whose owner is no longer an admin', function () {
	$this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();

	mcpList($this->admin->createToken('ohne', [])->plainTextToken)->assertUnauthorized();

	$customer = User::factory()->create();
	mcpList($customer->createToken('Kunde', [McpAccess::ABILITY])->plainTextToken)->assertUnauthorized();

	$token = $this->admin->createToken('Claude', [McpAccess::ABILITY])->plainTextToken;
	$this->admin->forceFill(['deactivated_at' => now()])->save();
	mcpList($token)->assertUnauthorized();
});

it('does not let an MCP token into the rest of the API', function () {
	$token = $this->admin->createToken('Claude', [McpAccess::ABILITY])->plainTextToken;

	$this->withToken($token)->getJson('/api/admin/courses')->assertUnauthorized();
	$this->withToken($token)->getJson('/api/profile')->assertUnauthorized();
});

it('lets an admin create, list and revoke their own tokens, and only theirs', function () {
	$this->actingAs($this->admin);

	$created = $this->postJson('/api/admin/profile/mcp-tokens', ['name' => 'Claude, Laptop Büro'])->assertCreated();

	expect($created->json('token'))->toContain('|')
		->and($this->getJson('/api/admin/profile/mcp-tokens')->json('data.0.name'))->toBe('Claude, Laptop Büro')
		->and($this->admin->tokens()->first()->can(McpAccess::ABILITY))->toBeTrue();

	$other = User::factory()->admin()->create()->createToken('fremd', [McpAccess::ABILITY])->accessToken;
	$this->deleteJson("/api/admin/profile/mcp-tokens/{$other->id}")->assertNotFound();

	$this->deleteJson('/api/admin/profile/mcp-tokens/'.$created->json('data.id'))->assertNoContent();
	expect($this->admin->tokens()->count())->toBe(0);
});

it('keeps token management to admins and asks for a name', function () {
	$this->actingAs(User::factory()->create())->getJson('/api/admin/profile/mcp-tokens')->assertForbidden();

	$this->actingAs($this->admin)->postJson('/api/admin/profile/mcp-tokens', ['name' => ''])->assertJsonValidationErrors('name');
});

it('lists upcoming Veranstaltungen with seats taken, without the cancelled or past', function () {
	$course = Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs']]);
	$event = Event::factory()->for($course)->create(['date' => today()->addDays(10)]);
	Booking::factory()->for($event)->count(2)->create();
	Booking::factory()->for($event)->cancelled()->create();
	Event::factory()->for($course)->in(EventState::Cancelled)->create(['date' => today()->addDays(12)]);
	Event::factory()->for($course)->past()->create();

	ViakServer::tool(UpcomingEvents::class, ['course' => 'rhino'])
		->assertOk()
		->assertSee([$event->number(), 'Rhino Einstiegskurs', '"seats_taken":2', '"count":1']);
});

it('finds one Veranstaltung by its number and lists who is booked', function () {
	$event = Event::factory()->create(['date' => today()->addDays(10)]);
	$booking = Booking::factory()->for($event)->create();
	Booking::factory()->for($event)->cancelled()->create();

	ViakServer::tool(EventBookings::class, ['event' => $event->number()])
		->assertOk()
		->assertSee([$booking->user->email, '"cancelled":1']);

	ViakServer::tool(EventBookings::class, ['event' => '99-010101'])->assertHasErrors();
});

it('sums what is still owed, overdue first', function () {
	Invoice::factory()->create(['grand_total' => '100.00']);
	$late = Invoice::factory()->overdue()->create(['grand_total' => '250.50']);
	Invoice::factory()->paid()->create(['grand_total' => '999.00']);

	ViakServer::tool(OpenInvoices::class)->assertOk()->assertSee(['"total":"350.50"', '"count":2']);

	ViakServer::tool(OpenInvoices::class, ['overdue_only' => true])
		->assertOk()
		->assertSee([$late->number, '"days_overdue":5', '"count":1']);
});

it('answers a tool call over HTTP and logs who made it, without the answer', function () {
	$token = $this->admin->createToken('Claude', [McpAccess::ABILITY])->plainTextToken;
	Invoice::factory()->create(['grand_total' => '120.00']);

	$log = Mockery::mock(LoggerInterface::class);
	$log->shouldReceive('info')->once()->with('mcp', Mockery::on(fn (array $context) => $context['user'] === $this->admin->email
		&& $context['token'] === 'Claude'
		&& $context['tool'] === 'open-invoices'
		&& ! array_key_exists('result', $context)));
	Log::shouldReceive('channel')->with('mcp')->andReturn($log);

	$this->withToken($token)
		->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'open-invoices', 'arguments' => []]])
		->assertOk()
		->assertSee('120.00');
});
