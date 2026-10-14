<?php

use App\Models\Camera;
use App\Models\CameraEvent;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee(__('Dashboard'));
    $response->assertSee(__('View event history'));
});

test('dashboard shows current operational counts', function () {
    Camera::factory()->count(2)->create(['is_active' => true]);
    Camera::factory()->create(['is_active' => false]);
    Client::factory()->count(2)->create();

    CameraEvent::factory()->create([
        'camera_id' => null,
        'status' => 'pending',
    ]);
    CameraEvent::factory()->count(2)->create([
        'camera_id' => null,
        'status' => 'skipped',
    ]);
    CameraEvent::factory()->count(3)->create([
        'camera_id' => null,
        'status' => 'completed',
    ]);
    CameraEvent::factory()->count(4)->create([
        'camera_id' => null,
        'status' => 'failed',
    ]);

    DB::table('jobs')->insert([
        [
            'queue' => 'default',
            'payload' => 'sensitive queued payload',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ],
        [
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 1,
            'reserved_at' => now()->timestamp,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ],
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => fake()->uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => 'sensitive failed payload',
        'exception' => 'sensitive exception details',
        'failed_at' => now(),
    ]);

    $component = Livewire::test('pages::dashboard.index');

    expect($component->get('stats'))->toBe([
        'cameras_total' => 3,
        'cameras_active' => 2,
        'clients_total' => 2,
        'events_pending' => 1,
        'events_skipped' => 2,
        'events_completed' => 3,
        'events_failed' => 4,
        'queue_jobs' => 2,
        'queue_failed_jobs' => 1,
    ]);

    $component
        ->assertSee(__('Cameras'))
        ->assertSee(__('Active cameras'))
        ->assertSee(__('Clients'))
        ->assertSee(__('Pending events'))
        ->assertSee(__('Skipped events'))
        ->assertSee(__('Completed events'))
        ->assertSee(__('Failed events'))
        ->assertSee(__('Queue health'))
        ->assertSee(__('Queued jobs'))
        ->assertSee(__('Failed queue jobs'))
        ->assertDontSee('sensitive queued payload')
        ->assertDontSee('sensitive failed payload')
        ->assertDontSee('sensitive exception details');
});

test('dashboard shows only five newest events in order', function () {
    foreach (range(1, 6) as $number) {
        CameraEvent::factory()->create([
            'camera_id' => null,
            'camera_name' => sprintf('Dashboard event %02d', $number),
        ]);
    }

    Livewire::test('pages::dashboard.index')
        ->assertSeeInOrder([
            'Dashboard event 06',
            'Dashboard event 05',
            'Dashboard event 04',
            'Dashboard event 03',
            'Dashboard event 02',
        ])
        ->assertDontSee('Dashboard event 01');
});

test('dashboard shows empty state when no events exist', function () {
    Livewire::test('pages::dashboard.index')
        ->assertSee(__('No recent events...'));
});
