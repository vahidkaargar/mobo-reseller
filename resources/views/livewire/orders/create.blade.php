<?php

use App\Facades\Cart;
use App\Models\BambooBrand;
use App\Services\ExchangeService;
use App\Services\CartService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use App\Enums\{SuppliersEnum, TransactionTypeEnum, TransactionExecutorEnum};
use App\Models\User;
use Illuminate\Support\Number;
use Flux\Flux;


new class extends Component {
    public SuppliersEnum $suppliers;
    public string $supplierAndCategoryId;
    public array $products = [];
    public array $cart;

    public function mount(): void
    {

    }

    public function updated($property, $value): void
    {
        $property = str($property);
        $this->whenCartUpdated($property);
    }

    private function whenCartUpdated($property): void
    {
        $start = 'cart.';
        $end = '.quantity';
        if ($property->startsWith($start) and $property->endsWith($end)) {
            $itemKey = $property->between($start, $end)->toString();
            $this->updateCart($itemKey);
        }
    }

    #[Computed]
    public function checkout(): int|float
    {
        $items = collect($this->cartItems());
        return $items->sum('amount');
    }

    #[Computed]
    public function categories(): array
    {
        $categories = [];
        foreach (SuppliersEnum::cases() as $supplier) {
            $categories = array_merge($categories, $supplier->catalog()->categories());
        }
        return $categories;
    }

    #[Computed]
    public function products(): array
    {
        if (empty($this->supplierAndCategoryId)) {
            return [];
        }
        list($supplier, $categoryId) = str($this->supplierAndCategoryId)->explode('|');
        $products = SuppliersEnum::tryFrom($supplier)->catalog()->products($categoryId);
        foreach ($products as $product) {
            $isProductExists = isset($this->products[$product['id']]);
            if (!$isProductExists) {
                $this->products[$product['id']] = [
                    "id" => $product['id'],
                    "supplier" => $supplier,
                    "quantity" => 1,
                    "purchase" => $product['purchase']['fixed'] ? $product['purchase']['min'] : null,
                    "min" => $product['purchase']['min'],
                    "max" => $product['purchase']['max'],
                    "name" => $product['name'],
                ];
            }
        }
        return $products;
    }

    #[Computed]
    public function cartItems(): array
    {
        $items = Cart::items();
        foreach ($items as $id => $item) {
            $this->cart[$id] = $item;
        }
        return $items;
    }

    public function addToCart($supplier, $productId): void
    {
        $product = $this->products[$productId];
        $this->validate([
            "products.$productId.purchase" => ['required', 'numeric', "between:{$product['min']},{$product['max']}"]
        ]);
        if (Cart::add($supplier, $productId, $product['purchase'], $product['quantity'])) {
            Flux::toast(text: "Product added to cart", heading: $product['name'], variant: 'success', position: 'bottom end');
            Flux::modal('cart')->show();
        } else {
            Flux::toast(text: "Something went wrong. Please try again.", heading: 'Connection error', variant: 'danger', position: 'bottom end');
        }
    }

    public function removeFromCart($itemKey): void
    {
        Cart::remove($itemKey);
    }

    public function updateCart($itemKey): void
    {
        $this->validate([
            "cart.$itemKey.quantity" => ['required', 'numeric', "between:1,99"]
        ]);
        $item = $this->cart[$itemKey];
        Cart::add($item['supplier'], $item['product_id'], $item['purchase'], $item['quantity']);
    }

}; ?>

