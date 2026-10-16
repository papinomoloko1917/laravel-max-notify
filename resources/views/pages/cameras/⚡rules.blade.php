<?php

use App\Models\Camera;
use App\Models\CameraRule;
use Flux\Flux;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Правила камеры')] class extends Component
{
    public Camera $camera;

    public string $code = '';

    public string $title = '';

    public string $presetCode = '';

    public ?int $editingRuleId = null;

    public string $editCode = '';

    public string $editTitle = '';

    public ?int $deletingRuleId = null;

    public string $deletingRuleTitle = '';

    /** @return Collection<int, CameraRule> */
    #[Computed]
    public function rules(): Collection
    {
        return $this->camera->rules()->orderBy('code')->get();
    }

    /** @return array<string, string> */
    #[Computed]
    public function presets(): array
    {
        /** @var array<string, string> $presets */
        $presets = config('camera_events.presets', []);

        return $presets;
    }

    public function createPresetRule(): void
    {
        $this->validate([
            'presetCode' => ['required', Rule::in(array_keys($this->presets))],
        ]);

        if ($this->camera->rules()->where('code', $this->presetCode)->exists()) {
            $this->addError('presetCode', 'Это событие уже добавлено для камеры.');

            return;
        }

        $this->camera->rules()->create([
            'code' => $this->presetCode,
            'title' => $this->presets[$this->presetCode],
        ]);

        $this->reset('presetCode');

        Flux::toast(variant: 'success', text: 'Событие добавлено.');
    }

    public function createRule(): void
    {
        $validated = $this->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('camera_rules', 'code')
                    ->where(fn (Builder $query): Builder => $query->where('camera_id', $this->camera->id)),
            ],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $this->camera->rules()->create($validated);

        $this->reset('code', 'title');

        Flux::toast(variant: 'success', text: 'Правило добавлено.');
    }

    public function startEditing(int $ruleId): void
    {
        $rule = $this->camera->rules()->findOrFail($ruleId);

        $this->editingRuleId = $rule->id;
        $this->editCode = $rule->code;
        $this->editTitle = $rule->title;
    }

    public function updateRule(): void
    {
        $rule = $this->camera->rules()->findOrFail($this->editingRuleId);

        $validated = $this->validate([
            'editCode' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('camera_rules', 'code')
                    ->where(fn (Builder $query): Builder => $query->where('camera_id', $this->camera->id))
                    ->ignore($rule->id),
            ],
            'editTitle' => ['required', 'string', 'max:255'],
        ]);

        $rule->update([
            'code' => $validated['editCode'],
            'title' => $validated['editTitle'],
        ]);

        $this->reset('editingRuleId', 'editCode', 'editTitle');

        Flux::modal('edit-camera-rule')->close();
    }

    public function startDeleting(int $ruleId): void
    {
        $rule = $this->camera->rules()->findOrFail($ruleId);

        $this->deletingRuleId = $rule->id;
        $this->deletingRuleTitle = $rule->title;
    }

    public function deleteRule(): void
    {
        $rule = $this->camera->rules()->findOrFail($this->deletingRuleId);

        $rule->delete();

        $this->reset('deletingRuleId', 'deletingRuleTitle');

        Flux::modal('delete-camera-rule')->close();
    }

    public function rotateWebhookToken(): void
    {
        $this->camera->update([
            'webhook_token' => Str::random(64),
        ]);

        $this->camera->refresh();

        Flux::modal('rotate-webhook-token')->close();
        Flux::toast(variant: 'success', text: 'Webhook-токен заменён. Обновите URL на устройстве.');
    }
}; ?>

