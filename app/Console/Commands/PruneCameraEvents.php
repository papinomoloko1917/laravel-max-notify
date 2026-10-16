<?php

namespace App\Console\Commands;

use App\Models\CameraEvent;
use Illuminate\Console\Command;

class PruneCameraEvents extends Command
{
    protected $signature = 'camera-events:prune';

    protected $description = 'Delete processed camera events older than the configured retention period';

    public function handle(): int
    {
        $retentionDays = (int) config('camera_events.retention_days');

        if ($retentionDays < 1) {
            $this->error('Camera event retention must be at least one day.');

            return self::FAILURE;
        }

        $deleted = CameraEvent::query()
            ->whereIn('status', CameraEvent::TERMINAL_STATUSES)
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->delete();

        $this->info("Deleted {$deleted} processed camera event(s).");

        return self::SUCCESS;
    }
}
