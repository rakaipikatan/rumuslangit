<?php

namespace App\Exceptions;

class GeminiRateLimitException extends \RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds,
        string $message = '',
    ) {
        parent::__construct($message ?: "Gemini rate limit: retry in {$retryAfterSeconds}s");
    }
}
