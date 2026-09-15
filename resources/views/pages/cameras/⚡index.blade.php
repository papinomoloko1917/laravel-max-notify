<?php

use App\Models\Camera;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Камеры')] class extends Component {
    public Collection $cameras;

    public array $filterOptions = [
        'all' => 'All',
        'active' => 'Only active',
        'inactive' => 'Only inactive ones',
    ];

    public string $name = '';

    public bool $is_active = true;

    public string $filter = 'all';

    public function createCamera(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Camera::create([
            'name' => $this->name,
            'is_active' => $this->is_active,
        ]);

        $this->reset('name');

        $this->loadCameras();
    }

    public function mount(): void
    {
        $this->loadCameras();
    }

    private function loadCameras(): void
    {
        $this->cameras = Camera::query()
            ->when($this->filter === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->filter === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->get();
    }

    public function updatedFilter(): void
    {
        $this->loadCameras();
    }
};
?>

<div>
    <flux:heading level="1" size="xl">
        {{ __('Cameras') }}
    </flux:heading>

    {{-- Фильтр --}}
    <flux:label class="mt-5">{{ __('Filter') }}: </flux:label>
    <flux:dropdown>

        <flux:button class="cursor-pointer" size="xs" icon:trailing="chevron-down">
            {{ __($filterOptions[$filter]) }}</flux:button>

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

    @if ($this->cameras->isNotEmpty())
        <div class="[--flux-bleed:1rem] mt-5">
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

    {{-- Модалка --}}
    <div class="mt-3">
        <flux:modal.trigger name="create-camera">
            <flux:button variant="primary" size="sm" class="cursor-pointer" icon:trailing="plus">
                {{ __('Add') }}
            </flux:button>
        </flux:modal.trigger>

        <form wire:submit='createCamera'>
            <flux:modal name="create-camera" flyout>
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Adding a camera') }}</flux:heading>
                        <flux:text class="mt-2">{{ __('Make the settings for the new camera.') }}</flux:text>
                    </div>
                    <flux:input wire:model="name" label="{{ __('Camera name') }}"
                        placeholder="{{ __('Camera name') }}" />

                    {{-- чекбокс --}}
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="is_active" />

                        <flux:label>{{ __('Activate the device') }}</flux:label>

                        <flux:error name="is_active" />
                    </flux:field>

                    <div class="flex">
                        <flux:spacer />
                        <flux:button size="sm" class="cursor-pointer" type="submit" variant="primary">
                            {{ __('Save') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        </form>
    </div>
</div>
