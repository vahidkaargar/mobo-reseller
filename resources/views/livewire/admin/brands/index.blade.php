<?php

use App\Models\BambooBrand;
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
    public string $currency = '';
    public string $country = '';
    public $countryDatum;

    public function mount()
    {
        $this->countryDatum = (new Countries())->whereIn('cca2', $this->countries())->values();
    }

    public function updated(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    #[Computed]
    private function brands(): LengthAwarePaginator
    {
        return BambooBrand::query()
            ->select(['brand_id', 'name', 'image', 'country', 'currency', 'updated_at'])
            ->when(filled($this->search), function (Builder $query) {
                $query->where('name', 'like', "%$this->search%");
            })
            ->when(filled($this->currency), function (Builder $query) {
                $query->where('currency', $this->currency);
            })
            ->when(filled($this->country), function (Builder $query) {
                $query->where('country', $this->country);
            })
            ->orderBy('name')
            ->paginate();
    }

    #[Computed]
    private function currencies()
    {
        return BambooBrand::query()->pluck('currency')->unique()->sort();
    }

    #[Computed]
    private function countries()
    {
        return BambooBrand::query()->pluck('country')->values()->unique()->sort();
    }
}; ?>
<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl max-w-6xl">

        <div class="relative overflow-hidden rounded-xl bg-linear-to-br dark:from-zinc-700 from-zinc-200">
            <div class="px-4 md:px-8 py-10 relative opacity-60">
                <flux:heading size="xl" class="dark:text-shadow-lg/30">
                    Brands
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
                <div class="col-span-2">
                    <flux:select
                        variant="listbox"
                        wire:model.live="country"
                        searchable
                        clearable
                        placeholder="Country">
                        @foreach($this->countries as $country)
                            <flux:select.option :value="$country">
                                {{ $countryDatum->where('cca2', $country)->value('name.common') ?? $country }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:select
                    variant="listbox"
                    wire:model.live="currency"
                    searchable
                    clearable
                    placeholder="Currency">
                    @foreach($this->currencies as $currency)
                        <flux:select.option :value="$currency">{{$currency}}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:table
                    class="p-4 md:p-8"
                    :paginate="$this->brands"
                    wire:key="brands-{{ md5(json_encode([$search, $country, $currency])) }}">
                    <flux:table.columns>
                        <flux:table.column>{{__('Brand name')}}</flux:table.column>
                        <flux:table.column align="start" width="120">{{__('Country')}}</flux:table.column>
                        <flux:table.column align="center" width="120">{{__('Currency')}}</flux:table.column>
                        <flux:table.column align="center" width="160">{{__('Updated at')}}</flux:table.column>
                        <flux:table.column align="center" width="80"></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->brands as $brand)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a class="flex items-center gap-3" href="{{route('admin.brands.show', $brand)}}">
                                        @if(filled($brand->image))
                                            <img
                                                alt="{{ $brand->name }}"
                                                loading="lazy"
                                                class="rounded border border-zinc-600"
                                                width="24"
                                                height="24"
                                                src="{{route('thumbnail', ['url' => $brand->image, 'w' => 32, 'h' => 32, 'q' => 95, 'fit' => 'cover'])}}">
                                        @else
                                            <flux:avatar size="xs" icon="gift"/>
                                        @endif
                                        {{ $brand->name }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell align="start">
                                    <div class="flex items-center gap-2">
                                        <i class="fi fi-{{strtolower($brand->country)}} rounded"></i>
                                        {{ $countryDatum->where('cca2', $brand->country)->value('name.common') ?? $brand->country }}
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    {{ $brand->currency }}
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    {{ $brand->updated_at->diffForHumans() }}
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    <flux:button
                                        :href="route('admin.brands.show', $brand)"
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
