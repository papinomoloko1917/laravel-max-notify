<?php

namespace App\Services;

use Carbon\CarbonInterface;

class NotificationWindow
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly ?string $from,
        private readonly ?string $until,
    ) {}

    public function allows(CarbonInterface $now): bool
    {
        $currentTime = $now->format('H:i:s');

        if ($this->from === null && $this->until === null) {
            return true;
        }

        if ($this->from === null || $this->until === null) {
            return false;
        }

        if ($this->from === $this->until) {
            return false;
        }

        if ($this->from < $this->until) {
            return $currentTime >= $this->from && $currentTime < $this->until;
        }

        return $currentTime >= $this->from || $currentTime < $this->until;
    }
}
