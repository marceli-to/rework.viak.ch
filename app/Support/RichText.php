<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Renders editor HTML. The content is admin-authored, but it is still rendered
 * unescaped, so it goes through a tag allowlist rather than straight out —
 * cheap insurance against a pasted `<script>` or `<iframe>`.
 *
 * Attributes are not filtered here. When the editor moves to tiptap
 * ([[07-editor]]) this should become a proper sanitiser.
 */
final class RichText
{
	private const ALLOWED = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><a><blockquote>';

	public static function render(?string $html): HtmlString
	{
		return new HtmlString(strip_tags((string) $html, self::ALLOWED));
	}
}
