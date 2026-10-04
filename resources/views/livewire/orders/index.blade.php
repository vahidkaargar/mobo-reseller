<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Builder;

new class extends Component {
    use WithPagination;

    public string $status = '';
    public array $createdAt = [];

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $from = filled($this->createdAt['start'] ?? null) ? Carbon::parse($this->createdAt['start'])->startOfDay() : null;
        $to = filled($this->createdAt['end'] ?? null) ? Carbon::parse($this->createdAt['end'])->endOfDay() : null;

        return auth()->user()->orders()
            ->withCount('items')
            ->when(filled($this->status), fn (Builder $query) => $query->where('status', $this->status))
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to))
            ->latest()
            ->paginate(15);
    }
}; ?>
<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl max-w-6xl">

    <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-neutral-200">
        <div class="px-4 md:px-8 py-10 relative opacity-60">
            <flux:heading size="xl" class="dark:text-shadow-lg/30">
                Orders
            </flux:heading>
        </div>
        <div class="relative grid md:grid-cols-6 gap-4 p-4 md:p-8 opacity-70 items-end">
            <div class="md:col-span-2">
                <flux:select wire:model.live="status" clearable variant="listbox" placeholder="Status" label="Status">
                    @foreach(OrderStatusEnum::cases() as $case)
                        <flux:select.option :value="$case->value">{{ $case->name() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="md:col-span-3">
                <flux:date-picker mode="range" wire:model.live="createdAt" clearable
                                  presets="today yesterday thisWeek last7Days thisMonth" label="Date range"/>
            </div>
        </div>
        <div>
            <flux:table class="p-4 md:p-8" align="center" :paginate="$this->orders"
                        wire:key="orders-{{ md5(json_encode([$status, $createdAt])) }}">
                <flux:table.columns>
                    <flux:table.column>{{__('Order')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Items')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Amount')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Status')}}</flux:table.column>
                    <flux:table.column align="center" width="160">{{__('Created at')}}</flux:table.column>
                    <flux:table.column align="center" width="160">{{__('Paid at')}}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->orders as $order)
                        <flux:table.row :key="$order->id">
                            <flux:table.cell>#{{ $order->id }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $order->items_count }}</flux:table.cell>
                            <flux:table.cell align="center" variant="strong">@currency($order->sale_amount)</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge color="{{ $order->status->color() }}">{{ $order->status->name() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="center">{{ $order->created_at->format('Y/m/d H:i') }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $order->paid_at?->format('Y/m/d H:i') ?? '-' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-10">{{ __('No orders yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
