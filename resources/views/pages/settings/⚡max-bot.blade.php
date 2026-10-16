<?php

use App\Models\MaxBotSetting;
use App\Services\MaxClient;
use Flux\Flux;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Настройка MAX-бота')] class extends Component
{
    public string $token = '';

    public ?string $botName = null;

    public ?string $botUsername = null;

    public ?int $botUserId = null;

    public function mount(): void
    {
        $this->loadCurrentBot();
    }

    public function updateBot(MaxClient $maxClient): void
    {
        $validated = $this->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $bot = $maxClient->inspectBot($validated['token']);
        } catch (ConnectionException|RequestException|\UnexpectedValueException) {
            $this->reset('token');
            $this->addError('token', 'Не удалось проверить токен. Убедитесь, что он действителен и MAX API доступен.');

            return;
        }

        MaxBotSetting::updateOrCreate(
            ['id' => MaxBotSetting::CURRENT_ID],
            [
                'token' => $validated['token'],
                'bot_user_id' => $bot['user_id'],
                'bot_name' => $bot['name'],
                'bot_username' => $bot['username'],
            ],
        );

        $this->reset('token');
        $this->loadCurrentBot();

        Flux::toast(variant: 'success', text: 'MAX-бот подключён.');
    }

    private function loadCurrentBot(): void
    {
        $setting = MaxBotSetting::find(MaxBotSetting::CURRENT_ID);

        $this->botName = $setting?->bot_name;
        $this->botUsername = $setting?->bot_username;
        $this->botUserId = $setting?->bot_user_id;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">Настройка MAX-бота</flux:heading>

    <x-pages::settings.layout
        heading="MAX-бот"
        subheading="Подключите единственного бота, через которого приложение отправляет уведомления"
    >
        @if ($botName !== null)
            <div class="mb-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">Подключённый бот</flux:heading>
                <flux:text class="mt-1">
                    {{ $botName }}
                    @if ($botUsername !== null)
                        ({{ '@' . $botUsername }})
                    @endif
                </flux:text>
                <flux:text class="mt-1 text-xs">ID: {{ $botUserId }}</flux:text>
            </div>
        @else
            <flux:callout class="mb-6" icon="information-circle">
                Бот ещё не сохранён в приложении. До этого момента используется токен из окружения, если он задан.
            </flux:callout>
        @endif

        <form wire:submit="updateBot" class="space-y-6">
            <flux:input
                wire:model="token"
                label="Новый токен бота"
                type="password"
                required
                autocomplete="new-password"
                description="Токен будет проверен через MAX API и сохранён в базе в зашифрованном виде."
            />

            <flux:button variant="primary" type="submit">
                {{ $botName === null ? 'Подключить бота' : 'Заменить бота' }}
            </flux:button>
        </form>
    </x-pages::settings.layout>
</section>
