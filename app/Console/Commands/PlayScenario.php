<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Scenarios\Cancel;
use App\Console\Scenarios\Confirm;
use App\Console\Scenarios\CustomerCancels;
use App\Console\Scenarios\LateBooker;
use App\Console\Scenarios\Reminder;
use App\Console\Scenarios\Rental;
use App\Console\Scenarios\Scenario;
use App\Console\Scenarios\Stage;
use App\Mail\VIAKMail;
use Database\Seeders\DevUsersSeeder;
use Illuminate\Console\Command;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

/**
 * Plays one branch of the mail flow table step by step ([[10-mail]], layer 3):
 * a throwaway course, date and people, then the real Actions in order,
 * printing each step and the mails it sent, and pausing for Enter between
 * steps so the inbox, the portals and the dashboard can be looked at.
 *
 * - **Mail is sent in-process** (`queue.default` is `sync` for the run), so a
 *   step's mails are in MailHog when it ends, with no worker needed. It is the
 *   same mailable either way; only the transport of the job differs.
 * - The clock moves with `Carbon::setTestNow()`, so the penalty window and the
 *   reminder do not wait days. It is put back at the end.
 * - Afterwards the run's data is removed ([[Stage::tearDown]]); `--keep`
 *   leaves it for clicking around, signed in as any of its people.
 *
 * Through the Actions, not HTTP: it does not replace opening the auth-gated
 * screens in a browser ([[viak-tests-cannot-see-middleware]]).
 *
 * **Local only**, like [[DevUsersSeeder]]: it writes people with a known
 * password. The `paid` branch of the table waits for the card-payment page,
 * as its mails do (`Open-Questions.md` #28).
 */
class PlayScenario extends Command
{
	protected $signature = 'scenario:play
		{name? : The scenario; leave out to list them}
		{--no-pause : Run straight through}
		{--keep : Leave the scenario\'s course, date and people in the database}';

	protected $description = 'Play a mail flow step by step through the real Actions (local only)';

	/** @var array<string, class-string<Scenario>> */
	public const SCENARIOS = [
		'confirm' => Confirm::class,
		'cancel' => Cancel::class,
		'late-booker' => LateBooker::class,
		'student-cancels' => CustomerCancels::class,
		'rental' => Rental::class,
		'reminder' => Reminder::class,
	];

	/** @var array<int, string> the current step's mails, one line each */
	private array $sent = [];

	public function handle(): int
	{
		if (! app()->environment('local', 'testing')) {
			$this->error('scenario:play runs locally only: it writes people with a known password.');

			return self::FAILURE;
		}

		$name = $this->argument('name');

		if ($name === null) {
			$this->table(['Scenario', 'What it plays'], collect(self::SCENARIOS)->map(fn ($class, $key) => [$key, $class::description()])->values()->all());

			return self::SUCCESS;
		}

		if (! isset(self::SCENARIOS[$name])) {
			$this->error("No scenario \"{$name}\". Known: ".implode(', ', array_keys(self::SCENARIOS)).'.');

			return self::FAILURE;
		}

		config(['queue.default' => 'sync']);
		Event::listen(MessageSending::class, fn (MessageSending $sending) => $this->sent[] = $this->describe($sending));

		$stage = new Stage($name);
		$scenario = new (self::SCENARIOS[$name])($stage);

		$this->intro($name);

		try {
			$this->play($scenario);
		} catch (Throwable $e) {
			$this->newLine();
			$this->error($e->getMessage());
			$this->finish($stage);

			throw $e;
		}

		$this->finish($stage);

		return self::SUCCESS;
	}

	private function intro(string $name): void
	{
		$this->info("Scenario {$name}: ".self::SCENARIOS[$name]::description().'.');

		$catchAll = config('mail.catch_all') ?: 'catch-all@viak.test';
		$this->line("Every mail goes to {$catchAll}; the header ".VIAKMail::INTENDED_TO.' says who it was for.');

		if (! config('mail.admin')) {
			$this->warn('No office address (MAIL_ADMIN_ADDRESS): the office\'s mails will not be sent.');
		}
	}

	private function play(Scenario $scenario): void
	{
		$steps = $scenario->steps();
		$number = 0;

		foreach ($steps as $title => $step) {
			$number++;
			$this->sent = [];

			$this->newLine();
			$this->line("<options=bold>{$number}/".count($steps)." {$title}</>");

			$clock = Carbon::getTestNow();

			if ($note = $step()) {
				$this->line("  {$note}");
			}

			if (Carbon::getTestNow() != $clock) {
				$this->line('  <fg=gray>The clock now reads '.now()->format('d.m.Y H:i').'.</>');
			}

			foreach ($this->sent ?: ['<fg=gray>no mail</>'] as $line) {
				$this->line("  {$line}");
			}

			if ($number < count($steps)) {
				$this->pause('Enter for the next step');
			}
		}
	}

	private function finish(Stage $stage): void
	{
		Carbon::setTestNow();
		$this->newLine();

		if ($this->option('keep')) {
			$this->info('Kept. Sign in as any of these with the dev password ('.DevUsersSeeder::PASSWORD.'):');
			foreach ($stage->people() as $person) {
				$this->line("  {$person->email}");
			}
			$this->line('The course is "'.$stage->course()?->getTranslation('title', 'de').'".');

			return;
		}

		$this->pause('Enter removes the scenario\'s data (or run it with --keep)');
		$stage->tearDown();
		$this->info('Removed.');
	}

	/** Subject, the address it was meant for, and what rides along. */
	private function describe(MessageSending $sending): string
	{
		$message = $sending->message;
		$for = $message->getHeaders()->get(VIAKMail::INTENDED_TO)?->getBodyAsString()
			?? collect($message->getTo())->map->getAddress()->implode(', ');
		$files = collect($message->getAttachments())->map(fn (DataPart $part) => $part->getFilename())->filter();

		return "<fg=green>✉</> {$message->getSubject()} <fg=gray>→</> {$for}"
			.($files->isNotEmpty() ? ' <fg=yellow>+ '.$files->implode(', ').'</>' : '');
	}

	private function pause(string $question): void
	{
		if (! $this->option('no-pause') && $this->input->isInteractive()) {
			$this->ask($question);
		}
	}
}
