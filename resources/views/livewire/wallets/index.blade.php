<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Number;
use vahidkaargar\LaravelWallet\Enums\TransactionStatus;
use vahidkaargar\LaravelWallet\Enums\TransactionType;

new class extends Component {
    use WithPagination;

    public Collection $wallets;
    public string $walletSlug = 'usd';
    public object $wallet;

    public array $transactionDateRange;
    public string $transactionType;
    public string $transactionStatus;

    public function mount(): void
    {
        $this->loadWallets();
        $this->loadWallet();
    }

    public function loadWallets(): void
    {
        $this->wallets = auth()->user()->wallets;
    }

    public function loadWallet(): void
    {
        $this->wallet = auth()->user()->getWallet($this->walletSlug);
    }

    // Reset pagination and refresh when filters change
    public function updatedTransactionType(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    public function updatedTransactionStatus(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    public function updatedTransactionDateRange(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    public function updatedWalletSlug(): void
    {
        $this->loadWallet();
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function transactions()
    {
        // Explicitly read reactive properties so Livewire tracks dependencies
        $slug = $this->walletSlug;
        $type = $this->transactionType ?? '';
        $status = $this->transactionStatus ?? '';
        $range = $this->transactionDateRange ?? [
            "start" => '',
            "end" => '',
        ];

        $fromDate = !empty($range['start']) ? Carbon::parse($range['start'])->startOfDay() : null;
        $toDate = !empty($range['end']) ? Carbon::parse($range['end'])->endOfDay() : null;

        $transactionType = $type ? TransactionType::tryFrom($type) : null;
        $transactionStatus = $status ? TransactionStatus::tryFrom($status) : null;

        return $this->wallet->getTransactionsPaginated(
            $transactionType,
            $transactionStatus,
            $fromDate,
            $toDate
        );
    }
}; ?>

    <!-- 💰 Wallet and Transactions Layout -->
<div class="flex items-start max-md:flex-col">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid md:grid-cols-8 gap-4">

            <!-- Sidebar Wallet Info -->
            <div class="md:col-span-2">
                <div class="relative overflow-hidden rounded-xl dark:border-neutral-700 bg-linear-to-br dark:from-zinc-700 from-neutral-200 p-4">

                    <!-- Wallet Selector -->
                    <flux:select
                        wire:model.live="walletSlug"
                        wire:change="loadWallet"
                        class="relative"
                        variant="listbox"
                        placeholder="Choose wallet...">

                        @foreach($wallets as $w)
                            <flux:select.option :value="$w->slug" :selected="$w->slug === $walletSlug">
                                <div class="grid auto-cols-max grid-flow-col gap-2 items-center">
                                    <flux:icon.wallet variant="micro"></flux:icon.wallet>
                                    <div>{{ $w->name }}</div>
                                </div>
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <!-- Wallet Details -->
                    <div class="mt-10 mb-5">
                        <flux:icon.wallet class="size-16 mx-auto opacity-50" variant="solid"/>
                        <flux:heading size="xl" class="px-3 text-center text-shadow-lg/20 mt-1">
                            {{ $wallet->name }}
                        </flux:heading>

                        <div class="grid grid-cols-2 gap-2 my-6 items-center">
                            <div class="text-end font-semibold opacity-50">Balance</div>
                            <div class="px-2">
                                {{ Number::currency($wallet->available_funds->toDecimal(), in: $wallet->currency) }}
                            </div>
                            @if($wallet->credit > 0)
                                <div class="text-end font-semibold opacity-50">Credit</div>
                                <div class="px-2">
                                    {{ Number::currency($wallet->credit, in: $wallet->currency) }}
                                </div>
                            @endif
                            @if($wallet->locked > 0)
                                <div class="text-end font-semibold opacity-50">Locked</div>
                                <div class="px-2">
                                    {{ Number::currency($wallet->locked, in: $wallet->currency) }}
                                </div>
                            @endif
                        </div>

                        <div class="text-center mt-5">
                            <flux:button class="mx-auto w-32" icon="credit-card" variant="primary" color="green">
                                Charge
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="relative md:col-span-6 overflow-hidden rounded-xl bg-linear-to-bl dark:from-zinc-700 from-neutral-200">

                <div class="px-4 md:px-8 py-10 relative opacity-60">
                    <flux:heading size="xl" class="dark:text-shadow-lg/30">
                        {{ __('Transactions') }}
                    </flux:heading>
                </div>

                <!-- Filters -->
                <div class="relative grid grid-cols-6 gap-4 p-4 md:p-8">
                    <div class="col-span-2">
                        <flux:date-picker
                            mode="range"
                            wire:model.live="transactionDateRange"
                            clearable
                            presets="today yesterday thisWeek last7Days thisMonth"
                            label="Date range"/>
                    </div>

                    <flux:select
                        wire:model.live="transactionType"
                        clearable
                        class="relative"
                        variant="listbox"
                        placeholder="Type"
                        label="Type">
                        @foreach(TransactionType::cases() as $type)
                            <flux:select.option :value="$type->value">
                                <div class="capitalize">{{ $type->label() }}</div>
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select
                        wire:model.live="transactionStatus"
                        clearable
                        class="relative"
                        variant="listbox"
                        placeholder="Status"
                        label="Status">
                        @foreach(TransactionStatus::cases() as $status)
                            <flux:select.option :value="$status->value">
                                <div class="capitalize">{{ $status->label() }}</div>
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Transactions Table -->
                <div class="p-4 md:p-8">
                    <flux:table
                        align="center"
                        :paginate="$this->transactions"
                        wire:key="tx-{{ md5(json_encode([$transactionType, $transactionStatus, $transactionDateRange])) }}">

                        <flux:table.columns>
                            <flux:table.column>{{__('ID')}}</flux:table.column>
                            <flux:table.column align="end">{{__('Amount')}}</flux:table.column>
                            <flux:table.column width="120">{{__('Type')}}</flux:table.column>
                            <flux:table.column align="center" width="120">{{__('Status')}}</flux:table.column>
                            <flux:table.column align="end" width="160">{{__('Created at')}}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse($this->transactions as $transaction)
                                <flux:table.row>
                                    <flux:table.cell variant="strong">
                                        <pre class="text-xs opacity-50 uppercase">{{ $transaction->id }}</pre>
                                    </flux:table.cell>
                                    <flux:table.cell align="end" variant="strong">
                                        {{ Number::currency($transaction->amount, in: $wallet->currency) }}
                                    </flux:table.cell>
                                    <flux:table.cell align="start">
                                        {{ $transaction->type->label() }}
                                    </flux:table.cell>
                                    <flux:table.cell align="center">
                                        <flux:badge class="capitalize">
                                            {{ $transaction->status->label() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        {{ $transaction->created_at->diffForHumans() }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="5" class="text-center">
                                        <div class="py-10">
                                            No transaction records found.
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        </div>
    </div>
</div>
