<?php

use App\Enums\OrderStatusEnum;
use App\Exceptions\OrderResolutionException;
use App\Models\Order;
use App\Services\OrderResolutionService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public bool $onlyUnsettled = false;

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->with('user:id,name,email')
            ->withCount('items')
            ->when(filled($this->search), function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('id', (int) $this->search)
                        ->orWhereHas('user', fn (Builder $user) => $user->whereAny(['name', 'email'], 'like', "%{$this->search}%"));
                });
            })
            ->when(filled($this->status), fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->onlyUnsettled, fn (Builder $query) => $query->whereIn('status', [OrderStatusEnum::PROCESSING, OrderStatusEnum::PARTIAL_FAILED]))
            ->latest()
            ->paginate(20);
    }

    public function release(int $orderId, OrderResolutionService $resolution): void
    {
        $this->resolve($orderId, fn (Order $order) => $resolution->release($order, auth()->user(), 'admin release'), 'Funds released; order marked failed.');
    }

    public function charge(int $orderId, OrderResolutionService $resolution): void
    {
        $this->resolve($orderId, fn (Order $order) => $resolution->charge($order, auth()->user(), 'admin charge'), 'Order charged and marked succeeded.');
    }

    private function resolve(int $orderId, Closure $action, string $success): void
    {
        $order = Order::query()->with(['user', 'wallet'])->findOrFail($orderId);

        try {
            $action($order);
        } catch (OrderResolutionException $e) {
            Flux::toast(text: $e->getMessage(), heading: "Order #{$order->id}", variant: 'danger', position: 'bottom end');

            return;
        }

        unset($this->orders);
        Flux::toast(text: $success, heading: "Order #{$order->id}", variant: 'success', position: 'bottom end');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl max-w-6xl">
        <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200">
            <div class="px-4 md:px-8 py-10 relative opacity-60">
                <flux:heading size="xl" class="dark:text-shadow-lg/30">Orders</flux:heading>
            </div>

            <div class="grid md:grid-cols-6 gap-4 p-4 md:p-8 items-end">
                <div class="col-span-2">
                    <flux:input
                        wire:model.live.debounce.500ms="search"
                        icon="magnifying-glass"
                        clearable
                        placeholder="Order # or customer..."/>
                </div>
                <div class="col-span-2">
                    <flux:select wire:model.live="status" variant="listbox" clearable placeholder="Status">
                        @foreach(OrderStatusEnum::cases() as $case)
                            <flux:select.option :value="$case->value">{{ $case->name() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="col-span-2">
                    <flux:switch wire:model.live="onlyUnsettled" label="Needs attention only"/>
                </div>
            </div>

            <flux:table
                class="p-4 md:p-8"
                align="center"
                :paginate="$this->orders"
                wire:key="orders-{{ md5(json_encode([$search, $status, $onlyUnsettled])) }}">
                <flux:table.columns>
                    <flux:table.column>{{ __('Order') }}</flux:table.column>
                    <flux:table.column>{{ __('Customer') }}</flux:table.column>
                    <flux:table.column align="center" width="80">{{ __('Items') }}</flux:table.column>
                    <flux:table.column align="center" width="120">{{ __('Cost') }}</flux:table.column>
                    <flux:table.column align="center" width="120">{{ __('Sale') }}</flux:table.column>
                    <flux:table.column align="center" width="130">{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="center" width="150">{{ __('Created') }}</flux:table.column>
                    <flux:table.column align="center" width="200">{{ __('Resolve') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->orders as $order)
                        <flux:table.row :key="$order->id">
                            <flux:table.cell variant="strong">#{{ $order->id }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:link :href="route('admin.users.show', $order->user)">{{ $order->user->name }}</flux:link>
                                <div class="text-xs opacity-60">{{ $order->user->email }}</div>
                            </flux:table.cell>
                            <flux:table.cell align="center">{{ $order->items_count }}</flux:table.cell>
                            <flux:table.cell align="center">@currency($order->purchase_amount)</flux:table.cell>
                            <flux:table.cell align="center" variant="strong">@currency($order->sale_amount)</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge color="{{ $order->status->color() }}">{{ $order->status->name() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="center">{{ $order->created_at->format('Y/m/d H:i') }}</flux:table.cell>
                            <flux:table.cell align="center">
                                @if(OrderResolutionService::isResolvable($order))
                                    <div class="flex gap-2 justify-center">
                                        <flux:button
                                            size="xs"
                                            variant="danger"
                                            wire:click="release({{ $order->id }})"
                                            wire:confirm="Release the locked funds back to the customer and mark order #{{ $order->id }} as failed?">
                                            Release
                                        </flux:button>
                                        <flux:button
                                            size="xs"
                                            variant="primary"
                                            wire:click="charge({{ $order->id }})"
                                            wire:confirm="Charge the full amount and mark order #{{ $order->id }} as succeeded?">
                                            Charge
                                        </flux:button>
                                    </div>
                                @else
                                    <span class="opacity-40">-</span>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-10">{{ __('No orders match.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
