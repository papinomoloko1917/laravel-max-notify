<?php

use App\Models\CameraEvent;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('История событий')] class extends Component
{
    use WithPagination;

    public array $filterOptions = [
        'all' => 'All',
        'pending' => 'Pending',
        'skipped' => 'Skipped',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ];

    public string $filter = 'all';

    #[Computed]
    public function events(): LengthAwarePaginator
    {
        return CameraEvent::query()
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }
};
?>

<div>
    <flux:heading level="1" size="xl">{{ __('The history of events') }}</flux:heading>

    <flux:label class="mt-5">{{ __('Filter') }}:</flux:label>
    <flux:dropdown>
        <flux:button class="cursor-pointer" size="xs" icon:trailing="chevron-down">
            {{ __($filterOptions[$filter]) }}
        </flux:button>

        <flux:menu>
            <flux:menu.radio.group wire:model.live="filter">
                @foreach ($filterOptions as $key => $label)
                    <flux:menu.radio class="cursor-pointer text-xs" value="{{ $key }}">
                        {{ __($label) }}
                    </flux:menu.radio>
                @endforeach
            </flux:menu.radio.group>
        </flux:menu>
    </flux:dropdown>

    @if ($this->events->isNotEmpty())
        <div class="mt-5 [--flux-bleed:1rem]">
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>{{ __('Received at') }}</flux:table.column>
                    <flux:table.column>{{ __('Camera name') }}</flux:table.column>
                    <flux:table.column>{{ __('Channel') }}</flux:table.column>
                    <flux:table.column>{{ __('Rule') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Delivery') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->events as $event)
                        <flux:table.row>
                            <flux:table.cell>{{ $event->created_at->format('d.m.Y H:i:s') }}</flux:table.cell>
                            <flux:table.cell>{{ $event->camera_name }}</flux:table.cell>
                            <flux:table.cell>{{ $event->channel }}</flux:table.cell>
                            <flux:table.cell>{{ $event->rule }}</flux:table.cell>
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

        <flux:pagination :paginator="$this->events" />
    @else
        <flux:callout
            class="mt-5"
            variant="secondary"
            icon="information-circle"
            heading="{{ __($filter === 'all' ? 'The event history is empty...' : 'No events match the selected status...') }}"
        />
    @endif
</div>
