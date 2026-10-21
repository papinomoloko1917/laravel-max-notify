<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Models\CameraEvent;
use App\Services\CameraEventSnapshotStore;
use App\Services\MaxClient;
use App\Services\MaxNotificationMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessCameraEvent implements ShouldQueue
{
    use Queueable;

    public int $timeout = 80;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $cameraId,
        public int $channel,
        public int $cameraEventId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        CameraEventSnapshotStore $snapshotStore,
        MaxClient $maxClient,
        MaxNotificationMessage $notificationMessage,
    ): void {
        try {
            $this->process($snapshotStore, $maxClient, $notificationMessage);
        } finally {
            $snapshotStore->delete($this->cameraEventId);
        }
    }

    private function process(
        CameraEventSnapshotStore $snapshotStore,
        MaxClient $maxClient,
        MaxNotificationMessage $notificationMessage,
    ): void {
        $cameraEvent = CameraEvent::findOrFail($this->cameraEventId);

        $camera = Camera::query()
            ->with('clients')
            ->whereKey($this->cameraId)
            ->first();

        if ($camera === null) {
            $cameraEvent->update([
                'status' => 'skipped',
                'failure_type' => 'camera_missing',
                'processed_at' => now(),
            ]);

            return;
        }

        if ($camera->is_active === false) {
            $cameraEvent->update([
                'status' => 'skipped',
                'failure_type' => 'camera_inactive',
                'processed_at' => now(),
            ]);

            return;
        }

        $clients = $camera->clients;

        if ($clients->isEmpty()) {
            $cameraEvent->update([
                'status' => 'skipped',
                'failure_type' => 'no_recipients',
                'processed_at' => now(),
            ]);

            return;
        }

        $cameraEvent->update([
            'recipients_total' => $clients->count(),
        ]);

        $jpeg = $snapshotStore->get($this->cameraEventId);

        $uploadUrl = $maxClient->createImageUploadUrl();

        $mediaToken = $maxClient->uploadImage($uploadUrl, $jpeg);

        $notificationText = $notificationMessage->format($cameraEvent);

        $i = 0;

        foreach ($clients as $client) {
            $maxClient->sendImageMessage(
                $client->max_chat_id,
                $mediaToken,
                $notificationText,
            );

            $cameraEvent->update([
                'recipients_sent' => ++$i,
            ]);
        }

        $cameraEvent->update([
            'status' => 'completed',
            'failure_type' => null,
            'processed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        app(CameraEventSnapshotStore::class)->delete($this->cameraEventId);

        $cameraEvent = CameraEvent::find($this->cameraEventId);

        if ($cameraEvent === null) {
            return;
        }

        $cameraEvent->update([
            'status' => 'failed',
            'failure_type' => $exception === null ? 'unknown' : $exception::class,
            'processed_at' => now(),
        ]);
    }
}
