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
}
