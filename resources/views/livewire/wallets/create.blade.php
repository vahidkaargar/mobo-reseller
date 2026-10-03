<x-layouts.app :title="__('Products')">
    <div
        class="relative overflow-hidden my-4">
        <flux:select class="md:w-96 relative" variant="listbox" searchable placeholder="Choose product...">
            <flux:select.option>
                <div class="grid auto-cols-max grid-flow-col gap-2">
                    <img alt="" class="aspect-square w-6 object-cover rounded "
                         src="https://bamboo-assets.s3.amazonaws.com/app-images/brand-images/272/logo">
                    <div>
                        Apple Australia
                    </div>
                    <flux:badge class="right-8 absolute" size="sm" icon="banknotes">AUD</flux:badge>
                    <flux:badge class="right-24 absolute" size="sm" icon="globe-alt">IR</flux:badge>
                </div>
            </flux:select.option>
        </flux:select>
    </div>

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid md:grid-cols-5 gap-4">
            <div
                class="relative md:col-span-3 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">

                <flux:table align="center">
                    <flux:table.columns>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column align="center" width="150">Amount</flux:table.column>
                        <flux:table.column align="center" width="130">Quantity</flux:table.column>
                        <flux:table.column align="center" width="140">Each</flux:table.column>
                        <flux:table.column align="center" width="140">Payable</flux:table.column>
                        <flux:table.column width="60"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>PSN 10 USD AR</flux:table.cell>
                            <flux:table.cell>
                                <flux:input class="text-center" class:input="text-center" placeholder="2 – 500"/>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input.group>
                                    <flux:button icon="minus" class="px-1"></flux:button>
                                    <flux:input class:input="text-center" value="1"/>
                                    <flux:button icon="plus" class="px-1"></flux:button>
                                </flux:input.group>
                            </flux:table.cell>
                            <flux:table.cell align="center">$49.00</flux:table.cell>
                            <flux:table.cell align="center" variant="strong">$120.00</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:button
                                    href="https://google.com"
                                    icon:trailing="shopping-cart"
                                ></flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
            </div>
            <div
                class="relative md:col-span-2 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 bg-white/5">
                <flux:heading size="xl" class="px-3 py-5 flex gap-3">
                    <flux:icon.shopping-cart/>
                    Basket
                </flux:heading>
                <flux:table align="center">
                    <flux:table.columns>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column align="center" width="150">Amount</flux:table.column>
                        <flux:table.column align="center" width="130">Quantity</flux:table.column>
                        <flux:table.column align="center" width="140">Payable</flux:table.column>
                        <flux:table.column width="60"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>Apple AUD</flux:table.cell>
                            <flux:table.cell align="center">
                                300 AUD
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input.group>
                                    <flux:button icon="minus" class="px-1"></flux:button>
                                    <flux:input class:input="text-center" value="1"/>
                                    <flux:button icon="plus" class="px-1"></flux:button>
                                </flux:input.group>
                            </flux:table.cell>
                            <flux:table.cell align="center">$120.00</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:button
                                    href="https://google.com"
                                    icon:trailing="trash"
                                ></flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>

                <div class="grid lg:grid-cols-5 gap-3 mt-10">
                    <div class="self-center lg:col-span-2"></div>
                    <div class="self-center lg:col-span-3">
                        <flux:input.group color="rose">
                            <flux:input class:input="text-center" icon="currency-dollar" value="3,200" readonly/>
                            <flux:select class="max-w-fit" variant="listbox" placeholder="Choose wallet...">
                                <flux:select.option selected>USDT</flux:select.option>
                                <flux:select.option>IRT</flux:select.option>
                            </flux:select>
                            <flux:button
                                href="https://google.com"
                                icon="credit-card" color="green" variant="primary"
                                class="ps-5 lg:ps-3"
                            >
                                <span class="hidden lg:block">Checkout</span>
                            </flux:button>
                        </flux:input.group>
                    </div>


                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
