<x-layouts.auth title="Two-Factor Authentication">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Two-Factor Authentication')"
                       :description="__('Please confirm access to your account by entering the authentication code provided by your authenticator application.')"/>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')"/>

        <form method="POST" action="{{ route('two-factor.login') }}" class="flex flex-col gap-6" x-data="{ recovery: false }">
            @csrf

            <!-- Authentication Code -->
            <div x-show="! recovery">
                <flux:input
                    name="code"
                    :label="__('Authentication Code')"
                    type="text"
                    inputmode="numeric"
                    autofocus
                    autocomplete="one-time-code"
                    placeholder="123456"
                    maxlength="6"
                    pattern="[0-9]*"
                />
            </div>

            <!-- Recovery Code -->
            <div x-show="recovery" style="display: none;">
                <flux:input
                    name="recovery_code"
                    :label="__('Recovery Code')"
                    type="text"
                    autocomplete="one-time-code"
                    placeholder="abcd-efgh-ijkl"
                    x-ref="recovery_code"
                />
            </div>

            <div class="flex flex-col gap-4">
                <!-- Submit Button -->
                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Verify') }}
                </flux:button>

                <!-- Toggle Recovery Code -->
                <div class="text-center">
                    <flux:link
                        x-show="! recovery"
                        x-on:click.prevent="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })"
                        href="#"
                        class="text-sm">
                        {{ __('Use a recovery code') }}
                    </flux:link>

                    <flux:link
                        x-show="recovery"
                        x-on:click.prevent="recovery = false"
                        href="#"
                        class="text-sm"
                        style="display: none;">
                        {{ __('Use an authentication code') }}
                    </flux:link>
                </div>
            </div>
        </form>
    </div>
</x-layouts.auth>
