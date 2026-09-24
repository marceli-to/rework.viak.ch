<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * What the dashboard's editor may store in a course's texts ([[07-dashboard]]).
 *
 * The same idea as [[MessageHtml]] — cleaned **on the way in**, because
 * [[RichText]]'s allowlist on the way out keeps attributes, and
 * `<a href="javascript:…">` would survive it. Two differences, both from the
 * course copy itself, counted on 2026-09-24:
 *
 * - **a link keeps `target` and `rel`** — 63 of the 87 links in the ported
 *   course texts open in a new tab, and saving a course must not quietly
 *   change that;
 * - **relative links are allowed** — a course page links to other pages of
 *   the site, where a message read in a mail client could not.
 *
 * The tags are the editor's schema: paragraphs, line breaks, bold, a bullet
 * list, links. They are also everything the ported copy uses — `p`, `br`,
 * `strong`, `ul`, `li`, `a` and nothing else — so cleaning an old course on
 * its first save loses nothing.
 */
final class EditorHtml
{
	public static function sanitize(?string $html): ?string
	{
		if ($html === null || MessageHtml::isBlank($html)) {
			return null;
		}

		$config = (new HtmlSanitizerConfig)
			->allowElement('p')
			->allowElement('br')
			->allowElement('strong')
			->allowElement('ul')
			->allowElement('li')
			->allowElement('a', ['href', 'target', 'rel'])
			->allowLinkSchemes(['http', 'https', 'mailto'])
			->allowRelativeLinks();

		$clean = (new HtmlSanitizer($config))->sanitize($html);

		// tiptap sends a trailing empty paragraph when Return was the last key.
		return preg_replace('#(?:<p>(?:\s|&nbsp;|<br\s*/?>)*</p>\s*)+$#u', '', trim($clean)) ?: null;
	}
}
