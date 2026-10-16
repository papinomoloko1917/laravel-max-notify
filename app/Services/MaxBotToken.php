<?php

namespace App\Services;

use App\Models\MaxBotSetting;
use UnexpectedValueException;

class MaxBotToken
{
    private ?string $resolvedToken = null;

    public function get(): string
    {
        if ($this->resolvedToken !== null) {
            return $this->resolvedToken;
        }

        $storedToken = MaxBotSetting::find(MaxBotSetting::CURRENT_ID)?->token;
        $fallbackToken = config('services.max.token');

        $token = is_string($storedToken) && $storedToken !== ''
            ? $storedToken
            : $fallbackToken;

        if (! is_string($token) || $token === '') {
            throw new UnexpectedValueException('MAX bot token is not configured');
        }

        return $this->resolvedToken = $token;
    }
}
