<?php

namespace Tests\Feature\Models;

use App\Models\MaxBotSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaxBotSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_is_encrypted_at_rest_and_hidden_from_serialization(): void
    {
        $setting = MaxBotSetting::create([
            'id' => MaxBotSetting::CURRENT_ID,
            'token' => 'secret-token',
            'bot_user_id' => 12345,
            'bot_name' => 'Notify Bot',
            'bot_username' => 'notify_bot',
        ]);

        $storedToken = DB::table('max_bot_settings')
            ->where('id', $setting->id)
            ->value('token');

        $this->assertIsString($storedToken);
        $this->assertNotSame('secret-token', $storedToken);
        $this->assertSame('secret-token', $setting->refresh()->token);
        $this->assertArrayNotHasKey('token', $setting->toArray());
    }
}
