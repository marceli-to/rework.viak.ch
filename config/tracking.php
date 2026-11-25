<?php

declare(strict_types=1);

/*
 * Tracking and attribution ([[11-tracking]]).
 *
 * `heard_from`: the answers to *Wie wurdest du auf uns aufmerksam?*, asked once
 * per customer on the checkout's confirmation page (step 4). Key => label: the
 * key is what is stored on the user, so a label can be reworded without
 * touching the data. **Placeholders** until VIAK sends its list
 * (`Open-Questions.md` #31); a key that is dropped later leaves its stored
 * answers readable in the database but out of the choice.
 *
 * `andere` is the one key that opens a text field.
 */
return [
	'heard_from' => [
		'google' => 'Google-Suche',
		'social' => 'Instagram oder Facebook',
		'linkedin' => 'LinkedIn',
		'ki' => 'ChatGPT oder ein anderer KI-Assistent',
		'empfehlung' => 'Empfehlung von Kolleg:innen',
		'hersteller' => 'Über einen Software-Hersteller',
		'andere' => 'Andere',
	],
];
