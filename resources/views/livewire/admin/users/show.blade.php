<?php

use App\Models\BambooBrand;
use App\Models\User;
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

    public array $ordersChartData = [
        ['date' => '2025-11-15', 'invoices' => 165],
        ['date' => '2025-11-14', 'invoices' => 143],
        ['date' => '2025-11-13', 'invoices' => 157],
        ['date' => '2025-11-12', 'invoices' => 12],
        ['date' => '2025-11-11', 'invoices' => 41],
        ['date' => '2025-11-10', 'invoices' => 128],
        ['date' => '2025-11-09', 'invoices' => 36],
        ['date' => '2025-11-08', 'invoices' => 90],
        ['date' => '2025-11-07', 'invoices' => 269],
        ['date' => '2025-11-06', 'invoices' => 201],
        ['date' => '2025-11-05', 'invoices' => 165],
        ['date' => '2025-11-04', 'invoices' => 143],
        ['date' => '2025-11-03', 'invoices' => 157],
        ['date' => '2025-11-02', 'invoices' => 220],
        ['date' => '2025-11-01', 'invoices' => 199],
    ];

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
                <flux:text>Orders</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">$32,100</flux:heading>
            </flux:card>
            <flux:card class="border-0">
                <flux:text>Invoices</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">2,100</flux:heading>
            </flux:card>
            <flux:card class="border-0">
                <flux:text>Items</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">4,223</flux:heading>
            </flux:card>
            <flux:card class="border-0">
                <flux:text>Balance</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">$1,241</flux:heading>
            </flux:card>
        </div>

        <div class="grid grid-cols-2 gap-4">

            <div
                class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200 p-4 space-y-6">
                <flux:heading>Invoices</flux:heading>
                <flux:chart wire:model="ordersChartData" class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.line field="invoices" class="text-violet-400"/>

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
                        <flux:chart.tooltip.value field="invoices" label="Invoices"/>
                    </flux:chart.tooltip>
                </flux:chart>

            </div>

            <div
                class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200 p-4 space-y-6">
                <flux:heading>Transactions</flux:heading>
                <flux:chart wire:model="ordersChartData" class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.line field="invoices" class="text-violet-400"/>

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
                        <flux:chart.tooltip.value field="invoices" label="Invoices"/>
                    </flux:chart.tooltip>
                </flux:chart>

            </div>

        </div>

    </x-admin.users.layout>

</section>
