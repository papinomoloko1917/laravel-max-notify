<?php

namespace App\Services;

use App\Models\CameraEvent;

class MaxNotificationMessage
{
    public function format(CameraEvent $event): string
    {
        $title = $event->rule_title ?? $event->rule;
        $occurredAt = $event->created_at
            ->timezone((string) config('app.timezone'))
            ->format('d.m H:i:s');

        return "🚨 {$title}\n\n📍 {$event->camera_name}\n🕒 {$occurredAt} МСК";
    }
}
