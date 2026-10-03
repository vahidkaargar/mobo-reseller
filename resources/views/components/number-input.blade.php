@props([
    'min' => -9_000_000_000_000_000_000,
    'max' => 9_000_000_000_000_000_000,
    'decimals' => 0,
    'disabled' => false,
    'placeholder' => null,
    'description' => null,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $entangleModel = $attributes->wire('model');
@endphp

<div
    class="relative"
    x-data="{
        value: @entangle($entangleModel),
        min: Number({{ $min }}),
        max: Number({{ $max }}),
        decimals: Number({{ $decimals }}),
        disabled: {{ $disabled ? 'true' : 'false' }},

        invalidState: false,
        error: '',

        cleanInput(val) {
            return val.replace(/[^0-9.]/g, '');
        },

        validate(val) {
            if (val === '' || val === null) {
                this.error = '';
                this.invalidState = false;
                return null;
            }

            const num = Number(val);

            if (isNaN(num)) {
                this.error = 'Invalid number';
                this.invalidState = true;
                return null;
            }

            if (num < this.min || num > this.max) {
                this.error = `Amount must be between ${this.min} – ${this.max}`;
                this.invalidState = true;
            } else {
                this.error = '';
                this.invalidState = false;
            }

            if(this.invalidState){
                Flux.toast({
                    heading: 'Invalid amount',
                    text: this.error,
                    variant: 'danger',
                })
            }

            return num;
        },

        handleInput(e) {
            const cleaned = this.cleanInput(e.target.value);
            const validated = this.validate(cleaned);

            // keep what user typed
            e.target.value = cleaned;

            // update Livewire
            this.value = validated || 0;

            // force Livewire sync (shadow DOM)
            e.target.dispatchEvent(new Event('input', { bubbles: true }));
        },

        blockInvalidKeys(e) {
            const allowed = [
                '0','1','2','3','4','5','6','7','8','9',
                '.','Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'
            ];
            if (!allowed.includes(e.key)) e.preventDefault();
        }
    }"
>
    <flux:input
        x-ref="input"
        x-model="value"
        {{ $attributes->whereStartsWith('wire:model') }}

        x-on:input="handleInput($event)"
        x-on:keydown="blockInvalidKeys($event)"

        x-bind:disabled="disabled"

        {{-- This WORKS — FluxUI will react to :invalid="true" --}}
        x-bind:invalid="invalidState"

        class="text-center"
        class:input="text-center"
        inputmode="decimal"
        :placeholder="$placeholder"
        :loading="false"
    />

    @if($description)
        <small class="bg-black/50 opacity-50 rounded-lg absolute right-0 px-2 mt-0.5">
            {{ $description }}
        </small>
    @endif

    <input type="hidden" wire:model="{{ $entangleModel }}"/>

</div>
