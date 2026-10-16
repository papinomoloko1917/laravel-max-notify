<?php

use App\Models\Camera;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Камеры')] class extends Component
{
    use WithPagination;

    public array $filterOptions = [
        'all' => 'All',
        'active' => 'Only active',
        'inactive' => 'Only inactive ones',
    ];

    public string $name = '';

    public string $channel = '';

    public string $snapshotBaseUrl = '';

    public string $snapshotUsername = '';

    public string $snapshotPassword = '';

    public string $webhookUsername = '';

    public string $webhookPassword = '';

    public bool $is_active = true;

    public string $filter = 'all';

    public ?int $editingCameraId = null;

    public string $editName = '';

    public string $editChannel = '';

    public string $editSnapshotBaseUrl = '';

    public string $editSnapshotUsername = '';

    public string $editSnapshotPassword = '';

    public bool $editingCameraHasSnapshotPassword = false;

    public string $editWebhookUsername = '';

    public string $editWebhookPassword = '';

    public bool $editingCameraHasWebhookPassword = false;

    public bool $editIsActive = true;

    public string $editNotifyFrom = '';

    public string $editNotifyUntil = '';

    public ?int $deletingCameraId = null;

    public string $deletingCameraName = '';

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'название камеры',
            'channel' => 'канал регистратора',
            'snapshotBaseUrl' => 'базовый URL устройства',
            'snapshotUsername' => 'логин устройства для снимка',
            'snapshotPassword' => 'пароль устройства для снимка',
            'webhookUsername' => 'логин webhook',
            'webhookPassword' => 'пароль webhook',
            'editName' => 'название камеры',
            'editChannel' => 'канал регистратора',
            'editSnapshotBaseUrl' => 'базовый URL устройства',
            'editSnapshotUsername' => 'логин устройства для снимка',
            'editSnapshotPassword' => 'новый пароль устройства для снимка',
            'editWebhookUsername' => 'логин webhook',
            'editWebhookPassword' => 'новый пароль webhook',
            'editNotifyFrom' => 'начало интервала',
            'editNotifyUntil' => 'конец интервала',
        ];
    }

    public function startEditing(int $cameraId): void
    {
        $camera = Camera::findOrFail($cameraId);

        $this->editingCameraId = $camera->id;

        $this->editName = $camera->name;

        $this->editChannel = $camera->channel === null ? '' : (string) $camera->channel;

        $this->editSnapshotBaseUrl = $camera->snapshot_base_url ?? '';

        $this->editSnapshotUsername = $camera->snapshot_username ?? '';

        $this->editSnapshotPassword = '';

        $this->editingCameraHasSnapshotPassword = is_string($camera->snapshot_password);

        $this->editWebhookUsername = $camera->webhook_username ?? '';

        $this->editWebhookPassword = '';

        $this->editingCameraHasWebhookPassword = is_string($camera->webhook_password);

        $this->editIsActive = $camera->is_active;

        $this->editNotifyFrom = substr($camera->notify_from ?? '', 0, 5);

        $this->editNotifyUntil = substr($camera->notify_until ?? '', 0, 5);
    }

    public function createCamera(): void
    {
        $this->snapshotBaseUrl = rtrim($this->snapshotBaseUrl, '/');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'channel' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('cameras', 'channel')
                    ->where(fn ($query) => $query->where('snapshot_base_url', $this->snapshotBaseUrl)),
            ],
            'snapshotBaseUrl' => ['required', 'url:http,https', 'max:2048', 'regex:/\Ahttps?:\/\/[^\/?#]+\z/i', 'not_regex:/@/'],
            'snapshotUsername' => ['required', 'string', 'max:255'],
            'snapshotPassword' => ['required', 'string', 'max:255'],
            'webhookUsername' => ['nullable', 'required_with:webhookPassword', 'string', 'max:255', 'unique:cameras,webhook_username'],
            'webhookPassword' => ['nullable', 'required_with:webhookUsername', 'string', 'min:12', 'max:72'],
            'is_active' => ['boolean'],
        ]);

        Camera::create([
            'name' => $this->name,
            'channel' => (int) $this->channel,
            'snapshot_base_url' => $this->snapshotBaseUrl,
            'snapshot_username' => $this->snapshotUsername,
            'snapshot_password' => $this->snapshotPassword,
            'webhook_username' => $this->webhookUsername === '' ? null : $this->webhookUsername,
            'webhook_password' => $this->webhookPassword === '' ? null : $this->webhookPassword,
            'is_active' => $this->is_active,
        ]);

        $this->reset(
            'name',
            'channel',
            'snapshotBaseUrl',
            'snapshotUsername',
            'snapshotPassword',
            'webhookUsername',
            'webhookPassword',
        );
    }

    public function updateCamera(): void
    {
        $this->editSnapshotBaseUrl = rtrim($this->editSnapshotBaseUrl, '/');

        $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editChannel' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('cameras', 'channel')
                    ->where(fn ($query) => $query->where('snapshot_base_url', $this->editSnapshotBaseUrl))
                    ->ignore($this->editingCameraId),
            ],
            'editSnapshotBaseUrl' => ['required', 'url:http,https', 'max:2048', 'regex:/\Ahttps?:\/\/[^\/?#]+\z/i', 'not_regex:/@/'],
            'editSnapshotUsername' => ['required', 'string', 'max:255'],
            'editSnapshotPassword' => [
                Rule::requiredIf(! $this->editingCameraHasSnapshotPassword),
                'nullable',
                'string',
                'max:255',
            ],
            'editWebhookUsername' => [
                Rule::requiredIf($this->editingCameraHasWebhookPassword || $this->editWebhookPassword !== ''),
                'nullable',
                'string',
                'max:255',
                Rule::unique('cameras', 'webhook_username')->ignore($this->editingCameraId),
            ],
            'editWebhookPassword' => [
                Rule::requiredIf(! $this->editingCameraHasWebhookPassword && $this->editWebhookUsername !== ''),
                'nullable',
                'string',
                'min:12',
                'max:72',
            ],
            'editIsActive' => ['boolean'],
            'editNotifyFrom' => ['nullable', 'date_format:H:i', 'required_with:editNotifyUntil', 'different:editNotifyUntil'],
            'editNotifyUntil' => ['nullable', 'date_format:H:i', 'required_with:editNotifyFrom'],
        ]);

        $camera = Camera::findOrFail($this->editingCameraId);

        $attributes = [
            'name' => $this->editName,
            'channel' => $this->editChannel === '' ? null : (int) $this->editChannel,
            'snapshot_base_url' => $this->editSnapshotBaseUrl,
            'snapshot_username' => $this->editSnapshotUsername,
            'webhook_username' => $this->editWebhookUsername === '' ? null : $this->editWebhookUsername,
            'is_active' => $this->editIsActive,
            'notify_from' => $this->editNotifyFrom === '' ? null : $this->editNotifyFrom,
            'notify_until' => $this->editNotifyUntil === '' ? null : $this->editNotifyUntil,
        ];

        if ($this->editWebhookPassword !== '') {
            $attributes['webhook_password'] = $this->editWebhookPassword;
        }

        if ($this->editSnapshotPassword !== '') {
            $attributes['snapshot_password'] = $this->editSnapshotPassword;
        }

        $camera->update($attributes);

        $this->reset('editSnapshotPassword', 'editWebhookPassword');

        Flux::modal('edit-camera')->close();
    }

    public function startDeleting(int $cameraId): void
    {
        $camera = Camera::findOrFail($cameraId);

        $this->deletingCameraId = $camera->id;

        $this->deletingCameraName = $camera->name;
    }

    public function deleteCamera(): void
    {
        $camera = Camera::findOrFail($this->deletingCameraId);

        $camera->delete();

        $this->deletingCameraId = null;

        $this->deletingCameraName = '';

        Flux::modal('delete-camera')->close();
    }

    #[Computed]
    public function cameras(): LengthAwarePaginator
    {
        return Camera::query()
            ->when($this->filter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10);
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }
};
?>

