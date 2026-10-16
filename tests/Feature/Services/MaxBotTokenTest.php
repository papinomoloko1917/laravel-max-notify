<?php

namespace Tests\Feature\Services;

use App\Models\MaxBotSetting;
use App\Services\MaxBotToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use UnexpectedValueException;

class MaxBotTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_environment_token_is_used_before_bot_is_saved(): void
    {
        config(['services.max.token' => 'environment-token']);

        $this->assertSame('environment-token', app(MaxBotToken::class)->get());
    }

    public function test_encrypted_database_token_takes_precedence(): void
    {
        config(['services.max.token' => 'environment-token']);

        MaxBotSetting::create([
            'id' => MaxBotSetting::CURRENT_ID,
            'token' => 'database-token',
            'bot_user_id' => 12345,
            'bot_name' => 'Notify Bot',
        ]);

        $this->assertSame('database-token', app(MaxBotToken::class)->get());
    }

    public function test_missing_token_is_rejected_before_an_http_request(): void
    {
        config(['services.max.token' => null]);

        $this->expectException(UnexpectedValueException::class);

        app(MaxBotToken::class)->get();
    }
}
