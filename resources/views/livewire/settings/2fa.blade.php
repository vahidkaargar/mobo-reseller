<?php


use Livewire\Volt\Component;

new class extends Component {
    public string $step = 'disabled'; // disabled, setup, confirm, enabled
    public string $confirmCode = '';
    public bool $showRecoveryCodes = false;

    public function mount()
    {
        $user = auth()->user();

        if (!$user->two_factor_secret) {
            $this->step = 'disabled';
        } elseif (!$user->two_factor_confirmed_at) {
            $this->step = 'confirm';
        } else {
            $this->step = 'enabled';
        }
    }

    public function enableTwoFactor()
    {
        $this->dispatch('submitHiddenForm', 'enable-2fa-form');
    }

    public function confirmTwoFactor()
    {
        $this->validate([
            'confirmCode' => 'required|string|size:6'
        ]);

        $this->dispatch('submitConfirmForm', $this->confirmCode);

    }

    public function disableTwoFactor()
    {
        $this->dispatch('submitHiddenForm', 'disable-2fa-form');
    }

    public function regenerateRecoveryCodes()
    {
        $this->dispatch('submitHiddenForm', 'regenerate-codes-form');
    }
}; ?>

<div>
    <section class="w-full">
        @include('partials.settings-heading')

        <x-settings.layout :heading="__('Two-Factor Authentication')"
                           :subheading="__('Add an extra layer of security to your account')">

            @if (session()->has('errors'))
                <flux:callout variant="danger" icon="exclamation-circle">
                    <flux:callout.heading>Something's wrong</flux:callout.heading>
                    <flux:callout.text>
                        <ul class="mt-2 text-sm list-disc list-inside space-y-1">
                            @foreach(session('errors')->confirmTwoFactorAuthentication->get('code') as  $error)
                                <li>{{$error}}</li>
                            @endforeach
                        </ul>
                    </flux:callout.text>
                </flux:callout>
            @endif

            @if ($step === 'disabled')
                <div class="space-y-6">
                    <div class="flex items-start gap-4 mt-8">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-amber-600 rounded-full flex items-center justify-center">
                                <flux:icon.shield-check/>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Two-Factor Authentication is currently disabled
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Enable two-factor authentication to add an extra layer of security to your account.
                            </p>
                        </div>
                    </div>

                    <flux:callout icon="information-circle">
                        <flux:callout.heading>What you'll need</flux:callout.heading>
                        <flux:callout.text>
                            <ul class="mt-2 text-sm list-disc list-inside space-y-1">
                                <li>An authenticator app (Google Authenticator, Authy, etc.)</li>
                                <li>Your phone or device with the app installed</li>
                                <li>A few minutes to complete the setup</li>
                            </ul>
                            <div class="text-center mt-6 mb-2">
                                <form wire:submit="enableTwoFactor">
                                    <flux:button type="submit" variant="primary" color="green" icon="shield-check">
                                        Enable Two-Factor Authentication
                                    </flux:button>
                                </form>
                            </div>
                        </flux:callout.text>
                    </flux:callout>

                </div>
            @endif

            @if ($step === 'setup' || $step === 'confirm')
                <!-- Step 2: Setup Process -->
                <div class="space-y-8 mt-8">

                    <div class="grid lg:grid-cols-2 gap-8">
                        <!-- QR Code Section -->
                        <flux:card class="px-0 py-4">
                            <div class="text-center space-y-2">
                                <h3 class="text-lg font-medium">Scan QR Code</h3>
                                <p class="text-sm opacity-60">Use your authenticator app<br>to scan this QR code</p>
                                <div class="bg-white/10 p-2 rounded-lg inline-block">
                                    {!! auth()->user()->twoFactorQrCodeSvg() !!}
                                </div>
                                <div class="text-xs space-y-1 mt-6">
                                    <p class="opacity-50">Can't scan?<br>Enter this code manually</p>
                                    <div class="mx-4">
                                        <flux:input size="xs" :value="decrypt(auth()->user()->two_factor_secret)"
                                                    readonly copyable/>
                                    </div>
                                </div>
                            </div>
                        </flux:card>

                        <!-- Instructions Section -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium mb-4">Setup Instructions</h3>
                                <ol class="space-y-3 text-sm">
                                    <li class="flex items-start gap-3">
                                    <span
                                        class="flex-shrink-0 w-6 h-6 bg-black text-white opacity-30 rounded-full flex items-center justify-center text-xs font-medium">1</span>
                                        <span class="opacity-50">Open your authenticator app (Google Authenticator, Authy, etc.)</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                    <span
                                        class="flex-shrink-0 w-6 h-6 bg-black text-white opacity-30 rounded-full flex items-center justify-center text-xs font-medium">2</span>
                                        <span class="opacity-50">Tap the "+" or "Add account" button</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                    <span
                                        class="flex-shrink-0 w-6 h-6 bg-black text-white opacity-30 rounded-full flex items-center justify-center text-xs font-medium">3</span>
                                        <span
                                            class="opacity-50">Choose "Scan QR code" and scan the code on this page</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                    <span
                                        class="flex-shrink-0 w-6 h-6 bg-black text-white opacity-30 rounded-full flex items-center justify-center text-xs font-medium">4</span>
                                        <span
                                            class="opacity-50">Enter the 6-digit code from your app below to verify</span>
                                    </li>
                                </ol>
                            </div>

                            @if ($step === 'confirm')
                                <!-- Verification Form -->
                                <flux:card>
                                    <form wire:submit="confirmTwoFactor" class="space-y-4 opacity-70">
                                        <flux:input
                                            wire:model="confirmCode"
                                            label="Enter 6-digit code"
                                            type="text"
                                            inputmode="numeric"
                                            placeholder="123456"
                                            maxlength="6"
                                            pattern="[0-9]*"
                                            autofocus
                                            required
                                            clearable
                                        />
                                        <flux:button type="submit" variant="primary" color="green" class="w-full"
                                                     icon="check-circle">
                                            Verify & Enable
                                        </flux:button>
                                    </form>
                                </flux:card>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($step === 'enabled')
                <!-- Step 3: Two-Factor Enabled -->
                <div class="space-y-6">
                    <!-- Status Banner -->
                    <flux:callout variant="success" icon="shield-check" class="border-0">
                        <flux:callout.heading>Two-Factor Authentication is Active</flux:callout.heading>
                        <flux:callout.text>
                            Your account is protected with two-factor authentication.
                        </flux:callout.text>
                    </flux:callout>

                    <!-- Recovery Codes -->
                    <flux:card>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium">Recovery Codes</h3>
                                    <p class="text-sm opacity-50">
                                        Store these codes in a safe place. They can be used to access your account if
                                        you
                                        lose your authenticator device.
                                    </p>
                                </div>
                                <flux:button wire:click="regenerateRecoveryCodes" size="sm" variant="filled"
                                             icon="arrow-path">
                                    Regenerate
                                </flux:button>
                            </div>

                            <flux:callout class="opacity-70 border-0"
                                          size="sm"
                                          variant="warning"
                                          icon="exclamation-triangle"
                                          text="Your recovery codes are one-time only. Protect them by storing them securely."/>

                            <flux:card class="p-2 opacity-75">
                                <div class="grid grid-cols-2 gap-2 text-sm font-mono">
                                    @foreach ((array) auth()->user()->recoveryCodes() as $code)
                                        <flux:input :value="$code" copyable readonly/>
                                    @endforeach
                                </div>
                            </flux:card>

                        </div>
                    </flux:card>

                    <!-- Disable Section -->
                    <flux:callout variant="danger" icon="shield-check">
                        <flux:callout.heading>Disable Two-Factor Authentication</flux:callout.heading>
                        <flux:callout.text>
                            Remove two-factor authentication from your account. This will make your account less secure.
                            <div class="mt-5 mb-2 text-end">
                                <flux:modal.trigger name="confirm-disable-two-factor">
                                    <flux:button variant="danger" size="sm">
                                        Disable Two-Factor Authentication
                                    </flux:button>
                                </flux:modal.trigger>
                            </div>
                        </flux:callout.text>
                    </flux:callout>
                    <flux:modal name="confirm-disable-two-factor" class="min-w-[22rem] max-w-md">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Disable Two-Factor Authentication?</flux:heading>
                                <flux:text class="mt-2">
                                    <p class="opacity-50 text-sm">
                                        Are you sure you want to disable two-factor authentication on your account?
                                        This will make your account less secure.
                                    </p>
                                </flux:text>
                            </div>
                            <div class="flex gap-2">
                                <flux:spacer/>
                                <flux:modal.close>
                                    <flux:button variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button wire:click="disableTwoFactor" type="submit" variant="danger">Disable
                                    Two-Factor
                                </flux:button>
                            </div>
                        </div>
                    </flux:modal>
                </div>
            @endif

        </x-settings.layout>

        <!-- Hidden Forms for 2FA Actions -->
        <form id="enable-2fa-form" method="POST" action="/user/two-factor-authentication" style="display: none;">
            @csrf
        </form>

        <form id="disable-2fa-form" method="POST" action="/user/two-factor-authentication" style="display: none;">
            @csrf
            @method('DELETE')
        </form>

        <form id="regenerate-codes-form" method="POST" action="/user/two-factor-recovery-codes" style="display: none;">
            @csrf
        </form>

        <form id="confirm-2fa-form" method="POST" action="/user/confirmed-two-factor-authentication"
              style="display: none;">
            @csrf
            <input type="hidden" name="code" id="confirm-code-input">
        </form>

        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('submitHiddenForm', (formId) => {
                    document.getElementById(formId).submit();
                });

                Livewire.on('submitConfirmForm', (code) => {
                    document.getElementById('confirm-code-input').value = code;
                    document.getElementById('confirm-2fa-form').submit();
                });
            });
        </script>
    </section>
</div>


