<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DiscountCode;

/**
 * A new code, legacy's shape: `VIAK-XXXX-XXXX` from 31 characters that cannot
 * be misread (no 0/O, 1/I/L), 31⁸ ≈ 850 billion of them.
 *
 * Legacy's `Discount::generate()` retried a collision by calling itself and
 * **throwing the result away**, then testing the same code again, so a
 * collision would have looped forever. This retries properly, and checks
 * deleted codes too: the unique index does.
 */
class DiscountCodeGenerator
{
	private const CHARS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

	public function next(): string
	{
		do {
			$code = 'VIAK-'.$this->block().'-'.$this->block();
		} while (DiscountCode::withTrashed()->where('code', $code)->exists());

		return $code;
	}

	private function block(): string
	{
		$block = '';
		for ($i = 0; $i < 4; $i++) {
			$block .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
		}

		return $block;
	}
}
