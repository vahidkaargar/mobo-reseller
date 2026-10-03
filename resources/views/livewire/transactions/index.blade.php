<?php

use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use \App\Enums\TransactionTypeEnum;
use \App\Enums\TransactionExecutorEnum;
use \App\Enums\ColorEnum;

new class extends Component {
    public int $walletId;
    public string $createdAt;
    public string $operation;
    public string $executedBy;
}; ?>

<x-layouts.app :title="__('Transactions')">
    <div
            class="relative md:col-span-6 overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-neutral-200 max-w-6xl">
        <div class="px-4 md:px-8 py-10 relative opacity-60">
            <flux:heading size="xl" class="dark:text-shadow-lg/30">
                Transactions
            </flux:heading>
        </div>
        <div class="relative grid md:grid-cols-14 gap-4 p-4 md:p-8 opacity-70 items-end">
            <div class="md:col-span-2">
                <flux:input type="text" label="Order barcode" placeholder="Barcode" clearable></flux:input>
            </div>
            <div class="md:col-span-2">
                <flux:select wire:model="operation" clearable class="relative" variant="listbox"
                             placeholder="Operation"
                             label="Operation">
                    @foreach(TransactionTypeEnum::cases() as $operator)
                        <flux:select.option :value="$operator->name">
                            <div class="grid auto-cols-max grid-flow-col gap-2 items-center">
                                <div class="capitalize">
                                    {{$operator->value}}
                                </div>
                            </div>
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="md:col-span-2">
                <flux:select wire:model="executedBy" clearable class="relative" variant="listbox"
                             placeholder="Executed by"
                             label="Executed by">
                    @foreach(TransactionExecutorEnum::cases() as $operator)
                        <flux:select.option :value="$operator->name">
                            <div class="grid auto-cols-max grid-flow-col gap-2 items-center">
                                <div class="capitalize">
                                    {{$operator->value}}
                                </div>
                            </div>
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="md:col-span-4">
                <flux:date-picker mode="range" wire:model="createdAt" clearable
                                  presets="today yesterday thisWeek last7Days thisMonth" label="Date range"/>
            </div>
            <div class="md:col-span-4">
                <flux:button class="w-full md:w-32 cursor-pointer" variant="filled" icon="funnel">Filter</flux:button>
            </div>
        </div>

        <div class="p-4 md:p-8">
            <flux:table align="center">
                <flux:table.columns>
                    <flux:table.column>{{__('#')}}</flux:table.column>
                    <flux:table.column align="end" width="120">{{__('Amount')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Wallet')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Operation')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Execute by')}}</flux:table.column>
                    <flux:table.column align="end" width="160">{{__('Created at')}}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @for($i=1;$i<5; $i++)
                        <flux:table.row>
                            <flux:table.cell variant="strong">129800</flux:table.cell>
                            <flux:table.cell align="end" variant="strong">$49.00</flux:table.cell>
                            <flux:table.cell align="center">USDT</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge
                                        color="{{TransactionTypeEnum::DEBIT->color()}}">{{TransactionTypeEnum::DEBIT}}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge
                                        color="{{TransactionExecutorEnum::USER->color()}}">{{TransactionExecutorEnum::USER}}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">2025/07/12 12:24</flux:table.cell>
                        </flux:table.row>
                    @endfor
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</x-layouts.app>
