<?php

use App\Models\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Клиенты')] class extends Component {
    use WithPagination;

    public string $name = '';

    public string $max_chat_id = '';

    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Client::query()->withCount('cameras')->orderBy('name')->paginate(10);
    }

    public function createClient(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_chat_id' => ['required', 'integer', 'unique:clients,max_chat_id'],
        ]);

        Client::create([
            'name' => $this->name,
            'max_chat_id' => $this->max_chat_id,
        ]);

        $this->reset('name', 'max_chat_id');
    }
};
?>

<div>
    <flux:heading level="1" size="xl">
        {{ __('Clients') }}
    </flux:heading>

    @if ($this->clients->isNotEmpty())
        <div class="mt-5 [--flux-bleed:1rem]">
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>{{ __('Position') }}</flux:table.column>
                    <flux:table.column>{{ __('Client name') }}</flux:table.column>
                    <flux:table.column>{{ __('MAX chat id') }}</flux:table.column>
                    <flux:table.column>{{ __('Assigned cameras') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->clients as $client)
                        <flux:table.row>
                            <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                            <flux:table.cell>{{ $client->name }}</flux:table.cell>
                            <flux:table.cell>{{ $client->max_chat_id }}</flux:table.cell>
                            <flux:table.cell>{{ $client->cameras_count }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
        <flux:pagination :paginator="$this->clients" />
    @else
        <flux:callout class="mt-5" variant="secondary" icon="information-circle"
            heading="{{ __('The list of clients is empty...') }}" />
    @endif

    {{-- Модалка добавления клиента --}}
    <div class="mt-3">
        <flux:modal.trigger name="create-client">
            <flux:button variant="primary" size="sm" class="cursor-pointer" icon:trailing="plus">
                {{ __('Add') }}
            </flux:button>
        </flux:modal.trigger>

        <form wire:submit='createClient'>
            <flux:modal name="create-client" flyout>
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Adding a client') }}</flux:heading>
                        <flux:text class="mt-2">{{ __('Create a new client.') }}</flux:text>
                    </div>
                    <flux:input wire:model="name" label="{{ __('Client name') }}"
                        placeholder="{{ __('Client name') }}" />

                    <flux:input wire:model="max_chat_id" label="{{ __('MAX chat id') }}"
                        placeholder="{{ __('MAX chat id') }}" />

                    <div class="flex">
                        <flux:spacer />
                        <flux:button size="sm" class="cursor-pointer" type="submit" variant="primary">
                            {{ __('Create') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        </form>
    </div>
</div>
