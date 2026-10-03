<?php

use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use App\Enums\{TransactionTypeEnum, TransactionExecutorEnum, BambooOrderStatusEnum};

new class extends Component {
    public int $walletId;
    public string $createdAt;
    public string $operation;
    public string $executedBy;
}; ?>
<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl max-w-6xl">

    <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-neutral-200">
        <div class="px-4 md:px-8 py-10 relative opacity-60">
            <flux:heading size="xl" class="dark:text-shadow-lg/30">
                Orders
            </flux:heading>
        </div>
        <div class="relative grid grid-cols-14 gap-4 p-4 md:p-8 opacity-70 items-end">
            <div class="col-span-2">
                <flux:input type="text" label="Order barcode" placeholder="Barcode" clearable></flux:input>
            </div>
            <div class="col-span-2">
                <flux:select wire:model="status" clearable variant="listbox"
                             placeholder="Status"
                             label="Status">
                    @foreach(BambooOrderStatusEnum::cases() as $status)
                        <flux:select.option :value="$status->name">
                            <div class="grid auto-cols-max grid-flow-col gap-2 items-center">
                                <div class="capitalize">
                                    {{$status->value}}
                                </div>
                            </div>
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="col-span-2">
                <flux:select wire:model="executedBy" clearable variant="listbox"
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
            <div class="col-span-4">
                <flux:date-picker mode="range" wire:model="createdAt" clearable
                                  presets="today yesterday thisWeek last7Days thisMonth" label="Date range"/>
            </div>
            <div>
                <flux:button class="w-32 cursor-pointer" variant="filled" icon="funnel">Filter</flux:button>
            </div>
        </div>
        <div>
            <flux:table class="p-4 md:p-8" align="center">
                <flux:table.columns>
                    <flux:table.column>Barcode</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Amount')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Status')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Executed by')}}</flux:table.column>
                    <flux:table.column align="center" width="160">{{__('Created at')}}</flux:table.column>
                    <flux:table.column align="center" width="160">{{__('Paid at')}}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    <flux:table.row>
                        <flux:table.cell>#12345678</flux:table.cell>
                        <flux:table.cell align="center" variant="strong">$49.00</flux:table.cell>
                        <flux:table.cell align="center">
                            <flux:badge
                                color="{{BambooOrderStatusEnum::CREATED->color()}}">{{BambooOrderStatusEnum::CREATED}}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            <flux:badge variant="solid"
                                        color="{{TransactionExecutorEnum::USER->color()}}">{{TransactionExecutorEnum::USER}}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="center">2025/07/12 12:24</flux:table.cell>
                        <flux:table.cell align="center">2025/07/12 12:24</flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
