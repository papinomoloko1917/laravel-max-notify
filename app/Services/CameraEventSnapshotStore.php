<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CameraEventSnapshotStore
{
    public function put(int $cameraEventId, string $jpeg): void
    {
        $stored = Storage::disk('local')->put($this->path($cameraEventId), $jpeg);

        if ($stored === false) {
            throw new RuntimeException('Unable to store the Camera event snapshot.');
        }
    }

    public function get(int $cameraEventId): string
    {
        $jpeg = Storage::disk('local')->get($this->path($cameraEventId));

        if (! is_string($jpeg)) {
            throw new RuntimeException('The Camera event snapshot is unavailable.');
        }

        return $jpeg;
    }

    public function delete(int $cameraEventId): void
    {
        $path = $this->path($cameraEventId);
        $disk = Storage::disk('local');

        if ($disk->exists($path) && $disk->delete($path) === false) {
            Log::warning('Camera event snapshot cleanup failed', [
                'camera_event_id' => $cameraEventId,
            ]);
        }
    }

    public function path(int $cameraEventId): string
    {
        return "camera-event-snapshots/{$cameraEventId}.jpg";
    }
}
