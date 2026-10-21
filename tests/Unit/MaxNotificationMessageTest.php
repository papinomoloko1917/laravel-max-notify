<?php

namespace Tests\Unit;

use App\Models\CameraEvent;
use App\Services\MaxNotificationMessage;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class MaxNotificationMessageTest extends TestCase
{
    public function test_it_formats_event_snapshot_data_and_acceptance_time(): void
    {
        config(['app.timezone' => 'Europe/Moscow']);

        $event = new CameraEvent([
            'camera_name' => 'Цех (техника)',
            'rule' => 'CrossLineDetection',
            'rule_title' => 'Пересечение линии',
        ]);
        $event->created_at = CarbonImmutable::parse('2026-09-16 06:55:42', 'Europe/Moscow');

        $message = (new MaxNotificationMessage)->format($event);

        $this->assertSame(
            "🚨 Пересечение линии\n\n📍 Цех (техника)\n🕒 16.09 06:55:42 МСК",
            $message,
        );
    }

    public function test_it_uses_raw_rule_when_event_has_no_configured_title(): void
    {
        $event = new CameraEvent([
            'camera_name' => 'Камера 1',
            'rule' => 'UnknownRule',
            'rule_title' => null,
        ]);
        $event->created_at = CarbonImmutable::parse('2026-10-15 10:00:00', 'Europe/Moscow');

        $message = (new MaxNotificationMessage)->format($event);

        $this->assertStringContainsString('🚨 UnknownRule', $message);
    }
}
