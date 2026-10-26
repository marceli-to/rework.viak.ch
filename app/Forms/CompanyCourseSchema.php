<?php

declare(strict_types=1);

namespace App\Forms;

use App\Models\Course;

/**
 * *Firmenkurs erfassen* / *bearbeiten* ([[16-firmenkurse]]).
 *
 * The company first, as the invoice address it is billed to: typed, since
 * each Firmenkurs is a one-off ([[InvoiceSchema]]'s fields, the company
 * required). A catalogue course or a typed title, or both: the title wins on
 * the invoice mail. The days as a
 * Veranstaltung's, and the lines *Rechnung stellen* issues, each a text and a
 * net amount, exempt like a course unless ticked *MWST*.
 */
final class CompanyCourseSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('company')->label('Firma')->required(),
			Field::row([
				Field::text('first_name')->label('Vorname'),
				Field::text('last_name')->label('Nachname'),
			])->with(['columns' => 2]),
			Field::row([
				Field::text('street')->label('Strasse')->required(),
				Field::text('street_no')->label('Nr.')->rules(['max:15']),
			])->with(['columns' => 2]),
			Field::row([
				Field::text('zip')->label('PLZ')->required()->rules(['max:15']),
				Field::text('city')->label('Ort')->required(),
			])->with(['columns' => 2]),
			Field::select('country', self::countries())->label('Land')->required(),

			Field::select('course', fn () => Course::query()->orderBy('number')->get()
				->mapWithKeys(fn (Course $course) => [$course->uuid => $course->number.' '.$course->getTranslation('title', 'de')])->all())
				->label('Kurs')->with(['placeholder' => 'Kein Kurs aus dem Katalog']),
			Field::text('title')->label('Titel')->rules(['required_without:course'])
				->message('required_without', 'Bitte einen Kurs wählen oder einen Titel erfassen.')
				->with(['hint' => 'Für ein eigenes Programm. Leer lassen für den Titel des Kurses.']),
			Field::text('location')->label('Kursort')->with(['hint' => 'Zum Beispiel «bei der Firma, Zürich».']),
			Field::number('participants')->label('Teilnehmende')->rules(['integer', 'min:1', 'max:999']),

			Field::repeater('dates', [
				Field::date('date')->label('Datum')->required()
					->message('required', 'Bitte ein Datum erfassen.')
					->message('date_format', 'Bitte als TT.MM.JJJJ erfassen, mit vierstelligem Jahr.'),
				Field::time('time_start')->label('von')
					->message('date_format', 'Bitte als hh.mm erfassen.'),
				Field::time('time_end')->label('bis')->rules(['after:dates.*.time_start'])
					->message('date_format', 'Bitte als hh.mm erfassen.')
					->message('after', 'Endet vor dem Beginn.'),
			], ['date' => '', 'time_start' => '', 'time_end' => ''])
				->label('Kursdaten')->required()->rules(['min:1'])
				->message('required', 'Bitte mindestens ein Datum erfassen.')
				->message('min', 'Bitte mindestens ein Datum erfassen.')
				->with(['inline' => true]),

			Field::text('email')->label('Rechnung an (E-Mail)')->required()->rules(['email'])
				->with(['input' => 'email', 'hint' => '«Rechnung stellen» sendet die Rechnung als PDF an diese Adresse.']),

			Field::section('Rechnungspositionen', [
				Field::repeater('lines', [
					Field::text('description')->label('Leistung')->required()
						->message('required', 'Bitte die Leistung erfassen.'),
					Field::number('net')->label('Betrag netto (CHF)')->required()->rules(['min:0', 'max:999999.99'])
						->message('required', 'Bitte einen Betrag erfassen.'),
					Field::checkbox('taxed')->label('MWST '.config('invoice.vat_rate').' %'),
				], ['description' => '', 'net' => '', 'taxed' => false])
					->label('Rechnungspositionen')->with(['add' => 'Position hinzufügen']),
			])->with(['count' => 'lines', 'open' => true]),

			Field::textarea('remarks')->label('Bemerkungen')->rules(['max:2000']),
		];
	}

	public function defaults(): array
	{
		return [
			'company' => '', 'first_name' => '', 'last_name' => '',
			'street' => '', 'street_no' => '', 'zip' => '', 'city' => '', 'country' => 'ch',
			'course' => '', 'title' => '', 'location' => '', 'participants' => '',
			'dates' => [['date' => '', 'time_start' => '', 'time_end' => '']],
			'email' => '',
			'lines' => [],
			'remarks' => '',
		];
	}
}
