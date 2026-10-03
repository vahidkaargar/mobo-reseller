<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item
                :current="request()->routeIs('admin.users.show')"
                icon="user"
                :href="route('admin.users.show', $user)"
                wire:navigate>
                {{ __('Account Summary') }}
            </flux:navlist.item>
            <flux:navlist.item
                :current="request()->routeIs('admin.users.settings')"
                icon="cog-6-tooth"
                :href="route('admin.users.settings', $user)"
                wire:navigate>
                {{ __('Settings') }}
            </flux:navlist.item>
            <flux:navlist.item
                :current="request()->routeIs('admin.users.fees')"
                icon="receipt-percent"
                :href="route('admin.users.fees', $user)"
                wire:navigate>
                {{ __('Product Fees') }}
            </flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden mb-4"/>

    <div class="flex-1 self-stretch">
        @if(isset($heading))
            <div class="my-6 md:mt-0">
                <flux:heading>{{ $heading ?? '' }}</flux:heading>
                @if(isset($subheading))
                    <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
                @endif
            </div>
        @endif


        <div class="w-full">
            <div class="mb-4 rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200">
                <div class="p-4 md:p-8 relative opacity-60">
                    <div class="flex items-center gap-4">
                        <flux:avatar size="lg">
                            {{Number::percentage($user->fee_percentage, maxPrecision: 2)}}
                        </flux:avatar>
                        <div>
                            <flux:heading size="lg">{{ $user->email }}</flux:heading>
                            <flux:text>{{ $user->name }}</flux:text>
                        </div>
                    </div>
                </div>
            </div>

            {{ $slot }}
        </div>
    </div>

</div>
