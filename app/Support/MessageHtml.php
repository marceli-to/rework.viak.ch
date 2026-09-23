<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * What the message composer's editor may store ([[09-public-site]]).
 *
 * The body is HTML from the browser and goes to every student on the course,
 * so it is cleaned **on the way in** — [[RichText]]'s `strip_tags` allowlist on
 * the way out keeps attributes, and `<a href="javascript:…">` would survive it.
 *
 * The allowlist is the editor's schema and nothing more (`js/shared/editor.js`):
 * paragraphs, line breaks, bold, a bullet list, links. A link keeps its `href`
 * and nothing else, and only `http`, `https` and `mailto` — no relative links,
 * because a message is also read in a mail client where they point nowhere.
 * **Anything else is dropped with its contents**, which is Symfony's default
 * and the only safe one here: set to unwrap instead, a `<style>` or a
 * `<template>` leaves its text behind, because the body context will not
 * register a drop for a `<head>` element. Nothing is lost in practice — the
 * editor's schema has already reduced a paste to these tags before it is
 * sent, so only a hand-made request carries anything else.
 */
final class MessageHtml
{
	public static function sanitize(string $html): string
	{
		$config = (new HtmlSanitizerConfig)
			->allowElement('p')
			->allowElement('br')
			->allowElement('strong')
			->allowElement('ul')
			->allowElement('li')
			->allowElement('a', ['href'])
			->allowLinkSchemes(['http', 'https', 'mailto'])
			->allowRelativeLinks(false);

		$clean = (new HtmlSanitizer($config))->sanitize($html);

		// Pressing Return at the end leaves an empty paragraph behind, and
		// tiptap sends it. Trailing ones only: an empty one between two
		// paragraphs is spacing somebody meant.
		return preg_replace('#(?:<p>(?:\s|&nbsp;|<br\s*/?>)*</p>\s*)+$#u', '', trim($clean)) ?? '';
	}

	/** Whether any words are left once the tags are gone — `<p></p>` is empty. */
	public static function isBlank(string $html): bool
	{
		return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5), " \t\n\r\0\x0B\u{00A0}") === '';
	}
}
