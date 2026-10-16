<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class MaxClient
{
    public function __construct(private readonly MaxBotToken $botToken)
    {
        //
    }

    /**
     * @return array{user_id: int, name: string, username: string|null}
     */
    public function inspectBot(string $token): array
    {
        $baseUrl = (string) config('services.max.base_url');
        $connectTimeout = (int) config('services.max.connect_timeout');
        $timeout = (int) config('services.max.timeout');

        $response = Http::acceptJson()
            ->withHeaders(['Authorization' => $token])
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->get(rtrim($baseUrl, '/') . '/me');

        $response->throw();

        $userId = $response->json('user_id');
        $name = $response->json('name');
        $username = $response->json('username');
        $isBot = $response->json('is_bot');

        if (! is_int($userId) || $userId < 1 || ! is_string($name) || $name === '' || $isBot !== true) {
            throw new UnexpectedValueException('MAX did not return valid bot information');
        }

        if ($username !== null && ! is_string($username)) {
            throw new UnexpectedValueException('MAX did not return a valid bot username');
        }

        return [
            'user_id' => $userId,
            'name' => $name,
            'username' => $username,
        ];
    }

    public function createImageUploadUrl(): string
    {
        $baseUrl = (string) config('services.max.base_url');

        $token = $this->botToken->get();

        $connectTimeout = (int) config('services.max.connect_timeout');

        $timeout = (int) config('services.max.timeout');

        $response = Http::acceptJson()
            ->withHeaders(['Authorization' => $token])
            ->withQueryParameters([
                'type' => 'image',
            ])
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
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
        $token = $this->botToken->get();

        $connectTimeout = (int) config('services.max.connect_timeout');

        $timeout = (int) config('services.max.timeout');

        $response = Http::acceptJson()
            ->withHeaders(['Authorization' => $token])
            ->attach(
                'data',
                $imageContents,
                'snapshot.jpg',
            )
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
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

        $token = $this->botToken->get();

        $connectTimeout = (int) config('services.max.connect_timeout');

        $timeout = (int) config('services.max.timeout');

        $response = Http::acceptJson()
            ->asJson()
            ->withHeaders(['Authorization' => $token])
            ->withQueryParameters(['chat_id' => $chatId])
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
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
