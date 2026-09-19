<?php

use App\Models\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Клиенты')] class extends Component {
    use WithPagination;

    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Client::query()->withCount('cameras')->orderBy('name')->paginate(10);
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

</div>
