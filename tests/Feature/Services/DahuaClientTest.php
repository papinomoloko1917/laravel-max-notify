<?php

namespace Tests\Feature\Services;

use App\Services\DahuaClient;
use Illuminate\Http\Client\ConnectionException;
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

        Http::fake([
            'http://nvr.test/cgi-bin/snapshot.cgi*' => Http::response(
                'fake-jpeg-content',
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);

        $client = new DahuaClient;

        $snapshot = $client->snapshot(
            'http://nvr.test',
            'test-user',
            'test-password',
            13,
        );

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

        Http::fake([
            'http://nvr.test/cgi-bin/snapshot.cgi*' => Http::response(
                'fake-jpeg-content',
                401,
                ['Content-Type' => 'text/plain'],
            ),
        ]);

        $client = new DahuaClient;

        $this->expectException(RequestException::class);

        $client->snapshot('http://nvr.test', 'test-user', 'test-password', 13);
    }

    public function test_it_rejects_a_successful_response_that_is_not_a_jpeg(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'http://nvr.test/cgi-bin/snapshot.cgi*' => Http::response(
                'fake-jpeg-content',
                200,
                ['Content-Type' => 'text/plain'],
            ),
        ]);

        $client = new DahuaClient;

        $this->expectException(UnexpectedValueException::class);

        $client->snapshot('http://nvr.test', 'test-user', 'test-password', 13);

    }

    public function test_it_throws_when_connection_to_nvr_fails(): void
    {
        Http::preventStrayRequests();

        config([
            'services.dahua.connect_timeout' => 3,
            'services.dahua.timeout' => 10,
        ]);

        Http::fake([
            'http://nvr.test/cgi-bin/snapshot.cgi*' => Http::failedConnection(),
        ]);

        $client = new DahuaClient;

        $this->expectException(ConnectionException::class);

        $client->snapshot('http://nvr.test', 'test-user', 'test-password', 13);
    }
}
