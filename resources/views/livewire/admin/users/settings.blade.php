<?php

use App\Models\BambooBrand;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;
use PragmaRX\Countries\Package\Countries;
use App\Enums\{TransactionTypeEnum, TransactionExecutorEnum, BambooOrderStatusEnum};

new class extends Component {
    use WithPagination;

    public User $user;

    #[Validate(['required', 'numeric', 'between:0.01,9.99'])]
    public float $fee_percentage;

    #[Validate(['required', 'boolean'])]
    public bool $is_active;

    #[Validate(['required', 'boolean'])]
    public bool $can_place_order;


    #[Validate(['required', 'boolean'])]
    public bool $has_api;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->can_place_order = $user->can_place_order;
        $this->fee_percentage = $user->fee_percentage;
        $this->is_active = $user->is_active;
        $this->has_api = $user->has_api;
    }

    public function submitSettings()
    {
        $this->validate();

        $this->user->fee_percentage = $this->fee_percentage;
        $this->user->is_active = $this->is_active;
        $this->user->can_place_order = $this->can_place_order;
        $this->user->has_api = $this->has_api;

        $text = 'Something went wrong.';
        $variant = 'danger';
        if ($this->user->save()) {
            $text = 'Settings have been updated successfully.';
            $variant = 'success';
        }
        Flux::toast(text: $text, heading: "Settings", variant: $variant, position: 'bottom end');
    }


}; ?>
<section class="w-full">
    <x-admin.users.layout
        :user="$user"
        :heading="__('Settings')"
        :subheading="__('You can change user settings')">

        <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200">
            <form wire:submit="submitSettings" class="p-4 md:p-8 space-y-10 max-w-md">
                <flux:field>
                    <flux:label>Preset Product Fee</flux:label>
                    <flux:description>
                        Fee percentage field must be between
                        <flux:badge size="sm">0.01 - 9.99</flux:badge>
                    </flux:description>
                    <flux:input.group>
                        <flux:input wire:model="fee_percentage"/>
                        <flux:input.group.suffix class="gap-2">
                            <flux:icon.receipt-percent variant="mini"/>
                            Percentage
                        </flux:input.group.suffix>
                    </flux:input.group>
                    <flux:error name="fee_percentage"/>
                </flux:field>

                <flux:switch
                    wire:model="can_place_order"
                    label="Can Place Order"
                    description="Once permission is granted, the user can place orders."/>

                <flux:switch
                    wire:model="has_api"
                    label="API Access"
                    description="Once permission is granted, the user can create API token."/>

                <flux:switch
                    wire:model="is_active"
                    label="User Activation"
                    description="Once deactivated, the user can no longer log into the reseller portal or use the API."/>

                <div class="text-end">
                    <flux:button type="submit" icon="pencil-square" variant="primary">Update</flux:button>
                </div>
            </form>

        </div>

    </x-admin.users.layout>

</section>
