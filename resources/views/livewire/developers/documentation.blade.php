<?php

use Illuminate\Support\Carbon;
use Livewire\Volt\Component;
use App\Enums\{TransactionTypeEnum, TransactionExecutorEnum};
use App\Models\User;
use Illuminate\Support\Number;
use Flux\Flux;


new class extends Component {

    public function mount(): void
    {

    }


}; ?>

<section class="w-full">
    <x-developers.layout :heading="__('Documentation')"
                         :subheading="__('Explore our API documentation to begin integration.')">

    </x-developers.layout>
</section>

