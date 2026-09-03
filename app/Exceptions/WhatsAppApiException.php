<?php

namespace App\Exceptions;

use RuntimeException;

class WhatsAppApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $apiErrorCode = null,
        public readonly bool $retryable = false,
    ) {
        parent::__construct($message);
    }
}
