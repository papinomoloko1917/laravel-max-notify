<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Services\DahuaClient;
use App\Services\MaxClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCameraEvent implements ShouldQueue
{
    use Queueable;

    public int $timeout = 80;

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $cameraId,
        public int $channel,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(DahuaClient $dahuaClient, MaxClient $maxClient): void
    {
        $camera = Camera::query()
            ->with('clients')
            ->whereKey($this->cameraId)
            ->where('is_active', true)
            ->first();

        if ($camera === null) {
            return;
        }

        $clients = $camera->clients;

        if ($clients->isEmpty()) {
            return;
        }

        $jpeg = $dahuaClient->snapshot($this->channel);

        $uploadUrl = $maxClient->createImageUploadUrl();

        $mediaToken = $maxClient->uploadImage($uploadUrl, $jpeg);

        $notificationText = "Событие камеры: {$camera->name}";

        foreach ($clients as $client) {
            $maxClient->sendImageMessage(
                $client->max_chat_id,
                $mediaToken,
                $notificationText,
            );
        }
    }
}
