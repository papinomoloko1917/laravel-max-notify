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

    public function uploadImage(string $uploadUrl, string $imageContents): string
    {
        $token = (string) config('services.max.token');

        $response = Http::acceptJson()
            ->withHeaders(['Authorization' => $token])
            ->attach(
                'data',
                $imageContents,
                'snapshot.jpg',
            )
            ->post($uploadUrl);

        $response->throw();

        $mediaToken = $response->json('photos.0.token');

        if (! is_string($mediaToken) || $mediaToken === '') {
            throw new UnexpectedValueException('MAX did not return a media token for the uploaded image');
        }

        return $mediaToken;
    }

    public function sendImageMessage(
        int $chatId,
        string $mediaToken,
        string $text,
    ): string {
        $baseUrl = (string) config('services.max.base_url');

        $token = (string) config('services.max.token');

        $response = Http::acceptJson()
            ->asJson()
            ->withHeaders(['Authorization' => $token])
            ->withQueryParameters(['chat_id' => $chatId])
            ->post(rtrim($baseUrl, '/') . '/messages', [
                'text' => $text,
                'attachments' => [
                    [
                        'type' => 'image',
                        'payload' => [
                            'token' => $mediaToken,
                        ],
                    ],
                ],
            ]);

        $response->throw();

        $messageId = $response->json('message.body.mid');

        if (! is_string($messageId) || $messageId === '') {
            throw new UnexpectedValueException('MAX did not return a message ID');
        }

        return $messageId;
    }
}