<div>
    <div class="relative my-4 flex gap-4 max-w-6xl">
        <flux:select
            wire:model.live="supplierAndCategoryId"
            clearable
            searchable
            class="lg:max-w-100 relative"
            variant="listbox"
            placeholder="Choose product...">
            @foreach($this->categories() as $category)
                <flux:select.option :value="$category['supplier'].'|'.$category['id']">
                    <div class="flex items-center gap-2">
                        <span class="truncate">{{ $category['name'] }}</span>
                    </div>
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:spacer class="hidden lg:block"/>
        <flux:modal.trigger name="cart" class="hidden lg:block">
            <flux:button icon="shopping-bag" variant="primary" color="orange">{{__('Cart')}}</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="flex flex-col md:grid gap-4 max-w-6xl">
        <div
            class="relative overflow-hidden rounded-xl dark:border-neutral-700 bg-linear-to-br dark:from-zinc-700 from-neutral-200">

            <flux:table
                align="center"
                class="opacity-70 p-4 md:p-8"
                wire:key="products-{{ md5(json_encode([$supplierAndCategoryId])) }}">
                <flux:table.columns>
                    <flux:table.column>Item</flux:table.column>
                    <flux:table.column align="center" width="180">Amount</flux:table.column>
                    <flux:table.column align="center" width="140">Quantity</flux:table.column>
                    <flux:table.column align="center" width="140">Each</flux:table.column>
                    <flux:table.column align="center" width="140">Payable</flux:table.column>
                    <flux:table.column width="30"></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>

                    @if(filled($this->products()))
                        @foreach($this->products() as $product)
                            @php($id = $product['id'])
                            @php($__product = $this->products[$id])
                            @php($__product_unit_amount = exchange(\App\Facades\FeeCalculator::supplier($__product['supplier'])->product($id)->addFeeTo($product['sale']['fixed'] ? $product['sale']['min'] : $product['sale']['unit']), $product['sale']['currency']))
                            <flux:table.row>
                                <flux:table.cell>{{ $product['name'] }}</flux:table.cell>
                                <flux:table.cell align="center">
                                    @if($product['purchase']['fixed'])
                                        @currency($product['purchase']['min'], $product['purchase']['currency'])
                                    @else
                                        <x-number-input
                                            wire:model.live.debounce.500ms="products.{{ $id }}.purchase"
                                            :placeholder="number_format($product['purchase']['min']) . ' – ' . number_format($product['purchase']['max'])"
                                            :description="'Between ' . number_format($product['purchase']['min']) . ' – ' . number_format($product['purchase']['max'])"
                                            :min="$product['purchase']['min']"
                                            :max="$product['purchase']['max']"/>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <x-quantity-picker wire:model.live.debounce.200ms="products.{{ $id }}.quantity"/>
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    @currency($__product_unit_amount)
                                </flux:table.cell>
                                <flux:table.cell align="center" variant="strong">
                                    @currency(
                                        $product['purchase']['fixed']
                                        ? $__product_unit_amount * $__product['quantity']
                                        : $__product['purchase'] * $__product_unit_amount * $__product['quantity']
                                    )
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    <flux:button
                                        wire:click="addToCart('{{$__product['supplier']}}', '{{ $id }}')"
                                        icon:trailing="shopping-cart"/>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    @else
                        <flux:table.row>
                            <flux:table.cell class="text-center py-10" colspan="6">
                                This product currently has no associated items.
                            </flux:table.cell>
                        </flux:table.row>
                    @endif

                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    <flux:modal name="cart" variant="flyout" class="px-0 pb-0 relative min-w-76 md:min-w-112 md:max-w-112">
        <div class="px-6 pb-8">
            <flux:heading size="xl" class="dark:text-shadow-lg/30">Cart</flux:heading>
        </div>

        @forelse($this->cartItems() as $id => $item)

            <flux:separator class="opacity-30 m-0"/>
            <div size="sm" class="bg-zinc-600/10 p-5 m-0 space-y-6">
                <div class="flex items-center">
                    <div class="flex-1 gap-3">
                        <flux:heading class="flex items-center gap-2  opacity-70">

                            <div class="overflow-hidden text-ellipsis">
                                {{ $item['product']['name'] }}
                            </div>

                            <flux:spacer/>

                            <flux:badge size="sm" class="gap-2">
                                @currency($item['purchase'], $item['product']['purchase']['currency'])
                                @if(strlen($item['product']['country']) === 2)
                                    <i class="fi fi-{{strtolower($item['product']['country'])}} rounded"></i>
                                @endif
                            </flux:badge>
                        </flux:heading>
                    </div>
                </div>

                <div class="flex items-center gap-4 opacity-70">
                    <flux:button
                        wire:click="removeFromCart('{{ $id }}')"
                        size="xs"
                        icon:trailing="trash"/>
                    <flux:spacer/>
                    <div class="w-26">
                        <x-quantity-picker wire:model.live.debounce.100ms="cart.{{$id}}.quantity"/>
                    </div>
                    <flux:spacer/>
                    @currency($item['amount'])
                </div>
            </div>
        @empty
            <flux:separator class="opacity-30 m-0"/>
            <div class="opacity-60 text-center py-24 bg-zinc-600/10">
                Cart is empty
            </div>
        @endforelse
        <flux:separator class="opacity-30 m-0"/>

        <div class="pt-6 pb-4">
            <flux:heading size="lg" class="text-center">
                <span class="opacity-50 text-sm">Payable</span>
                <span class="opacity-90 tracking-widest mx-2">
                    @currency($this->checkout())
                </span>
            </flux:heading>

            <div class="grid grid-cols-2 gap-x-4 p-4">
                <div>
                    <flux:select
                        wire:model="walletId"
                        class="relative"
                        variant="listbox"
                        placeholder="Choose wallet...">
                        <flux:select.option value="usdt" selected>
                            <div class="grid auto-cols-max grid-flow-col gap-2 items-center">
                                <flux:icon.wallet variant="micro"></flux:icon.wallet>
                                <div>
                                    USDT
                                </div>
                            </div>
                        </flux:select.option>
                    </flux:select>
                </div>
                <div>
                    <flux:button variant="primary" class="w-full">Checkout</flux:button>
                </div>
            </div>

        </div>


    </flux:modal>
</div>

