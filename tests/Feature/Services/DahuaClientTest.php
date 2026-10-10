<?php

namespace Tests\Feature\Services;

use App\Services\DahuaClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class DahuaClientTest extends TestCase
{
    public function test_it_retrieves_a_snapshot_for_the_requested_channel(): void
    {
        Http::preventStrayRequests();

        config([
            'services.dahua.base_url' => 'http://nvr.test',
            'services.dahua.username' => 'test-user',
            'services.dahua.password' => 'test-password',
        ]);

        Http::fake([
            'http://nvr.test/cgi-bin/snapshot.cgi*' => Http::response(
                'fake-jpeg-content',
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);

        $client = new DahuaClient;

        $snapshot = $client->snapshot(13);

        $this->assertSame(
            'fake-jpeg-content',
            $snapshot,
        );

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'GET'
                && $request->url() === 'http://nvr.test/cgi-bin/snapshot.cgi?channel=13',
        );
    }

    public function test_it_throws_when_snapshot_request_fails(): void
    {
        Http::preventStrayRequests();

        config([
            'services.dahua.base_url' => 'http://nvr.test',
            'services.dahua.username' => 'test-user',
            'services.dahua.password' => 'test-password',
        ]);

        Http::fake([
            'http://nvr.test/cgi-bin/snapshot.cgi*' => Http::response(
                'fake-jpeg-content',
                401,
                ['Content-Type' => 'text/plain'],
            ),
        ]);

        $client = new DahuaClient;

        $this->expectException(RequestException::class);

        $client->snapshot(13);
    }

    public function test_it_rejects_a_successful_response_that_is_not_a_jpeg(): void
    {
        Http::preventStrayRequests();

        config([
            'services.dahua.base_url' => 'http://nvr.test',
            'services.dahua.username' => 'test-user',
            'services.dahua.password' => 'test-password',
        ]);

        Http::fake([
            'http://nvr.test/cgi-bin/snapshot.cgi*' => Http::response(
                'fake-jpeg-content',
                200,
                ['Content-Type' => 'text/plain'],
            ),
        ]);

        $client = new DahuaClient;

        $this->expectException(UnexpectedValueException::class);

        $client->snapshot(13);

    }
}
