<?php

declare(strict_types=1);

namespace App\Support\Documents;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

/**
 * Blade in, PDF bytes out ([[03-invoices]], [[08-accounts]]).
 *
 * The one place dompdf is configured, and the whole of what legacy spread
 * across `Services/Pdf/Pdf.php`, `Services/Pdf/Invoice/EventInvoice.php` and
 * `Services/Pdf/Invoice/RentalInvoice.php` — 273 lines of which the last two
 * were the same file with two strings changed, each carrying a `create()` and
 * an `update()` that were themselves copies of each other.
 *
 * ## What it deliberately does not do
 *
 * **It does not touch the filesystem or the database.** Legacy's services
 * rendered a PDF, invented a filename, made a directory, wrote the file and
 * inserted a `user_documents` row, all in one method — so the only way to
 * preview an invoice was to persist one. Here the Action decides what the
 * document *is* and where it goes ([[RenderInvoice]]); this turns markup into
 * bytes.
 *
 * ## Why not `barryvdh/laravel-dompdf`
 *
 * It adds a facade, a service provider and a 200-line published config over
 * `loadHtml`/`render`/`output`. Every option below is one this application has
 * a reason for, and half of them contradict that config's defaults — so the
 * wrapper would have been a second place to look for the answer.
 */
final class PdfRenderer
{
	/** @param array<string, mixed> $data */
	public function render(string $view, array $data = []): string
	{
		return $this->fromHtml(view($view, $data)->render());
	}

	public function fromHtml(string $html): string
	{
		$dompdf = new Dompdf($this->options());

		$dompdf->loadHtml($html, 'UTF-8');
		$dompdf->setPaper('A4', 'portrait');
		$dompdf->render();

		return (string) $dompdf->output();
	}

	private function options(): Options
	{
		$cache = (string) config('documents.font_cache');

		// dompdf writes its compiled fonts here and does not create the
		// directory itself — it warns and falls back to Helvetica, which is the
		// kind of failure that shows up as "the invoice looks wrong" months
		// later rather than as an error.
		File::ensureDirectoryExists($cache);

		$options = new Options();

		/*
		 * **No network at render time.** Legacy's templates pull the two Effra
		 * faces and the letterhead over HTTP from the application's own public
		 * URL every time a PDF is made. Everything here is a local file or a
		 * `data:` URI, both of which dompdf reads without this switch — so the
		 * renderer cannot be made to fetch a URL by anything that reaches a
		 * template, and cannot quietly render a fontless invoice because a queue
		 * worker had no route out.
		 */
		$options->set('isRemoteEnabled', false);

		// Local files are governed by chroot rather than by the switch above.
		// Confined to the directory holding the fonts and the letterhead, so a
		// `file://` in a template cannot reach `.env`.
		$options->set('chroot', [(string) config('documents.assets.path')]);

		$options->set('fontDir', $cache);
		$options->set('fontCache', $cache);

		// Only ever reached if a glyph is missing from Effra.
		$options->set('defaultFont', 'Helvetica');

		/*
		 * **Off, and it is a security setting rather than a feature switch.**
		 * dompdf executes `<script type="text/php">` when this is on, and the
		 * invoice template interpolates a customer's own address. Legacy leaves
		 * the default (off) and has a commented-out page-number script in
		 * `footer.blade.php` that would have turned it on; saying so here means
		 * the next person to want page numbers has to make that trade knowingly.
		 */
		$options->set('isPhpEnabled', false);

		$options->set('isHtml5ParserEnabled', true);
		$options->set('defaultPaperSize', 'A4');

		return $options;
	}
}
