<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Teammitglied erfassen* / *bearbeiten* ([[07-dashboard]]): what the mockup's
 * Team card shows — a name, one line of what they do, a portrait.
 */
final class TeamMemberSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('name')->label('Name')->required(),
			Field::text('role')->label('Funktion'),
			Field::row([
				Field::checkbox('publish')->label('Publizieren'),
			]),

			// The square portrait on the card: the first image is its *Vorschau*.
			Field::section('Bild', [Field::custom('images')->with(['owner' => 'team-members'])]),
		];
	}

	public function defaults(): array
	{
		return ['name' => '', 'role' => '', 'publish' => false];
	}
}