<div>
    <div class="flex items-center gap-3">
        <flux:button :href="route('cameras.index')" wire:navigate icon="arrow-left" variant="subtle" />
        <div>
            <flux:heading level="1" size="xl">Правила камеры</flux:heading>
            <flux:subheading>{{ $camera->name }}</flux:subheading>
        </div>
    </div>

    @if ($camera->channel === null)
        <flux:callout
            class="mt-6 max-w-3xl"
            variant="warning"
            icon="exclamation-triangle"
            heading="У камеры не указан канал регистратора"
        >
            Укажите канал в настройках камеры, чтобы приложение сформировало готовые URL.
        </flux:callout>
    @endif

    <flux:callout
        class="mt-6 max-w-3xl"
        variant="secondary"
        icon="information-circle"
        heading="Настройка HTTP-действия камеры или регистратора"
    >
        <div class="mt-3 space-y-3">
            <flux:text>
                Используйте готовый URL нужного правила и отключите на устройстве переключатель
                <strong>Аутентификация</strong>. URL уже содержит уникальный секретный токен камеры.
                Не публикуйте и не записывайте его в обычные журналы доступа.
            </flux:text>

            @if ($camera->webhook_username !== null && $camera->webhook_password !== null)
                <flux:text>
                    Для устройств, которые корректно отправляют HTTP Basic, прежний способ также поддерживается.
                </flux:text>
                <flux:input
                    :value="$camera->webhook_username"
                    label="Username для необязательного Basic Auth"
                    readonly
                    copyable
                    size="sm"
                />

                <flux:text>
                    Password — значение, которое вы задали в настройках камеры. Сохранённый пароль
                    намеренно не отображается; при необходимости задайте новый через редактирование камеры.
                </flux:text>
            @endif

            <flux:modal.trigger name="rotate-webhook-token">
                <flux:button variant="subtle" icon="arrow-path">Заменить webhook-токен</flux:button>
            </flux:modal.trigger>
        </div>
    </flux:callout>

    @if (in_array(parse_url((string) config('services.dahua.webhook_base_url'), PHP_URL_HOST), ['localhost', '127.0.0.1'], true))
        <flux:callout
            class="mt-6 max-w-3xl"
            variant="warning"
            icon="exclamation-triangle"
            heading="APP_URL недоступен устройству"
        >
            Укажите в DAHUA_WEBHOOK_BASE_URL сетевой адрес Laravel-сервера и выполните
            <code>php artisan config:clear</code>. Камера или регистратор не сможет обратиться к localhost.
        </flux:callout>
    @endif

    <form wire:submit="createPresetRule" class="mt-8 flex max-w-3xl items-end gap-4">
        <flux:select wire:model="presetCode" label="Тип события" class="flex-1">
            <flux:select.option value="">Выберите событие</flux:select.option>
            @foreach ($this->presets as $presetCode => $presetTitle)
                <flux:select.option value="{{ $presetCode }}">{{ $presetTitle }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:button type="submit" variant="primary">Добавить</flux:button>
    </form>

    <flux:separator class="mt-8 max-w-3xl" text="Собственный тип события" />

    <form wire:submit="createRule" class="mt-8 grid max-w-3xl gap-4 md:grid-cols-[1fr_2fr_auto] md:items-end">
        <flux:input
            wire:model="code"
            label="Идентификатор"
            placeholder="custom_event"
            description="Латиница, цифры, дефис или подчёркивание"
        />
        <flux:input
            wire:model="title"
            label="Заголовок уведомления"
            placeholder="Обнаружен транспорт"
        />
        <flux:button type="submit" variant="primary">Добавить</flux:button>
    </form>

    @if ($this->rules->isNotEmpty())
        <div class="mt-8 [--flux-bleed:1rem]">
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>Код rule</flux:table.column>
                    <flux:table.column>Заголовок уведомления</flux:table.column>
                    <flux:table.column>Webhook URL</flux:table.column>
                    <flux:table.column>Действия</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->rules as $rule)
                        <flux:table.row wire:key="camera-rule-{{ $rule->id }}">
                            <flux:table.cell>{{ $rule->code }}</flux:table.cell>
                            <flux:table.cell>{{ $rule->title }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($camera->channel !== null)
                                    <flux:input
                                        :value="rtrim((string) config('services.dahua.webhook_base_url'), '/') . route('dahua.webhook', [
                                            'camera' => $camera->id,
                                            'token' => $camera->webhook_token,
                                            'event' => 'ivs',
                                            'channel' => $camera->channel,
                                            'rule' => $rule->code,
                                        ], false)"
                                        readonly
                                        copyable
                                        size="sm"
                                        aria-label="Webhook URL"
                                    />
                                @else
                                    —
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:modal.trigger name="edit-camera-rule">
                                    <flux:button
                                        wire:click="startEditing({{ $rule->id }})"
                                        icon="pencil-square"
                                        variant="subtle"
                                        size="sm"
                                        tooltip="Изменить"
                                    />
                                </flux:modal.trigger>
                                <flux:modal.trigger name="delete-camera-rule">
                                    <flux:button
                                        wire:click="startDeleting({{ $rule->id }})"
                                        icon="trash"
                                        variant="subtle"
                                        size="sm"
                                        tooltip="Удалить"
                                    />
                                </flux:modal.trigger>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @else
        <flux:callout
            class="mt-8 max-w-3xl"
            variant="secondary"
            icon="information-circle"
            heading="Для камеры ещё нет настроенных правил"
        >
            Выберите тип из списка выше или создайте собственный.
        </flux:callout>
    @endif

    <form wire:submit="updateRule">
        <flux:modal :closable="false" name="edit-camera-rule">
            <div class="space-y-6">
                <flux:heading size="lg">Изменить правило</flux:heading>
                <flux:input wire:model="editCode" label="Код rule" />
                <flux:input wire:model="editTitle" label="Заголовок уведомления" />
                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Сохранить</flux:button>
                    <flux:modal.close>
                        <flux:button>Закрыть</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    </form>

    <form wire:submit="deleteRule">
        <flux:modal :closable="false" name="delete-camera-rule">
            <div class="space-y-6">
                <flux:heading size="lg">Удалить правило?</flux:heading>
                <flux:text>{{ $deletingRuleTitle }}</flux:text>
                <flux:text>Уже принятые события сохранят свой заголовок.</flux:text>
                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:button type="submit" variant="danger">Удалить</flux:button>
                    <flux:modal.close>
                        <flux:button>Отмена</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    </form>

    <form wire:submit="rotateWebhookToken">
        <flux:modal :closable="false" name="rotate-webhook-token">
            <div class="space-y-6">
                <flux:heading size="lg">Заменить webhook-токен?</flux:heading>
                <flux:text>
                    Все ранее скопированные URL этой камеры перестанут работать. После замены скопируйте новый URL
                    в каждое настроенное HTTP-действие устройства.
                </flux:text>
                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:button type="submit" variant="danger">Заменить токен</flux:button>
                    <flux:modal.close>
                        <flux:button>Отмена</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    </form>
</div>
