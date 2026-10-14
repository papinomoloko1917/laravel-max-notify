<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Models\CameraEvent;
use App\Services\DahuaClient;
use App\Services\MaxClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(DahuaClient $dahuaClient, MaxClient $maxClient): void
    {
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

        $jpeg = $dahuaClient->snapshot($this->channel);

        $uploadUrl = $maxClient->createImageUploadUrl();

        $mediaToken = $maxClient->uploadImage($uploadUrl, $jpeg);

        $notificationText = "Событие камеры: {$camera->name}";

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
}