<div>
    <flux:heading level="1" size="xl"> {{ __('Cameras') }} </flux:heading>

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
        <div class="mt-5 [--flux-bleed:1rem]">
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>{{ __('Position') }}</flux:table.column>
                    <flux:table.column>{{ __('Camera name') }}</flux:table.column>
                    <flux:table.column>Источник снимка</flux:table.column>
                    <flux:table.column>Канал</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->cameras as $camera)
                        <flux:table.row>
                            <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                            <flux:table.cell>{{ $camera->name }}</flux:table.cell>
                            <flux:table.cell>{{ $camera->snapshot_base_url ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $camera->channel ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="py-0">
                                <flux:badge color="{{ $camera->is_active === true ? 'green' : 'red' }}" size="sm">
                                    {{ $camera->is_active === true ? __('Active') : __('Inactive') }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button
                                    :href="route('cameras.rules', $camera)"
                                    wire:navigate
                                    tooltip="Правила событий"
                                    variant="subtle"
                                    class="cursor-pointer"
                                    icon="list-bullet"
                                    size="sm"
                                />
                                {{-- триггер редактирования камеры --}}
                                <flux:modal.trigger name="edit-camera">
                                    <flux:button
                                        wire:click='startEditing({{ $camera->id }})'
                                        tooltip="{{ __('Edit') }}"
                                        variant="subtle"
                                        class="cursor-pointer"
                                        icon="pencil-square"
                                        size="sm"
                                    />
                                </flux:modal.trigger>
                                {{-- триггер удаления камеры --}}
                                <flux:modal.trigger name="delete-camera">
                                    <flux:button
                                        wire:click="startDeleting({{ $camera->id }})"
                                        tooltip="{{ __('Delete') }}"
                                        variant="subtle"
                                        class="cursor-pointer"
                                        icon="trash"
                                        size="sm"
                                    />
                                </flux:modal.trigger>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
        <flux:pagination :paginator="$this->cameras" />
    @else
        <flux:callout
            class="mt-5"
            variant="secondary"
            icon="information-circle"
            heading="{{ __('The list of cameras is empty...') }}"
        />
    @endif

    {{-- Модалка добавления камеры --}}
    <div class="mt-3">
        <flux:modal.trigger name="create-camera">
            <flux:button variant="primary" size="sm" class="cursor-pointer" icon:trailing="plus">
                {{ __('Add') }}
            </flux:button>
        </flux:modal.trigger>

        <form wire:submit="createCamera">
            <flux:modal name="create-camera" flyout>
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Adding a camera') }}</flux:heading>
                        <flux:text class="mt-2">{{ __('Make the settings for the new camera.') }}</flux:text>
                    </div>
                    <flux:input
                        wire:model="name"
                        label="{{ __('Camera name') }}"
                        placeholder="{{ __('Camera name') }}"
                    />

                    <flux:input
                        wire:model="channel"
                        label="Канал устройства"
                        type="number"
                        min="1"
                        placeholder="1"
                    />

                    <flux:separator text="Получение снимка" />

                    <flux:input
                        wire:model="snapshotBaseUrl"
                        label="Базовый URL устройства"
                        placeholder="http://10.10.0.117"
                        description="Только адрес и порт, без /cgi-bin/snapshot.cgi и channel"
                    />

                    <flux:input
                        wire:model="snapshotUsername"
                        label="Логин устройства для снимка"
                        autocomplete="off"
                        description="Учётная запись на камере или регистраторе; используется Digest-аутентификация"
                    />

                    <flux:input
                        wire:model="snapshotPassword"
                        label="Пароль устройства для снимка"
                        type="password"
                        autocomplete="new-password"
                        description="Хранится в базе в зашифрованном виде и не отображается"
                    />

                    <flux:separator text="Необязательный Basic Auth для webhook" />

                    <flux:input
                        wire:model="webhookUsername"
                        label="Логин webhook"
                        autocomplete="off"
                        description="Только для устройств, которые отправляют HTTP Basic; для URL-токена оставьте пустым"
                    />

                    <flux:input
                        wire:model="webhookPassword"
                        label="Пароль webhook"
                        type="password"
                        autocomplete="new-password"
                        description="Не менее 12 символов; заполняется только вместе с логином Basic Auth"
                    />

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

        {{-- Модалка редактирования камеры --}}
        <form wire:submit="updateCamera">
            <flux:modal :closable="false" name="edit-camera">
                <div class="space-y-6">
                    <flux:heading size="lg"> {{ __('Edit the camera') }} </flux:heading>

                    <flux:input wire:model="editName" label="{{ __('Camera name') }}" />

                    <flux:input
                        wire:model="editChannel"
                        label="Канал устройства"
                        type="number"
                        min="1"
                        description="Нужен для генерации webhook URL и получения снимка"
                    />

                    <flux:separator text="Получение снимка" />

                    <flux:input
                        wire:model="editSnapshotBaseUrl"
                        label="Базовый URL устройства"
                        placeholder="http://10.10.0.117"
                        description="Только адрес и порт, без /cgi-bin/snapshot.cgi и channel"
                    />

                    <flux:input
                        wire:model="editSnapshotUsername"
                        label="Логин устройства для снимка"
                        autocomplete="off"
                        description="Учётная запись на камере или регистраторе; используется Digest-аутентификация"
                    />

                    <flux:input
                        wire:model="editSnapshotPassword"
                        label="Новый пароль устройства для снимка"
                        type="password"
                        autocomplete="new-password"
                        description="Оставьте пустым, чтобы сохранить действующий пароль"
                    />

                    <flux:separator text="Необязательный Basic Auth для webhook" />

                    <flux:input
                        wire:model="editWebhookUsername"
                        label="Логин webhook"
                        autocomplete="off"
                        description="Только для устройств, которые отправляют HTTP Basic; для URL-токена оставьте пустым"
                    />

                    <flux:input
                        wire:model="editWebhookPassword"
                        label="Новый пароль webhook"
                        type="password"
                        autocomplete="new-password"
                        description="Оставьте пустым, чтобы сохранить действующий пароль Basic Auth"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <flux:label class="col-span-2">{{ __('Time interval') }}</flux:label>
                        <flux:input type="time" wire:model="editNotifyFrom" label="{{ __('Beginning') }}" />
                        <flux:input type="time" wire:model="editNotifyUntil" label="{{ __('End') }}" />
                    </div>

                    <flux:field variant="inline">
                        <flux:checkbox wire:model="editIsActive" />
                        <flux:label>{{ __('Activate the device') }}</flux:label>
                    </flux:field>

                    <div class="flex gap-3">
                        <flux:spacer />
                        <flux:button type="submit" variant="primary" class="cursor-pointer">
                            {{ __('Save') }}
                        </flux:button>
                        <flux:modal.close>
                            <flux:button class="cursor-pointer"> {{ __('Close') }} </flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        </form>

        {{-- Модалка удаления камеры --}}
        <form wire:submit="deleteCamera">
            <flux:modal :closable="false" name="delete-camera">
                <div class="space-y-6">
                    <flux:text class="text-lg font-bold">
                        {{
                            __('Are you sure you want to delete the camera :name?', [
                                'name' => $deletingCameraName,
                            ])
                        }}
                    </flux:text>

                    <flux:text class="text-lg"> {{ __('Recovery will be impossible') }} </flux:text>

                    <div class="flex gap-3">
                        <flux:spacer />
                        <flux:button type="submit" variant="danger" class="cursor-pointer">
                            {{ __('Delete') }}
                        </flux:button>
                        <flux:modal.close>
                            <flux:button class="cursor-pointer"> {{ __('Cancel') }} </flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        </form>
    </div>
</div>
