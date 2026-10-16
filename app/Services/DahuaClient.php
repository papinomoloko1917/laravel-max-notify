<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class DahuaClient
{
    public function snapshot(
        string $baseUrl,
        string $username,
        string $password,
        int $channel,
    ): string {
        $connectTimeout = (int) config('services.dahua.connect_timeout');
        $timeout = (int) config('services.dahua.timeout');

        $response = Http::withOptions([
            'auth' => [
                $username,
                $password,
                'digest',
            ],
        ])
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->get(
                rtrim($baseUrl, '/') . '/cgi-bin/snapshot.cgi',
                ['channel' => $channel],
            );

        $response->throw();

        $contentType = $response->header('Content-Type');

        if ($contentType !== 'image/jpeg') {
            throw new UnexpectedValueException(
                'Unexpected Dahua snapshot content type.',
            );
        }

        return $response->body();
    }
}
