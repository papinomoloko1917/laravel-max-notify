<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class MaxClient
{
    public function createImageUploadUrl(): string
    {
        $baseUrl = (string) config('services.max.base_url');

        $token = (string) config('services.max.token');

        $response = Http::acceptJson()
            ->withHeaders(['Authorization' => $token])
            ->withQueryParameters([
                'type' => 'image',
            ])
            ->post(
                rtrim($baseUrl, '/') . '/uploads',
            );

        $response->throw();

        $url = $response->json('url');

        if (! is_string($url) || $url === '') {
            throw new UnexpectedValueException('MAX did not return an image upload URL');
        }

        return $url;
    }
}
