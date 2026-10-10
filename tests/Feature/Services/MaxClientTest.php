<?php

namespace Tests\Feature\Services;

use App\Services\MaxClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class MaxClientTest extends TestCase
{
    public function test_it_creates_an_image_upload_url(): void
    {
        Http::preventStrayRequests();

        config([
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'testToken',
        ]);

        Http::fake([
            'https://max.test/uploads*' => Http::response(
                ['url' => 'https://upload.test/image'],
                200,
            ),
        ]);

        $client = new MaxClient;

        $uploadUrl = $client->createImageUploadUrl();

        $this->assertSame(
            'https://upload.test/image',
            $uploadUrl,
        );

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === 'https://max.test/uploads?type=image'
                && $request->hasHeader('Authorization', 'testToken'),
        );
    }

    public function test_it_throws_when_image_upload_url_request_fails(): void
    {
        Http::preventStrayRequests();

        config([
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'testToken',
        ]);

        Http::fake([
            'https://max.test/uploads*' => Http::response(
                ['url' => 'https://upload.test/image'],
                401,
            ),
        ]);

        $client = new MaxClient;

        $this->expectException(RequestException::class);

        $client->createImageUploadUrl();
    }

    public function test_it_rejects_a_successful_response_without_an_upload_url(): void
    {
        Http::preventStrayRequests();

        config([
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'testToken',
        ]);

        Http::fake([
            'https://max.test/uploads*' => Http::response(
                ['test' => 'https://upload.test/image'],
                200,
            ),
        ]);

        $client = new MaxClient;

        $this->expectException(UnexpectedValueException::class);

        $client->createImageUploadUrl();
    }

    public function test_it_uploads_an_image_and_returns_its_media_token(): void
    {
        Http::preventStrayRequests();

        config(['services.max.token' => 'test_token']);

        $uploadUrl = 'https://upload.test/image?key=test';

        $jpegBytes = 'fake-jpeg-content';

        $expectedMediaToken = 'test-media-token';

        Http::fake([
            $uploadUrl => Http::response(
                ['photos' => [
                    [
                        'token' => $expectedMediaToken,
                    ],
                ], ]),
        ]);

        $client = new MaxClient;

        $actualMediaToken = $client->uploadImage(
            $uploadUrl,
            $jpegBytes,
        );

        $this->assertSame(
            $expectedMediaToken,
            $actualMediaToken,
        );

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === $uploadUrl && $request->hasHeader('Authorization', 'test_token')
        && $request->hasFile('data', $jpegBytes, 'snapshot.jpg'));
    }

    public function test_it_throws_when_image_upload_fails(): void
    {
        Http::preventStrayRequests();

        config(['services.max.token' => 'test_token']);

        $uploadUrl = 'https://upload.test/image?key=test';

        $jpegBytes = 'fake-jpeg-content';

        Http::fake([
            $uploadUrl => Http::response(
                ['test' => 'https://upload.test/image'],
                500,
            ),
        ]);

        $client = new MaxClient;

        $this->expectException(RequestException::class);

        $client->uploadImage(
            $uploadUrl,
            $jpegBytes,
        );
    }

    public function test_it_rejects_a_successful_upload_without_a_media_token(): void
    {
        Http::preventStrayRequests();

        config(['services.max.token' => 'test_token']);

        $uploadUrl = 'https://upload.test/image?key=test';

        $jpegBytes = 'fake-jpeg-content';

        Http::fake([
            $uploadUrl => Http::response(
                ['photos' => []],
                200,
            ),
        ]);

        $client = new MaxClient;

        $this->expectException(UnexpectedValueException::class);

        $client->uploadImage(
            $uploadUrl,
            $jpegBytes,
        );
    }

    public function test_it_sends_an_image_message_and_returns_its_id(): void
    {
        Http::preventStrayRequests();

        config([
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'test_token',
        ]);

        $chatId = 12345;
        $mediaToken = 'test-media-token';
        $text = 'Test notification';
        $expectedMessageId = 'test-message-id';

        $expectedBody = [
            'text' => $text,
            'attachments' => [
                [
                    'type' => 'image',
                    'payload' => [
                        'token' => $mediaToken,
                    ],
                ],
            ],
        ];

        Http::fake([
            'https://max.test/messages*' => Http::response([
                'message' => [
                    'body' => [
                        'mid' => $expectedMessageId,
                    ],
                ],
            ]),
        ]);

        $client = new MaxClient;

        $actualMessageId = $client->sendImageMessage(
            $chatId,
            $mediaToken,
            $text,
        );

        $this->assertSame($expectedMessageId, $actualMessageId);

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === 'https://max.test/messages?chat_id=12345'
                && $request->hasHeader('Authorization', 'test_token')
                && $request->isJson()
                && $request->data() === $expectedBody,
        );
    }

    public function test_it_throws_when_sending_an_image_message_fails(): void
    {
        Http::preventStrayRequests();

        config([
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'test_token',
        ]);

        Http::fake([
            'https://max.test/messages*' => Http::response([], 500),
        ]);

        $client = new MaxClient;

        $this->expectException(RequestException::class);

        $client->sendImageMessage(
            12345,
            'test-media-token',
            'Test notification',
        );
    }

    public function test_it_rejects_a_successful_message_response_without_an_id(): void
    {
        Http::preventStrayRequests();

        config([
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'test_token',
        ]);

        Http::fake([
            'https://max.test/messages*' => Http::response([
                'message' => [
                    'body' => [],
                ],
            ]),
        ]);

        $client = new MaxClient;

        $this->expectException(UnexpectedValueException::class);

        $client->sendImageMessage(
            12345,
            'test-media-token',
            'Test notification',
        );
    }
}
