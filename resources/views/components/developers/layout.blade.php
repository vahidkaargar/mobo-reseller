<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item :current="request()->routeIs('developers.tokens')" icon="key" :href="route('developers.tokens')" wire:navigate>{{ __('API Tokens') }}</flux:navlist.item>
            <flux:navlist.item :current="request()->routeIs('developers.documentation')" icon="document" :href="route('developers.documentation')" wire:navigate>{{ __('Documentation') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-xl">
            {{ $slot }}
        </div>
    </div>
</div>
