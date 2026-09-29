<?php

declare(strict_types=1);

namespace App\Console\Scenarios;

use App\Actions\Accounts\RegisterUser;
use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CompleteCheckout;
use App\Actions\Bookings\PriceBasket;
use App\Actions\Courses\CreateCourse;
use App\Actions\Events\CreateEvent;
use App\Enums\BookingCancellationReason;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Checkout;
use App\Models\Country;
use App\Models\Course;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Message;
use App\Models\User;
use App\Models\UserDocument;
use Database\Seeders\DevUsersSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The throwaway world a scenario plays in ([[10-mail]], layer 3): one course,
 * its dates, an expert and the students, and everything the Actions hang off
 * them. Built through the **real Actions** wherever the site has one, so a
 * scenario runs the paths a click runs; the people are written directly,
 * except where registering is the step.
 *
 * Everything is named after the run, `szenario-{run}-…@viak.test`, and holds
 * the dev users' password ([[.rework/Test-Users.md]]), so a run kept with
 * `--keep` can be signed into. `tearDown()` takes it all away again, down to
 * the PDFs.
 */
class Stage
{
	public readonly string $run;

	private ?Course $course = null;

	private ?User $defaultExpert = null;

	/** @var array<int, Event> */
	private array $events = [];

	/** @var array<int, User> */
	private array $people = [];

	public function __construct(public readonly string $scenario)
	{
		$this->run = Str::lower(Str::random(4));
	}

	/**
	 * A course date, a month out unless told otherwise, with one expert.
	 *
	 * @param  array<string, mixed>  $attributes
	 */
	public function event(int $daysOut = 30, array $attributes = [], ?User $expert = null): Event
	{
		$this->course ??= app(CreateCourse::class)->execute([
			'title' => ['de' => "Szenario {$this->scenario} ({$this->run})"],
			'subtitle' => ['de' => 'Kurs aus scenario:play, wird wieder gelöscht'],
			'fee' => '890.00',
			'online' => false,
			'publish' => true,
			'order' => 0,
		]);

		$day = now()->addDays($daysOut)->toDateString();

		return $this->events[] = app(CreateEvent::class)->execute(
			$this->course,
			[
				'min_participants' => 2,
				'max_participants' => 4,
				'rentals_available' => 2,
				'publish' => true,
				'location_id' => Location::query()->published()->value('id'),
				...$attributes,
			],
			[['date' => $day, 'time_start' => '09:00', 'time_end' => '17:00']],
			[($expert ?? ($this->defaultExpert ??= $this->expert('Kevin')))->id],
		);
	}

	/** An expert, made in the dashboard: no invite is part of any scenario. */
	public function expert(string $firstName): User
	{
		return $this->person($firstName, 'Experte', Role::Expert);
	}

	/** A student who already has an account, verified. */
	public function student(string $firstName): User
	{
		return $this->person($firstName, 'Muster', Role::Student);
	}

	/**
	 * A student who signs up on the site: [[RegisterUser]] and then the
	 * `Registered` event Fortify fires after it, which is what sends
	 * *Bestätigung Anmeldung*.
	 */
	public function register(string $firstName): User
	{
		$email = $this->email($firstName);
		$country = Country::query()->where('code', 'CH')->value('code') ?? Country::query()->value('code');

		$user = app(RegisterUser::class)->create([
			'gender' => 'female',
			'first_name' => $firstName,
			'last_name' => 'Muster',
			'phone' => '044 000 00 00',
			'street' => 'Musterstrasse',
			'street_no' => '1',
			'zip' => '8000',
			'city' => 'Zürich',
			'country_code' => $country,
			'email' => $email,
			'email_confirmation' => $email,
			'password' => DevUsersSeeder::PASSWORD,
			'password_confirmation' => DevUsersSeeder::PASSWORD,
			'operating_systems' => ['windows'],
			'accept_tos' => true,
		]);

		event(new Registered($user));

		return $this->people[] = $user;
	}

	/** A seat bought through the site's checkout ([[CompleteCheckout]]). */
	public function book(User $student, Event $event, bool $rental = false): Booking
	{
		$basket = app(PriceBasket::class)->execute([['event' => $event->refresh(), 'rental' => $rental]], null, $student);

		return app(CompleteCheckout::class)->execute($student, $basket)->bookings->first();
	}

	/** The student gives the seat up themselves ([[CancelBooking]]). */
	public function cancel(Booking $booking): ?Invoice
	{
		return app(CancelBooking::class)->execute($booking->refresh(), BookingCancellationReason::Student);
	}

	/** Moves the clock for the rest of the run ([[PlayScenario]] puts it back). */
	public function travelTo(Carbon $moment): void
	{
		Carbon::setTestNow($moment);
	}

	/** @return array<int, User> */
	public function people(): array
	{
		return $this->people;
	}

	public function course(): ?Course
	{
		return $this->course;
	}

	/**
	 * Takes the run away again, children before parents: most of the foreign
	 * keys on bookings, invoices and documents restrict rather than cascade,
	 * on purpose ([[01-schema]]), so the order is the one they allow.
	 */
	public function tearDown(): void
	{
		$userIds = collect($this->people)->pluck('id')->all();
		$eventIds = collect($this->events)->pluck('id')->all();

		DB::transaction(function () use ($userIds, $eventIds): void {
			$bookingIds = Booking::withTrashed()->whereIn('event_id', $eventIds)->pluck('id');

			DiscountCode::withTrashed()->whereIn('booking_id', $bookingIds)->forceDelete();

			UserDocument::query()->whereIn('user_id', $userIds)->get()->each(function (UserDocument $document): void {
				Storage::disk('documents')->delete($document->path());
				$document->delete();
			});

			Invoice::withTrashed()->whereIn('user_id', $userIds)->forceDelete();
			Message::withTrashed()->whereIn('event_id', $eventIds)->forceDelete();
			Booking::withTrashed()->whereIn('id', $bookingIds)->forceDelete();
			Checkout::query()->whereIn('user_id', $userIds)->delete();
			Event::withTrashed()->whereIn('id', $eventIds)->forceDelete();
			$this->course?->forceDelete();
			User::withTrashed()->whereIn('id', $userIds)->forceDelete();
		});
	}

	private function person(string $firstName, string $lastName, Role $role): User
	{
		$user = User::create([
			'first_name' => $firstName,
			'last_name' => $lastName,
			'email' => $this->email($firstName),
			'password' => Hash::make(DevUsersSeeder::PASSWORD),
		]);
		$user->forceFill(['email_verified_at' => now()])->save();

		DB::table('role_user')->insert(['user_id' => $user->id, 'role' => $role->value]);

		return $this->people[] = $user;
	}

	private function email(string $firstName): string
	{
		return 'szenario-'.$this->run.'-'.Str::lower($firstName).'@viak.test';
	}
}
