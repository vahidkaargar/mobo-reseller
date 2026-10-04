<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
<flux:sidebar sticky stashable class="border-zinc-200 bg-zinc-50 dark:bg-zinc-700 shadow-xl">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark"/>

    <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo/>
    </a>

    <flux:navlist variant="outline">
        <flux:navlist.group class="grid">
            <flux:navlist.item
                icon="home"
                :href="route('dashboard')"
                :current="request()->routeIs('dashboard')"
                wire:navigate>
                {{ __('Dashboard') }}
            </flux:navlist.item>
            <flux:navlist.item
                icon="wallet"
                :href="route('wallets.index')"

                :current="request()->routeIs('wallets.index')"
                wire:navigate>
                {{ __('Wallets') }}
            </flux:navlist.item>
            <flux:navlist.item
                icon="shopping-bag"
                :href="route('orders.create')"
                :current="request()->routeIs('orders.create')"
                wire:navigate>
                {{ __('Order now') }}
            </flux:navlist.item>
            <flux:navlist.item
                icon="document-text"
                :href="route('orders.index')"
                :current="request()->routeIs('orders.index')"
                wire:navigate>
                {{ __('Orders') }}
            </flux:navlist.item>
            <flux:navlist.item
                icon="lifebuoy"
                :href="route('orders.index')"
                :current="request()->routeIs('orders.index')"
                wire:navigate>
                {{ __('Support Tickets') }}
            </flux:navlist.item>
        </flux:navlist.group>

        @role('admin')
        <flux:navlist.group class="mt-4" heading="Admin" expandable>
            <flux:navlist.item
                :current="request()->routeIs('admin.users.index')"
                :href="route('admin.users.index')">
                Users
            </flux:navlist.item>
            <flux:navlist.item
                :current="request()->routeIs('admin.orders.index')"
                :href="route('admin.orders.index')">
                Orders
            </flux:navlist.item>
            <flux:navlist.item href="#">Transactions</flux:navlist.item>
            <flux:navlist.item
                :current="request()->routeIs('admin.brands.index')"
                :href="route('admin.brands.index')">
                Brands
            </flux:navlist.item>
            <flux:navlist.item href="#">Settings</flux:navlist.item>
        </flux:navlist.group>
        @endrole

    </flux:navlist>

    <flux:spacer/>

    <flux:navlist variant="success">
        <flux:navlist.item :current="true" badge="API" icon="folder-git-2" :href="route('developers.tokens')">
            {{ __('Developers') }}
        </flux:navlist.item>
    </flux:navlist>

    <!-- Desktop User Menu -->
    <flux:dropdown class="hidden lg:block" position="bottom" align="start">
        <flux:profile
            :name="auth()->user()->name"
            :initials="auth()->user()->initials()"
            icon:trailing="chevrons-up-down"
        />

        <flux:menu class="w-[220px]">
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <flux:menu.radio.group>
                <flux:menu.item :href="route('wallets.index')" icon="wallet"
                                wire:navigate>{{ __('Wallets') }}</flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <flux:menu.radio.group>
                <flux:menu.item :href="route('settings.profile')" icon="cog"
                                wire:navigate>{{ __('Settings') }}</flux:menu.item>
            </flux:menu.radio.group>


            <flux:menu.separator/>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:sidebar>

<!-- Mobile User Menu -->
<flux:header class="lg:hidden">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left"/>

    <flux:spacer/>

    @if(request()->routeIs('orders.create'))
        <flux:modal.trigger name="basket">
            <flux:button variant="primary" color="orange" class="me-2" size="sm" icon="shopping-bag"/>
        </flux:modal.trigger>
    @endif

    <flux:dropdown position="top" align="end">
        <flux:profile
            :initials="auth()->user()->initials()"
            icon-trailing="chevron-down"
        />

        <flux:menu>
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <flux:menu.radio.group>
                <flux:menu.item :href="route('settings.profile')" icon="cog"
                                wire:navigate>{{ __('Settings') }}</flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>

{{ $slot }}

@fluxScripts
@livewireScripts
</body>
</html>
