<?php

use App\Models\Camera;
use App\Models\CameraEvent;
use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        $queueConnection = config('queue.connections.database.connection');
        $queueTable = config('queue.connections.database.table', 'jobs');
        $failedQueueConnection = config('queue.failed.database');
        $failedQueueTable = config('queue.failed.table', 'failed_jobs');

        return [
            'cameras_total' => Camera::query()->count(),
            'cameras_active' => Camera::query()->where('is_active', true)->count(),
            'clients_total' => Client::query()->count(),
            'events_pending' => CameraEvent::query()->where('status', 'pending')->count(),
            'events_skipped' => CameraEvent::query()->where('status', 'skipped')->count(),
            'events_completed' => CameraEvent::query()->where('status', 'completed')->count(),
            'events_failed' => CameraEvent::query()->where('status', 'failed')->count(),
            'queue_jobs' => DB::connection(is_string($queueConnection) ? $queueConnection : null)
                ->table(is_string($queueTable) ? $queueTable : 'jobs')
                ->count(),
            'queue_failed_jobs' => DB::connection(is_string($failedQueueConnection) ? $failedQueueConnection : null)
                ->table(is_string($failedQueueTable) ? $failedQueueTable : 'failed_jobs')
                ->count(),
        ];
    }

    /** @return Collection<int, CameraEvent> */
    #[Computed]
    public function recentEvents(): Collection
    {
        return CameraEvent::query()
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:heading level="1" size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:spacer />
        <flux:button :href="route('events.index')" variant="primary" size="sm" wire:navigate>
            {{ __('View event history') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Cameras') }}</flux:text>
            <flux:heading size="xl">{{ $this->stats['cameras_total'] }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Active cameras') }}</flux:text>
            <flux:heading size="xl">{{ $this->stats['cameras_active'] }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Clients') }}</flux:text>
            <flux:heading size="xl">{{ $this->stats['clients_total'] }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Pending events') }}</flux:text>
            <flux:heading size="xl">{{ $this->stats['events_pending'] }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Skipped events') }}</flux:text>
            <flux:heading size="xl">{{ $this->stats['events_skipped'] }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Completed events') }}</flux:text>
            <flux:heading size="xl">{{ $this->stats['events_completed'] }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Failed events') }}</flux:text>
            <flux:heading size="xl">{{ $this->stats['events_failed'] }}</flux:heading>
        </div>
    </div>

    <div>
        <flux:heading size="lg">{{ __('Queue health') }}</flux:heading>

        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text>{{ __('Queued jobs') }}</flux:text>
                <flux:heading size="xl">{{ $this->stats['queue_jobs'] }}</flux:heading>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text>{{ __('Failed queue jobs') }}</flux:text>
                <flux:heading size="xl">{{ $this->stats['queue_failed_jobs'] }}</flux:heading>
            </div>
        </div>
    </div>

    <div>
        <flux:heading size="lg">{{ __('Recent events') }}</flux:heading>

        @if ($this->recentEvents->isNotEmpty())
            <div class="mt-3 [--flux-bleed:1rem]">
                <flux:table bleed>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Received at') }}</flux:table.column>
                        <flux:table.column>{{ __('Camera name') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Delivery') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->recentEvents as $event)
                            <flux:table.row>
                                <flux:table.cell>{{ $event->created_at->format('d.m.Y H:i:s') }}</flux:table.cell>
                                <flux:table.cell>{{ $event->camera_name }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge
                                        color="{{ match ($event->status) {
                                            'completed' => 'green',
                                            'failed' => 'red',
                                            'skipped' => 'amber',
                                            default => 'blue',
                                        } }}"
                                        size="sm"
                                    >
                                        {{ __(ucfirst($event->status)) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $event->recipients_sent }} / {{ $event->recipients_total }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @else
            <flux:callout
                class="mt-3"
                variant="secondary"
                icon="information-circle"
                heading="{{ __('No recent events...') }}"
            />
        @endif
    </div>
</div>
