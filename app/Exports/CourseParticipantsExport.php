<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\Gender;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * *Download Kurse + Teilnehmer (Excel)* ([[07-dashboard]], step 7) — legacy's
 * `CoursesExport` and `CourseExportSheet`, which kept someone's mailing list or
 * accounting routine going.
 *
 * The same file: **a sheet per course that has had participants**, a row per
 * booking on each of its past dates, legacy's eleven columns under legacy's
 * German headings, the heading row bold and the columns as wide as their
 * contents. The person's own address, not the invoice's, as legacy wrote it.
 *
 * What legacy counted, counted the same way: a date is past when it began
 * before today ([[Event::scopePast]]), and a cancelled booking is not a
 * participant (legacy's `notFlagged('isCancelled')`). Deleted courses and
 * dates stay out, as they did.
 *
 * Written with PhpSpreadsheet directly. Legacy went through
 * `maatwebsite/excel`, which is PhpSpreadsheet underneath; one file with no
 * queued chunks does not need the wrapper.
 */
class CourseParticipantsExport
{
	private const HEADINGS = ['Anrede', 'Vorname', 'Nachname', 'Firmenname', 'Strasse', 'Hausnummer', 'PLZ', 'Ort', 'Tel', 'E-Mail', 'Kursdatum'];

	/** The workbook's bytes. */
	public function xlsx(): string
	{
		$book = $this->workbook();
		$stream = fopen('php://memory', 'r+');

		(new Xlsx($book))->save($stream);
		rewind($stream);
		$bytes = (string) stream_get_contents($stream);
		fclose($stream);
		$book->disconnectWorksheets();

		return $bytes;
	}

	public function workbook(): Spreadsheet
	{
		$book = new Spreadsheet;
		$book->removeSheetByIndex(0);
		$titles = [];

		foreach ($this->courses() as $course) {
			$rows = $course->events->flatMap(fn (Event $event) => $event->bookings->map(fn (Booking $booking) => $this->row($booking, $event)));

			if ($rows->isEmpty()) {
				continue;
			}

			$sheet = $book->createSheet();
			$sheet->setTitle($this->title($course, $titles));
			$sheet->fromArray([self::HEADINGS, ...$rows->all()], null, 'A1', true);
			$sheet->getStyle('A1:K1')->getFont()->setBold(true);

			foreach (range('A', 'K') as $column) {
				$sheet->getColumnDimension($column)->setAutoSize(true);
			}
		}

		// A workbook has at least one sheet; before anyone has taken a course,
		// that is an empty one under the headings.
		if ($book->getSheetCount() === 0) {
			$book->createSheet()->setTitle('Kurse')->fromArray([self::HEADINGS]);
		}

		$book->setActiveSheetIndex(0);

		return $book;
	}

	/** @return Collection<int, Course> */
	private function courses(): Collection
	{
		return Course::query()
			->with(['events' => fn ($events) => $events->past()->reorder('date')->with([
				'bookings' => fn ($bookings) => $bookings->active()->whereHas('user')->orderBy('id')->with('user'),
			])])
			->orderBy('id')
			->get();
	}

	/** @return array<int, string|null> */
	private function row(Booking $booking, Event $event): array
	{
		$user = $booking->user;

		return [
			match ($user->gender) {
				Gender::Male => 'Herr',
				Gender::Female => 'Frau',
				Gender::Other => 'Andere',
				default => null,
			},
			$user->first_name,
			$user->last_name,
			$user->company,
			$user->street,
			$user->street_no,
			$user->zip,
			$user->city,
			$user->phone,
			$user->email,
			$event->date?->format('d.m.Y'),
		];
	}

	/**
	 * The course's title, as legacy named each sheet, made into one Excel
	 * accepts: none of `\ / ? * [ ] :`, at most 31 characters, and no two
	 * alike. Legacy handed the title over as it was; a longer one broke the
	 * download.
	 *
	 * @param  array<int, string>  $taken
	 */
	private function title(Course $course, array &$taken): string
	{
		$base = trim(Str::limit(trim((string) preg_replace('/\s*[\\\\\/?*\[\]:]+\s*/', ' ', (string) $course->getTranslation('title', 'de'))), 31, '')) ?: 'Kurs';
		$title = $base;

		for ($n = 2; in_array(mb_strtolower($title), $taken, true); $n++) {
			$title = trim(Str::limit($base, 31 - strlen(" ({$n})"), '')." ({$n})");
		}

		$taken[] = mb_strtolower($title);

		return $title;
	}
}
