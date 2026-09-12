<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_client(): void
    {
        $clients = Client::factory()->count(3)->create();

        $maxChatId = $clients->pluck('max_chat_id');

        $allCount = $maxChatId->count();
        $uniqueCount = $maxChatId->unique()->count();

        $this->assertSame($allCount, $uniqueCount);
    }
}
