<?php

use App\Models\Camera;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Камеры')] class extends Component
{
    public Collection $cameras;

    public function mount(): void
    {
        $this->cameras = Camera::orderBy('name')->get();
    }
};
?>

<div>
    <flux:heading level="1" size="xl">
        {{ __('Cameras') }}
    </flux:heading>

    @if ($this->cameras->isNotEmpty())
        <div class="[--flux-bleed:1rem]">
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>{{ __('Position') }}</flux:table.column>
                    <flux:table.column>{{ __('Camera name') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->cameras as $camera)
                        <flux:table.row>
                            <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                            <flux:table.cell>{{ $camera->name }}</flux:table.cell>
                            <flux:table.cell class="py-0">
                                <flux:badge color="{{ $camera->is_active === true ? 'green' : 'red' }}" size="sm">
                                    {{ $camera->is_active === true ? __('Active') : __('Inactive') }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @else
        <flux:callout class="mt-5" variant="secondary" icon="information-circle"
            heading="{{ __('The list of cameras is empty...') }}" />
    @endif
</div>
