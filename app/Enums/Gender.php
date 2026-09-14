<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Collected for the salutation on invoices and course confirmations, nothing
 * more. Legacy kept these in a `genders` lookup table with translatable
 * labels; three fixed rows nobody can usefully extend are an enum.
 */
enum Gender: string
{
	case Male = 'male';
	case Female = 'female';
	case Other = 'other';

	/** The legacy `genders.id` this maps to, for the port. */
	public static function fromLegacyId(int $id): ?self
	{
		return match ($id) {
			1 => self::Male,
			2 => self::Female,
			3 => self::Other,
			default => null,
		};
	}

	public function label(): string
	{
		return match ($this) {
			self::Male => __('männlich'),
			self::Female => __('weiblich'),
			self::Other => __('andere'),
		};
	}
}
