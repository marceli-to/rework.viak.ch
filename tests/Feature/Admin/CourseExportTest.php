<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Exports\CourseParticipantsExport;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * *Exporte* — `/api/admin/exports/courses` ([[07-dashboard]], step 7), legacy's
 * *Kurse + Teilnehmer* workbook.
 */
function participant(array $attributes = []): User
{
	return User::factory()->student()->create([
		'gender' => Gender::Female, 'first_name' => 'Eva', 'last_name' => 'Keller', 'company' => 'Keller AG',
		'street' => 'Hauptgasse', 'street_no' => '3', 'zip' => '3011', 'city' => 'Bern',
		'phone' => '031 000 00 00', 'email' => 'eva@example.test',
		...$attributes,
	]);
}

it('writes a sheet per course with past participants, in legacy\'s columns', function () {
	$course = Course::factory()->create(['title' => ['de' => 'Blender Einführungskurs', 'en' => 'Blender']]);
	$past = Event::factory()->for($course)->create(['date' => '2026-03-12']);
	Booking::factory()->for($past)->for(participant())->create();
	Booking::factory()->for($past)->for(participant(['gender' => Gender::Male, 'first_name' => 'Paul', 'email' => 'paul@example.test']))->create();

	// Not participants: a cancelled booking, and a date still to come.
	Booking::factory()->for($past)->for(participant(['email' => 'weg@example.test']))->cancelled()->create();
	Booking::factory()->for(Event::factory()->for($course)->create())->for(participant(['email' => 'bald@example.test']))->create();

	// A course with nobody on a past date gets no sheet.
	Event::factory()->for(Course::factory())->past()->create();

	$book = app(CourseParticipantsExport::class)->workbook();

	expect($book->getSheetNames())->toBe(['Blender Einführungskurs'])
		->and($book->getSheet(0)->toArray())->toBe([
			['Anrede', 'Vorname', 'Nachname', 'Firmenname', 'Strasse', 'Hausnummer', 'PLZ', 'Ort', 'Tel', 'E-Mail', 'Kursdatum'],
			['Frau', 'Eva', 'Keller', 'Keller AG', 'Hauptgasse', '3', '3011', 'Bern', '031 000 00 00', 'eva@example.test', '12.03.2026'],
			['Herr', 'Paul', 'Keller', 'Keller AG', 'Hauptgasse', '3', '3011', 'Bern', '031 000 00 00', 'paul@example.test', '12.03.2026'],
		])
		->and($book->getSheet(0)->getStyle('A1')->getFont()->getBold())->toBeTrue();
});

it('names a sheet the way Excel allows: no colon, 31 characters, never twice', function () {
	foreach (['Präsentation: VisualARQ und Lands Design', 'Präsentation: VisualARQ und Lands Design (2)'] as $title) {
		Booking::factory()->for(Event::factory()->for(Course::factory()->create(['title' => ['de' => $title, 'en' => $title]]))->past())->for(User::factory())->create();
	}

	expect(app(CourseParticipantsExport::class)->workbook()->getSheetNames())
		->toBe(['Präsentation VisualARQ und Land', 'Präsentation VisualARQ und (2)']);
});

it('downloads as an Excel file an admin can open', function () {
	Booking::factory()->for(Event::factory()->past())->for(participant())->create();
	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);

	$response = $this->actingAs($admin)->get('/api/admin/exports/courses')->assertOk();

	expect($response->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
		->and($response->headers->get('Content-Disposition'))->toBe('attachment; filename="viak-kurse-'.now()->format('d.m.Y').'.xlsx"');

	$file = tempnam(sys_get_temp_dir(), 'xlsx');
	file_put_contents($file, $response->getContent());
	expect(IOFactory::load($file)->getSheet(0)->getCell('B2')->getValue())->toBe('Eva');
	unlink($file);
});
