<?php

use App\Models\BambooBrand;
use App\Models\User;
use App\Services\ExchangeService;
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
    public string $search = '';
    public array $fees = [];
    public array $exchange;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->prepareProductFees();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    public function submitFeePercentage($supplierName, $supplierId, $productName): void
    {
        $heading = $productName;

        $payload = [
            'supplier_name' => $supplierName,
            'supplier_id' => $supplierId,
        ];
        $productFee = $this->user->fees()->firstOrNew($payload);
        $this->validate([
            "fees.$supplierName.$supplierId.fee_percentage" => ['nullable', 'numeric', 'between:0.01,9.99']
        ]);

        $feePercentage = $this->fees[$supplierName][$supplierId]['fee_percentage'] ?? null;
        if (is_numeric($feePercentage) and $feePercentage > 0 and $feePercentage < 10) {
            $productFee->fee_percentage = Number::parseFloat($feePercentage);

            $text = 'Something went wrong.';
            $variant = 'danger';
            if ($productFee->save()) {
                $text = 'Product fee successfully updated.';
                $variant = 'success';
            }
        } else {
            $productFee->delete();
            $text = 'Product fee restored to default preset.';
            $variant = 'warning';
        }

        Flux::toast(text: $text, heading: $heading, variant: $variant, position: 'bottom end');
    }

    public function prepareProductFees()
    {
        $this->user
            ->fees()
            ->get()
            ->each(function ($item) {
                $this->fees[$item->supplier_name][$item->supplier_id] = $item->toArray();
            });
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

        <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200">
            <flux:table
                class="p-4 md:p-8"
                :paginate="$this->brands">
                <flux:table.columns>
                    <flux:table.column>
                        <div class="flex items-center gap-3">
                            {{__('Brands')}}
                            <flux:input
                                class="opacity-50 max-w-[200px]"
                                clearable
                                size="sm"
                                wire:model.live.debounce.500ms="search"
                                icon="magnifying-glass"
                                placeholder="Search..."/>
                        </div>
                    </flux:table.column>
                    <flux:table.column align="start" width="120">{{__('Country')}}</flux:table.column>
                    <flux:table.column align="center" width="120">{{__('Currency')}}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->brands as $brand)
                        <flux:table.row class="bg-linear-to-br dark:from-zinc-800 from-zinc-200">
                            <flux:table.cell class="flex items-center gap-3">
                                @if(filled($brand->image))
                                    <img
                                        alt="{{ $brand->name }}"
                                        loading="lazy"
                                        class="rounded border border-zinc-600 ms-3"
                                        width="24"
                                        height="24"
                                        src="{{route('thumbnail', ['url' => $brand->image, 'w' => 32, 'h' => 32, 'q' => 95, 'fit' => 'cover'])}}">
                                @else
                                    <flux:avatar class="ms-3" size="xs" icon="gift"/>
                                @endif
                                {{ $brand->name }}
                            </flux:table.cell>
                            <flux:table.cell align="start">
                                <div class="flex items-center gap-2">
                                    <i class="fi fi-{{strtolower($brand->country)}} rounded"></i>
                                    {{ $brand->country }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                {{ $brand->currency }}
                            </flux:table.cell>
                        </flux:table.row>

                        <flux:table.row>
                            <flux:table.cell colspan="5" style="padding: 0 !important">

                                <flux:table>
                                    <flux:table.columns class="opacity-20">
                                        <flux:table.column class="w-[100px]">ID</flux:table.column>
                                        <flux:table.column>Product name</flux:table.column>
                                        <flux:table.column class="w-[180px]" align="center">
                                            Purchase
                                        </flux:table.column>
                                        <flux:table.column class="w-[100px]" align="center">
                                            Discount
                                        </flux:table.column>
                                        <flux:table.column class="w-[180px]" align="center">
                                            Sale
                                        </flux:table.column>
                                        <flux:table.column class="w-[120px]" align="center">
                                            <div class="flex items-center gap-1">
                                                Fee <small>[0.01-9.99%]</small>
                                            </div>
                                        </flux:table.column>
                                        <flux:table.column class="w-[180px]" align="center">
                                            Sale with fee
                                        </flux:table.column>
                                    </flux:table.columns>

                                    <flux:table.rows class="opacity-70">
                                        @foreach(collect($brand->products)->sortBy('purchase.min') as $product)
                                            @php($productFee = $fees['BAMBOO'][$product['id']]['fee_percentage'] ?? null)
                                            @php($productFee = is_numeric($productFee) ? $productFee : $user->fee_percentage)
                                            @php($productSale['min'] = add_percent($product['sale']['min'], $productFee))
                                            @php($productSale['max'] = add_percent($product['sale']['max'], $productFee))
                                            <flux:table.row>
                                                <flux:table.cell>{{$product['id']}}</flux:table.cell>
                                                <flux:table.cell>{{$product['name']}}</flux:table.cell>
                                                <flux:table.cell align="center" variant="strong">
                                                    @currency($product['purchase']['min'], $product['purchase']['currency'])
                                                    @if(!$product['purchase']['fixed'])
                                                        -
                                                        @currency($product['purchase']['max'], $product['purchase']['currency'])
                                                    @endif
                                                </flux:table.cell>
                                                <flux:table.cell align="center">
                                                    @percentage($product['discount'])
                                                </flux:table.cell>
                                                <flux:table.cell align="center" variant="strong">
                                                    @currency(@exchange($product['sale']['min'], $product['sale']['currency']))
                                                    @if(!$product['sale']['fixed'])
                                                        -
                                                        @currency(@exchange($product['sale']['max'], $product['sale']['currency']))
                                                    @endif
                                                </flux:table.cell>
                                                <flux:table.cell align="center">
                                                    <form
                                                        wire:submit="submitFeePercentage('BAMBOO', '{{$product['id']}}', '{{$product['name']}}')"
                                                        wire:key="form-{{ md5(json_encode([$product['id']])) }}">
                                                        <flux:input.group>
                                                            <flux:input
                                                                size="sm"
                                                                class:input="text-center"
                                                                wire:model.defer="fees.BAMBOO.{{$product['id']}}.fee_percentage"/>
                                                            <flux:button
                                                                class="w-[42px]"
                                                                size="sm"
                                                                icon="pencil-square"
                                                                icon:class="size-3"
                                                                type="submit"></flux:button>
                                                        </flux:input.group>
                                                    </form>
                                                </flux:table.cell>
                                                <flux:table.cell align="center">
                                                    <flux:badge size="sm">
                                                        +@percentage($productFee)
                                                    </flux:badge>
                                                    @exchange($productSale['min'], $product['sale']['currency'])
                                                    @if(!$product['sale']['fixed'])
                                                        -
                                                        @exchange($productSale['max'], $product['sale']['currency'])
                                                    @endif
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>

                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

    </x-admin.users.layout>

</section>
