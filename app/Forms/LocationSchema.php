<?php

declare(strict_types=1);

namespace App\Forms;

/**
 * *Ort* — legacy's `setting/location/Form.vue`: the name a course date shows,
 * the address, and the map link. German only, as [[TermSchema]].
 */
final class LocationSchema extends Schema
{
	public function fields(): array
	{
		return [
			Field::text('description')->label('Bezeichnung')->required(),
			Field::textarea('address')->label('Adresse')->required()->rules(['max:1000'])->with(['rows' => 3]),
			Field::text('map')->label('Google-Maps-Link')->rules(['url', 'max:2000'])
				->message('url', 'Bitte einen vollständigen Link erfassen, mit https://.'),
			Field::row([
				Field::checkbox('publish')->label('Publizieren'),
			]),
		];
	}

	public function defaults(): array
	{
		return ['description' => '', 'address' => '', 'map' => '', 'publish' => true];
	}
}
