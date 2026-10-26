<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\EventBookings;
use App\Mcp\Tools\OpenInvoices;
use App\Mcp\Tools\OutstandingLicenceOrders;
use App\Mcp\Tools\UpcomingEvents;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * VIAK's MCP server ([[17-mcp]]), at `/mcp`, for admins with a token from
 * *Mein Profil* ([[McpAccess]]).
 *
 * **Read-only until the client says otherwise** (#64): the four tools answer
 * the office's everyday questions and change nothing. A write, when one is
 * asked for, goes through the action the dashboard uses, never straight to
 * a table, so the same rules and mails apply.
 */
#[Name('VIAK')]
#[Version('0.1.0')]
#[Instructions(<<<'TEXT'
	Data of the Visualisierungs-Akademie (VIAK), a Swiss school for 3D and visualisation software that also resells software licences. Answers are in German where the data is.

	Vocabulary: a Kurs is a course in the catalogue; a Veranstaltung is one run of it on given dates, identified by its number (course number and start date, e.g. 05-161126). A Buchung is a customer's seat on a Veranstaltung. A Rechnung is an invoice; a Lizenzbestellung is an order for software licences, which VIAK sends out by hand.

	Every tool is read-only. Amounts are in CHF.
	TEXT)]
class ViakServer extends Server
{
	protected array $tools = [
		UpcomingEvents::class,
		EventBookings::class,
		OpenInvoices::class,
		OutstandingLicenceOrders::class,
	];
}
