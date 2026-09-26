<?php

use App\Models\Camera;
use App\Models\Client;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Клиенты')] class extends Component {
    use WithPagination;

    public string $name = '';

    public string $max_chat_id = '';

    public ?int $editingClientId = null;

    public string $editName = '';

    public string $editMaxChatId = '';

    public ?int $deletingClientId = null;

    public string $deletingClientName = '';

    /** @var array<int> */
    public array $editCameraIds = [];

    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Client::query()->withCount('cameras')->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function cameras(): LengthAwarePaginator
    {
        return Camera::query()->orderBy('name')->paginate(10, pageName: 'camerasPage');
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

    public function startEditing(int $clientId): void
    {
        $client = Client::findOrFail($clientId);

        $this->editCameraIds = $client->cameras()->pluck('cameras.id')->all();

        $this->editingClientId = $client->id;
        $this->editName = $client->name;
        $this->editMaxChatId = (string) $client->max_chat_id;
    }

    public function updateClient(): void
    {
        $client = Client::findOrFail($this->editingClientId);

        $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editMaxChatId' => ['required', 'integer', Rule::unique('clients', 'max_chat_id')->ignore($client->id)],
            'editCameraIds' => ['array'],
            'editCameraIds.*' => ['integer', 'exists:cameras,id'],
        ]);

        $client->update([
            'name' => $this->editName,
            'max_chat_id' => $this->editMaxChatId,
        ]);

        $client->cameras()->sync($this->editCameraIds);

        $this->reset('editingClientId', 'editName', 'editMaxChatId', 'editCameraIds');

        Flux::modal('edit-client')->close();
    }

    public function startDeleting(int $clientId): void
    {
        $client = Client::findOrFail($clientId);

        $this->deletingClientId = $client->id;

        $this->deletingClientName = $client->name;
    }

    public function deleteClient(): void
    {
        $client = Client::findOrFail($this->deletingClientId);

        $client->delete();

        $this->deletingClientId = null;

        $this->deletingClientName = '';

        Flux::modal('delete-client')->close();
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
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->clients as $client)
                        <flux:table.row>
                            <flux:table.cell>{{ $this->clients->firstItem() + $loop->index }}</flux:table.cell>
                            <flux:table.cell>{{ $client->name }}</flux:table.cell>
                            <flux:table.cell>{{ $client->max_chat_id }}</flux:table.cell>
                            <flux:table.cell>{{ $client->cameras_count }}</flux:table.cell>
                            <flux:table.cell>
                                {{-- триггер редактирования клиента --}}
                                <flux:modal.trigger name="edit-client">
                                    <flux:button wire:click='startEditing({{ $client->id }})'
                                        tooltip="{{ __('Edit') }}" variant="subtle" class="cursor-pointer"
                                        icon="pencil-square" size="sm" />
                                </flux:modal.trigger>
                                {{-- триггер удаления клиента --}}
                                <flux:modal.trigger name="delete-client">
                                    <flux:button wire:click="startDeleting({{ $client->id }})"
                                        tooltip="{{ __('Delete') }}" variant="subtle" class="cursor-pointer"
                                        icon="trash" size="sm" />
                                </flux:modal.trigger>
                            </flux:table.cell>
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

    {{-- Модалка редактирования клиента --}}
    <form wire:submit="updateClient">
        <flux:modal :closable="false" name="edit-client">
            <div class="space-y-6">
                <flux:heading size="lg">
                    {{ __('Edit the client') }}
                </flux:heading>

                <flux:input wire:model="editName" label="{{ __('Name') }}" />

                <flux:input wire:model="editMaxChatId" label="{{ __('MAX chat id') }}" />

                @if ($this->cameras->isNotEmpty())
                    <div>
                        <flux:table bleed>
                            <flux:table.columns>
                                <flux:table.column>{{ __('Position') }}</flux:table.column>
                                <flux:table.column>{{ __('Camera name') }}</flux:table.column>
                                <flux:table.column>{{ __('Append') }}</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($this->cameras as $camera)
                                    <flux:table.row>
                                        <flux:table.cell>{{ $this->cameras->firstItem() + $loop->index }}
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $camera->name }}</flux:table.cell>
                                        <flux:table.cell class="text-center">
                                            <flux:checkbox value="{{ $camera->id }}"
                                                class="inline-block align-middle" wire:model="editCameraIds" />
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                    <flux:pagination :paginator="$this->cameras" />
                @endif

                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary" class="cursor-pointer">
                        {{ __('Save') }}
                    </flux:button>

                    <flux:modal.close>
                        <flux:button class="cursor-pointer">
                            {{ __('Close') }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    </form>

    {{-- Модалка удаления клиента --}}
    <form wire:submit="deleteClient">
        <flux:modal :closable="false" name="delete-client">
            <div class="space-y-6">
                <flux:text class="text-lg font-bold">
                    {{ __('Are you sure you want to delete the client :name?', [
                        'name' => $deletingClientName,
                    ]) }}
                </flux:text>

                <flux:text class="text-lg">
                    {{ __('Recovery will be impossible') }}
                </flux:text>

                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:button type="submit" variant="danger" class="cursor-pointer">
                        {{ __('Delete') }}
                    </flux:button>
                    <flux:modal.close>
                        <flux:button class="cursor-pointer">
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    </form>

</div>
