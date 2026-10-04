<?php

use App\Models\BambooBrand;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use PragmaRX\Countries\Package\Countries;
use App\Enums\{TransactionTypeEnum, TransactionExecutorEnum, BambooOrderStatusEnum};

new class extends Component {

    public BambooBrand $brand;

    public function mount(BambooBrand $brand)
    {
        $this->brand = $brand;
    }
}; ?>
<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl max-w-6xl">

        <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-neutral-200">
            <div class="px-4 md:px-8 pt-10 relative">
                <flux:heading size="xl" class="dark:text-shadow-lg/30 flex items-center gap-3">
                    @if(filled($brand->image))
                        <flux:avatar
                            :href="$brand->image"
                            target="_blank"
                            :src="route('thumbnail', ['brandId' => $brand->brand_id, 'w' => 32, 'h' => 32, 'q' => 95, 'fit' => 'cover'])"/>
                    @endif
                    {{$brand->name}}
                </flux:heading>
            </div>
            <div>
                <flux:table class="p-4 md:p-8">
                    <flux:table.columns>
                        <flux:table.column width="120">{{__('Brand ID')}}</flux:table.column>
                        <flux:table.column>{{__('Brand name')}}</flux:table.column>
                        <flux:table.column align="center" width="120">{{__('Country')}}</flux:table.column>
                        <flux:table.column align="center" width="120">{{__('Currency')}}</flux:table.column>
                        <flux:table.column align="center" width="160">{{__('Updated at')}}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>
                                {{ $brand->brand_id }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $brand->name }}
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                <div class="flex items-center gap-3">
                                    <i class="fi fi-{{strtolower($brand->country)}} rounded"></i>
                                    {{ (new Countries())->where('cca2', $brand->country)->value('name.common') ?? $brand->country }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                {{ $brand->currency }}
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                {{ $brand->updated_at->diffForHumans() }}
                            </flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-neutral-200">
            <div class="px-4 md:px-8 pt-10 relative">
                <flux:heading size="xl" class="dark:text-shadow-lg/30 flex items-center gap-3 opacity-50">
                    <flux:icon.gift class="size-8"/>
                    Products
                </flux:heading>
            </div>
            <div>
                <flux:table class="p-4 md:p-8">
                    <flux:table.columns>
                        <flux:table.column class="max-w-5">ID</flux:table.column>
                        <flux:table.column>Product name</flux:table.column>
                        <flux:table.column align="center">Inventory</flux:table.column>
                        <flux:table.column align="center">Profit</flux:table.column>
                        <flux:table.column align="end">Purchase</flux:table.column>
                        <flux:table.column align="end">Sale</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach(collect($brand->products)->sortBy('purchase.min') as $product)
                            <flux:table.row>
                                <flux:table.cell>{{$product['id']}}</flux:table.cell>
                                <flux:table.cell>{{$product['name']}}</flux:table.cell>
                                <flux:table.cell align="center">{{$product['inventory'] ?? '—'}}</flux:table.cell>
                                <flux:table.cell align="center">{{$product['discount']}}%</flux:table.cell>
                                <flux:table.cell align="end" variant="strong">
                                    @if($product['purchase']['fixed'])
                                        {{Number::currency($product['purchase']['min'], in: $product['purchase']['currency'])}}
                                    @else
                                        <span class="opacity-50">
                                            {{$product['purchase']['currency']}}
                                        </span>
                                        {{$product['purchase']['min']}} — {{$product['purchase']['max']}}
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end" variant="strong">
                                    @if($product['sale']['fixed'])
                                        {{Number::currency($product['sale']['min'], in: $product['sale']['currency'])}}
                                    @else
                                        <span class="opacity-50">
                                            {{$product['sale']['currency']}}
                                        </span>
                                        {{$product['sale']['min']}} — {{$product['sale']['max']}}
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </div>
</div>
