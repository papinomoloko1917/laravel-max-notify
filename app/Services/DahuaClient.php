<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class DahuaClient
{
    public function snapshot(int $channel): string
    {
        $baseUrl = (string) config('services.dahua.base_url');
        $username = (string) config('services.dahua.username');
        $password = (string) config('services.dahua.password');

        $response = Http::withOptions([
            'auth' => [
                $username,
                $password,
                'digest',
            ],
        ])->get(
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
