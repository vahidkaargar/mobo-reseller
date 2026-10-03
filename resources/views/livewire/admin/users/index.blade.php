<?php

use App\Models\BambooBrand;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;
use PragmaRX\Countries\Package\Countries;
use App\Enums\{TransactionTypeEnum, TransactionExecutorEnum, BambooOrderStatusEnum};

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function mount()
    {

    }

    public function updated(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    #[Computed]
    private function users(): LengthAwarePaginator
    {
        return User::query()
            ->when(filled($this->search), function (Builder $query) {
                $query->whereAny(['name', 'email'], 'like', "%$this->search%");
            })
            ->paginate();
    }
}; ?>
<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl max-w-6xl">

        <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200">
            <div class="px-4 md:px-8 py-10 relative opacity-60">
                <flux:heading size="xl" class="dark:text-shadow-lg/30">
                    Users
                </flux:heading>
            </div>
            <div class="grid md:grid-cols-5 gap-4 p-4 md:p-8 max-w-180">
                <div class="col-span-2">
                    <flux:input
                        wire:model.live.debounce.500ms="search"
                        icon="magnifying-glass"
                        clearable
                        placeholder="Search..."/>
                </div>
            </div>
            <div>
                <flux:table
                    class="p-4 md:p-8"
                    :paginate="$this->users"
                    wire:key="brands-{{ md5(json_encode([$search])) }}">
                    <flux:table.columns>
                        <flux:table.column>{{__('Email')}}</flux:table.column>
                        <flux:table.column align="start" width="120">{{__('Name')}}</flux:table.column>
                        <flux:table.column align="center" width="120">{{__('Updated at')}}</flux:table.column>
                        <flux:table.column align="center" width="160">{{__('Created at')}}</flux:table.column>
                        <flux:table.column align="center" width="80"></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->users as $user)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a href="{{route('admin.users.show', $user)}}">
                                        @php($tooltip = filled($user->email_verified_at) ? ('Verified at: ' . $user->email_verified_at) : 'Not verified')
                                        <flux:tooltip
                                            @class(['text-green-600' => filled($user->email_verified_at), 'flex gap-2'])
                                            :content="$tooltip">
                                            <span>{{ $user->email }}</span>
                                            @if(filled($user->email_verified_at))
                                                <flux:icon.check-circle variant="mini"/>
                                            @endif
                                        </flux:tooltip>
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $user->name }}
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    {{ $user->updated_at->diffForHumans() }}
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    {{ $user->created_at->diffForHumans() }}
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    <flux:button
                                        :href="route('admin.users.show', $user)"
                                        variant="ghost"
                                        size="sm"
                                        icon="eye"></flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </div>
</div>
