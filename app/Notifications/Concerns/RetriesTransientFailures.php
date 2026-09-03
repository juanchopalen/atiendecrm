<?php

namespace App\Notifications\Concerns;

trait RetriesTransientFailures
{
    /**
     * Matches spec section 9: retry transient WhatsApp API failures (5xx,
     * timeouts) up to 5 times with exponential backoff. Non-retryable
     * failures (unapproved template, invalid number) are never thrown by
     * WhatsAppChannel, so they don't consume these attempts.
     */
    public int $tries = 5;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }
}
