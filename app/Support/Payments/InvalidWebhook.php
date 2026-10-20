<?php

declare(strict_types=1);

namespace App\Support\Payments;

use RuntimeException;

/** A webhook call that is not signed by the payment provider ([[PaymentGateway]]). */
class InvalidWebhook extends RuntimeException {}
