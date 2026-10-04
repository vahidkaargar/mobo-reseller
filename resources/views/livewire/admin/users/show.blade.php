<?php

use App\Models\BambooBrand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;
use PragmaRX\Countries\Package\Countries;
use App\Enums\{TransactionTypeEnum, TransactionExecutorEnum, BambooOrderStatusEnum};

new class extends Component {
    use WithPagination;


    public User $user;
    public string $action = 'products';
    public string $search = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatedAction(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function orders()
    {
        return $this->user->orders()->paginate();
    }

    /**
     * @return array{sale: float, orders: int, items: int, balance: float}
     */
    #[Computed]
    public function stats(): array
    {
        $succeeded = $this->user->orders()->where('status', OrderStatusEnum::SUCCEEDED);
        $wallet = $this->user->wallets()->where('currency', 'USD')->first();

        return [
            'sale' => (float) $succeeded->sum('sale_amount'),
            'orders' => $this->user->orders()->count(),
            'items' => (int) OrderItem::query()->whereIn('order_id', $succeeded->select('id'))->sum('quantity'),
            'balance' => $wallet ? (float) $wallet->available_funds->toDecimal() : 0.0,
        ];
    }

    /**
     * Orders per day for the last 15 days, oldest first.
     *
     * @return list<array{date: string, orders: int}>
     */
    #[Computed]
    public function ordersChartData(): array
    {
        $perDay = $this->user->orders()
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->get(['created_at'])
            ->countBy(fn (Order $order) => $order->created_at->toDateString());

        return collect(range(14, 0))
            ->map(fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString())
            ->map(fn (string $date) => ['date' => $date, 'orders' => $perDay->get($date, 0)])
            ->values()
            ->all();
    }

    #[Computed]
    public function brands()
    {
        return BambooBrand::query()
            ->when(filled($this->search), function (Builder $query) {
                $query->where('name', 'like', "%$this->search%");
            })
            ->orderBy('name')
            ->paginate();
    }

}; ?>
<section class="w-full">
    <x-admin.users.layout :user="$user">

        <div class="grid mb-4 grid-cols-2 md:grid-cols-4 gap-4 opacity-70">
            <flux:card class="border-0">
                <flux:text>Sales</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">@currency($this->stats['sale'])</flux:heading>
            </flux:card>
            <flux:card class="border-0">
                <flux:text>Orders</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($this->stats['orders']) }}</flux:heading>
            </flux:card>
            <flux:card class="border-0">
                <flux:text>Cards sold</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($this->stats['items']) }}</flux:heading>
            </flux:card>
            <flux:card class="border-0">
                <flux:text>Balance</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">@currency($this->stats['balance'])</flux:heading>
            </flux:card>
        </div>

        <div class="grid grid-cols-2 gap-4">

            <div
                class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200 p-4 space-y-6">
                <flux:heading>Orders, last 15 days</flux:heading>
                <flux:chart :value="$this->ordersChartData" class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.line field="orders" class="text-violet-400"/>

                        <flux:chart.axis axis="x" field="date">
                            <flux:chart.axis.line/>
                            <flux:chart.axis.tick/>
                        </flux:chart.axis>

                        <flux:chart.axis axis="y">
                            <flux:chart.axis.grid/>
                            <flux:chart.axis.tick/>
                        </flux:chart.axis>

                        <flux:chart.cursor/>
                    </flux:chart.svg>

                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading
                            field="date"
                            :format="['year' => 'numeric', 'month' => 'numeric', 'day' => 'numeric']"/>
                        <flux:chart.tooltip.value field="orders" label="Orders"/>
                    </flux:chart.tooltip>
                </flux:chart>

            </div>

            <div
                class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200 p-4 space-y-6">
                <flux:heading>Transactions</flux:heading>
                <flux:chart wire:model="ordersChartData" class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.line field="orders" class="text-violet-400"/>

                        <flux:chart.axis axis="x" field="date">
                            <flux:chart.axis.line/>
                            <flux:chart.axis.tick/>
                        </flux:chart.axis>

                        <flux:chart.axis axis="y">
                            <flux:chart.axis.grid/>
                            <flux:chart.axis.tick/>
                        </flux:chart.axis>

                        <flux:chart.cursor/>
                    </flux:chart.svg>

                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="date"
                                                    :format="['year' => 'numeric', 'month' => 'numeric', 'day' => 'numeric']"/>
                        <flux:chart.tooltip.value field="orders" label="Orders"/>
                    </flux:chart.tooltip>
                </flux:chart>

            </div>

        </div>

    </x-admin.users.layout>

</section>
