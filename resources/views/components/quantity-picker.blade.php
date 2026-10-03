@props([
    'min' => 1,
    'max' => 99,
    'disabled' => false,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $entangleModel = $attributes->wire('model');
@endphp

<div
    x-data="{
        qty: @entangle($entangleModel),
        min: Number({{ $min }}),
        max: Number({{ $max }}),
        isDisabled: {{ $disabled ? 'true' : 'false' }},

        sanitize(value) {
            // Remove everything except digits
            value = value.replace(/[^0-9]/g, '');

            // Convert to integer
            value = Number(value);

            // If empty after cleaning, fallback to min
            if (isNaN(value)) value = this.min;

            // Enforce min/max
            value = Math.max(this.min, Math.min(value, this.max));

            return value;
        },

        onInput(e) {
            let clean = this.sanitize(e.target.value);
            this.qty = clean;
            e.target.value = clean;

            // Manually trigger Livewire for flux input
            const ev = new Event('input', { bubbles: true });
            e.target.dispatchEvent(ev);
        },

        decrease() {
            if (this.isDisabled) return;
            this.qty = Math.max(this.min, Number(this.qty) - 1);
        },

        increase() {
            if (this.isDisabled) return;
            this.qty = Math.min(this.max, Number(this.qty) + 1);
        }
    }"
>
    <flux:input.group>

        <flux:button
            icon="minus"
            class="px-1"
            x-bind:class="(isDisabled || qty <= min)
                ? 'opacity-50 cursor-not-allowed'
                : ''"
            @click="decrease()"
            x-bind:disabled="isDisabled || qty <= min"
        />

        <flux:input
            x-model="qty"
            {{$attributes->thatStartWith('wire:model')}}
            inputmode="numeric"
            :loading="false"
            class:input="text-center"
            x-bind:disabled="isDisabled"
            x-on:input="onInput($event)"
        />

        <flux:button
            icon="plus"
            class="px-1"
            x-bind:class="(isDisabled || qty >= max)
                ? 'opacity-50 cursor-not-allowed'
                : ''"
            @click="increase()"
            x-bind:disabled="isDisabled || qty >= max"
        />

    </flux:input.group>
</div>
