<?php

use App\Models\MaxBotSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('max bot settings require authentication and password confirmation', function () {
    $this->get(route('max-bot.edit'))
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('max-bot.edit'))
        ->assertRedirect(route('password.confirm'));
});

test('max bot settings page displays connected bot without exposing its token', function () {
    $user = User::factory()->create();

    MaxBotSetting::create([
        'id' => MaxBotSetting::CURRENT_ID,
        'token' => 'secret-token',
        'bot_user_id' => 12345,
        'bot_name' => 'Notify Bot',
        'bot_username' => 'notify_bot',
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('max-bot.edit'))
        ->assertOk()
        ->assertSee('Notify Bot')
        ->assertSee('@notify_bot')
        ->assertDontSee('secret-token');
});

test('valid token replaces the global max bot setting', function () {
    Http::preventStrayRequests();

    config(['services.max.base_url' => 'https://max.test']);

    Http::fake([
        'https://max.test/me' => Http::response([
            'user_id' => 67890,
            'name' => 'New Bot',
            'username' => 'new_bot',
            'is_bot' => true,
        ]),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.max-bot')
        ->set('token', 'new-secret-token')
        ->call('updateBot')
        ->assertHasNoErrors()
        ->assertSet('token', '')
        ->assertSet('botName', 'New Bot')
        ->assertSet('botUsername', 'new_bot')
        ->assertSet('botUserId', 67890);

    $setting = MaxBotSetting::findOrFail(MaxBotSetting::CURRENT_ID);

    expect($setting->token)->toBe('new-secret-token')
        ->and($setting->bot_user_id)->toBe(67890)
        ->and($setting->bot_name)->toBe('New Bot')
        ->and($setting->bot_username)->toBe('new_bot')
        ->and(DB::table('max_bot_settings')->where('id', MaxBotSetting::CURRENT_ID)->value('token'))
        ->not->toBe('new-secret-token');

    Http::assertSent(
        fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://max.test/me'
            && $request->hasHeader('Authorization', 'new-secret-token'),
    );
});

test('invalid token does not replace the current bot', function () {
    Http::preventStrayRequests();

    config(['services.max.base_url' => 'https://max.test']);

    $setting = MaxBotSetting::create([
        'id' => MaxBotSetting::CURRENT_ID,
        'token' => 'working-token',
        'bot_user_id' => 12345,
        'bot_name' => 'Current Bot',
        'bot_username' => 'current_bot',
    ]);

    Http::fake([
        'https://max.test/me' => Http::response([], 401),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.max-bot')
        ->set('token', 'invalid-token')
        ->call('updateBot')
        ->assertHasErrors(['token'])
        ->assertSet('token', '');

    expect($setting->refresh()->token)->toBe('working-token')
        ->and($setting->bot_name)->toBe('Current Bot');
});
