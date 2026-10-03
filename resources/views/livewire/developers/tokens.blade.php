<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use App\Enums\{TransactionTypeEnum, TransactionExecutorEnum};
use App\Models\User;
use Illuminate\Support\Number;
use Flux\Flux;


new class extends Component {

    public string $name;
    public Collection $tokens;
    public string $newToken;

    public function mount(): void
    {
        $this->newToken = '';
        $this->getTokens();
    }

    protected function tokens()
    {
        return auth()->user()->tokens();
    }

    protected function getTokens(): void
    {
        $this->tokens = auth()->user()->tokens()->get();
    }

    public function generateNewToken(): void
    {
        if (auth()->user()->has_api) {
            $validated = $this->validate([
                'name' => 'required|alpha_num',
            ]);
            $this->newToken = auth()->user()->createToken(Str::upper($this->name))->plainTextToken;
            $this->getTokens();
            Flux::toast(text: "The API token has been created successfully.", variant: 'success', position: 'bottom end');
        }
    }

    public function deleteToken($tokenId): void
    {
        $this->tokens()->where('id', $tokenId)->delete();
        $this->getTokens();
        Flux::toast(text: "The API token was removed successfully.", variant: 'success', position: 'bottom end');
    }

}; ?>

<section class="w-full">
    <x-developers.layout
        :heading="__('API Tokens')"
        :subheading="__('Manage API tokens for secure authenticated access.')">

        @if(filled($this->tokens))
            <div class="my-6 w-full space-y-6 text-end">
                <flux:modal.trigger name="generate-new-token">
                    <flux:button variant="primary">{{ __('Generate new token') }}</flux:button>
                </flux:modal.trigger>
            </div>
        @endif

        <flux:modal name="generate-new-token" class="md:w-96" :dismissible="false">
            @if(auth()->user()->has_api)
                @if(filled($this->newToken))
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Token is ready') }}</flux:heading>
                        </div>
                        <flux:callout icon="exclamation-circle" variant="warning">
                            <flux:callout.heading>Important</flux:callout.heading>
                            <flux:callout.text>
                                <p>Copy your API token now. You won’t be able to view it again.</p>
                            </flux:callout.text>
                        </flux:callout>
                        <flux:input icon="key" label="API Token" :value="$this->newToken" readonly copyable/>
                    </div>
                @else
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Generate new token') }}</flux:heading>
                            <flux:text class="mt-2">{{ __('Provide a project name for your use case.') }}</flux:text>
                        </div>
                        <form wire:submit="generateNewToken" class="space-y-6">
                            <flux:field>
                                <flux:label badge="Required">{{ __('Project name') }}</flux:label>
                                <flux:input wire:model="name" type="text"/>
                                <flux:error name="name"/>
                            </flux:field>
                            <div class="flex gap-2">
                                <flux:spacer/>
                                <flux:modal.close>
                                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button
                                    type="submit"
                                    variant="primary"
                                    icon="key">
                                    {{ __('Generate') }}
                                </flux:button>
                            </div>
                        </form>
                    </div>
                @endif
            @else
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Generate new token') }}</flux:heading>
                        <flux:text class="mt-2">{{ __('To generate an API token, you must request it via a support ticket.') }}</flux:text>
                    </div>
                    <div class="text-end">
                        <flux:button
                            variant="primary"
                            icon="lifebuoy">
                            {{ __('Create Ticket') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </flux:modal>


        @if(filled($this->tokens))
            <flux:card class="space-y-6">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-8">#</flux:table.column>
                        <flux:table.column>{{ __('Project') }}</flux:table.column>
                        <flux:table.column class="w-45">{{ __('Created at') }}</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->tokens as $token)
                            <flux:table.row :key="$loop->iteration">
                                <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $token->name }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $token->created_at }}</flux:table.cell>
                                <flux:table.cell class="w-10">
                                    <flux:modal.trigger name="delete-token-{{ $token->id }}">
                                        <flux:button variant="ghost" size="sm" icon="trash" inset="top bottom"/>
                                    </flux:modal.trigger>
                                    <flux:modal name="delete-token-{{ $token->id }}" class="min-w-[22rem]">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Delete API token?</flux:heading>
                                                <flux:text class="mt-2">
                                                    You're about to delete
                                                    <strong class="uppercase">{{ $token->name }}</strong>.
                                                    This action cannot be reversed.
                                                </flux:text>
                                            </div>
                                            <form wire:submit="deleteToken" class="flex gap-2">
                                                <flux:spacer/>
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger"
                                                             @click="$wire.deleteToken('{{ $token->id }}')">
                                                    Delete token
                                                </flux:button>
                                            </form>
                                        </div>
                                    </flux:modal>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @else
            <flux:callout class="my-8" icon="key">
                <flux:callout.heading>No API tokens available</flux:callout.heading>
                <flux:callout.text>
                    To begin using our API, you must first generate an access token. This token
                    authenticates your requests and secures your API communication.
                </flux:callout.text>
                <x-slot name="actions">
                    <flux:modal.trigger name="generate-new-token">
                        <flux:button>Generate token</flux:button>
                    </flux:modal.trigger>
                    <flux:button variant="ghost" :href="route('developers.documentation')">Documentation</flux:button>
                </x-slot>
            </flux:callout>
        @endif

    </x-developers.layout>
</section>

