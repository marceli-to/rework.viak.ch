<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Event;

/**
 * The lines the mails' tables repeat about a course date, in legacy's words
 * and formats ([[10-mail]]).
 */
final class EventFacts
{
	/** Every day, `d.m.Y`, comma-separated — legacy's `date_short` list. */
	public static function dates(Event $event): string
	{
		return $event->dates->sortBy('date')->map(fn ($day) => $day->date->format('d.m.Y'))->implode(', ');
	}

	/** Legacy's `experts_fullname_string`. */
	public static function experts(Event $event): string
	{
		return $event->experts->map(fn ($expert) => $expert->name)->implode(', ');
	}

	/** *online*, or the place's name. */
	public static function place(Event $event): string
	{
		return $event->online ? 'online' : (string) $event->location?->getTranslation('description', 'de', false);
	}

	/**
	 * A fee as legacy printed it: the sum as PHP writes a float, `890` or
	 * `847.5`, never `890.00`. Kept for parity with the mails on production.
	 */
	public static function money(string $amount): string
	{
		return (string) (float) $amount;
	}
}
