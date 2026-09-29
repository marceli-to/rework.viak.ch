<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Mail\VIAKMail;
use App\Support\MailPreviews;
use Closure;
use Illuminate\Http\Response;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\DB;

/**
 * `/dev/mails`, **local only** ([[10-mail]], layer 5): every VIAK mail
 * rendered from fixture data, one link each, for measuring against legacy's
 * mails on production side by side, the way the PDFs were ([[03-invoices]]).
 *
 * The fixtures are written and **rolled back** on every request
 * ([[MailPreviews]]): nothing is left behind and nothing is sent.
 */
class MailPreviewController extends Controller
{
	public function index(): Response
	{
		return $this->rolledBack(function (MailPreviews $previews): Response {
			$mails = collect($previews->all())->map(function (array $entry, string $key): array {
				[$when, $make] = $entry;
				$mail = $make();

				return [
					'key' => $key,
					'when' => $when,
					'subject' => $mail->envelope()->subject,
					'files' => collect(method_exists($mail, 'attachments') ? $mail->attachments() : [])->map(fn (Attachment $file) => $file->as)->filter()->all(),
				];
			});

			return response()->view('dev.mails', ['mails' => $mails]);
		});
	}

	public function show(string $key): Response
	{
		return $this->rolledBack(function (MailPreviews $previews) use ($key): Response {
			$make = $previews->all()[$key][1] ?? abort(404);

			/** @var VIAKMail $mail */
			$mail = $make();

			return response($mail->render());
		});
	}

	/** @param  Closure(MailPreviews): Response  $render */
	private function rolledBack(Closure $render): Response
	{
		DB::beginTransaction();

		try {
			return $render(new MailPreviews);
		} finally {
			DB::rollBack();
		}
	}
}
