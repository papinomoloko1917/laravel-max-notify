<?php

namespace Tests\Unit;

use App\Services\NotificationWindow;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class NotificationWindowTest extends TestCase
{
    public function test_overnight_window_allows_time_after_start(): void
    {
        $window = new NotificationWindow('21:00:00', '06:00:00');

        $afterStart = CarbonImmutable::create(
            2026,
            9,
            27,
            22,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertTrue($window->allows($afterStart));
    }

    public function test_overnight_window_allows_time_after_midnight(): void
    {
        $window = new NotificationWindow('21:00:00', '06:00:00');

        $afterMidnight = CarbonImmutable::create(
            2026,
            9,
            28,
            2,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertTrue($window->allows($afterMidnight));
    }

    public function test_overnight_window_rejects_time_outside_window(): void
    {
        $window = new NotificationWindow('21:00:00', '06:00:00');

        $outsideWindow = CarbonImmutable::create(
            2026,
            9,
            28,
            12,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertFalse($window->allows($outsideWindow));
    }

    public function test_daytime_window_rejects_time_at_end(): void
    {
        $window = new NotificationWindow('08:00:00', '20:00:00');

        $atEnd = CarbonImmutable::create(
            2026,
            9,
            28,
            20,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertFalse($window->allows($atEnd));
    }

    public function test_daytime_window_allows_time_at_start(): void
    {
        $window = new NotificationWindow('08:00:00', '20:00:00');

        $atStart = CarbonImmutable::create(
            2026,
            9,
            28,
            8,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertTrue($window->allows($atStart));
    }

    public function test_daytime_window_allows_time_inside_window(): void
    {
        $window = new NotificationWindow('08:00:00', '20:00:00');

        $insideWindow = CarbonImmutable::create(
            2026,
            9,
            28,
            12,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertTrue($window->allows($insideWindow));
    }

    public function test_empty_window_allows_any_time(): void
    {
        $window = new NotificationWindow(null, null);

        $anyTime = CarbonImmutable::create(
            2026,
            9,
            28,
            12,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertTrue($window->allows($anyTime));
    }

    public function test_window_with_only_start_rejects_time(): void
    {
        $window = new NotificationWindow('21:00:00', null);

        $anyTime = CarbonImmutable::create(
            2026,
            9,
            28,
            22,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertFalse($window->allows($anyTime));
    }

    public function test_window_with_only_end_rejects_time(): void
    {
        $window = new NotificationWindow(null, '06:00:00');

        $anyTime = CarbonImmutable::create(
            2026,
            9,
            28,
            2,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertFalse($window->allows($anyTime));
    }

    public function test_equal_start_and_end_rejects_time(): void
    {
        $window = new NotificationWindow('08:00:00', '08:00:00');

        $anyTime = CarbonImmutable::create(
            2026,
            9,
            28,
            12,
            0,
            0,
            'Europe/Moscow',
        );

        $this->assertFalse($window->allows($anyTime));
    }
}
