@props(['activeFilters' => []])

@php $af = $activeFilters; @endphp

<div
    x-show="showAdvancedFilter"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showAdvancedFilter = false"></div>

    <div
        class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150 transform"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        @click.stop
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/40">
                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 .707 1.707L13 13.414V20a1 1 0 0 1-1.447.894l-4-2A1 1 0 0 1 7 18v-4.586L3.293 5.707A1 1 0 0 1 3 5V4z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-zinc-900 dark:text-white">Advanced Filters</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Narrow down your order results</p>
                </div>
            </div>
            <button @click="showAdvancedFilter = false" class="rounded-xl p-2 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">
            @php
                $lc  = 'mb-3 block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400';
                $chk = 'flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm text-zinc-700 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white';
                $inp = 'w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500';
                $cb  = 'h-4 w-4 flex-shrink-0 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700';
                $sec = 'rounded-xl border border-zinc-100 bg-zinc-50/60 p-4 dark:border-zinc-700/60 dark:bg-zinc-800/40';
            @endphp

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

                {{-- Stock Filter --}}
                <div class="{{ $sec }}">
                    <span class="{{ $lc }}">Stock Filter</span>
                    <div class="space-y-0.5">
                        @foreach(['in_stock'=>'In Stock','out_of_stock'=>'Out Of Stock','excess_stock'=>'Excess Stock'] as $v=>$l)
                            <label class="{{ $chk }}"><input type="checkbox" wire:model="stockFilter" value="{{ $v }}" class="{{ $cb }}"/>{{ $l }}</label>
                        @endforeach
                    </div>
                </div>

                {{-- Drug Tariff Category --}}
                <div class="{{ $sec }}">
                    <span class="{{ $lc }}">Drug Tariff Category</span>
                    <div class="space-y-0.5">
                        @foreach(['Generics','CAT-C Brands','CAT-H Brands','Part IX Appliances','ZD'] as $c)
                            <label class="{{ $chk }}"><input type="checkbox" wire:model="dtCategory" value="{{ $c }}" class="{{ $cb }}"/>{{ $c }}</label>
                        @endforeach
                    </div>
                </div>

                {{-- Flag Filter --}}
                <div class="{{ $sec }}">
                    <span class="{{ $lc }}">Flag</span>
                    <div class="space-y-0.5">
                        @foreach(['red'=>['l'=>'Red Flag','d'=>'bg-red-500'],'green'=>['l'=>'Green Flag','d'=>'bg-green-500'],'black'=>['l'=>'Black Flag','d'=>'bg-zinc-800'],'blue'=>['l'=>'Blue Flag','d'=>'bg-blue-500'],'none'=>['l'=>'No Flag','d'=>'bg-zinc-300']] as $v=>$f)
                            <label class="{{ $chk }}">
                                <input type="checkbox" wire:model="flagFilter" value="{{ $v }}" class="{{ $cb }}"/>
                                <span class="h-3 w-3 flex-shrink-0 rounded-full {{ $f['d'] }}"></span>{{ $f['l'] }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Supplier --}}
                <div class="{{ $sec }} sm:col-span-2 lg:col-span-1">
                    <span class="{{ $lc }}">Supplier</span>
                    <div class="grid grid-cols-2 gap-0.5">
                        @foreach(['AAH','Alliance','BNS','Cavendish','DayLewis','Phoenix','Smartway','Target','TestSuppliers'] as $s)
                            <label class="{{ $chk }}"><input type="checkbox" wire:model="supplierFilter" value="{{ $s }}" class="{{ $cb }}"/>{{ $s }}</label>
                        @endforeach
                    </div>
                </div>

                {{-- Category --}}
                <div class="{{ $sec }} sm:col-span-2">
                    <span class="{{ $lc }}">Category</span>
                    <div class="grid grid-cols-3 gap-0.5">
                        @foreach(['Brand','Branded Generics','CD2','CD3','CD4','CD5','Fridge','Generic','OTC','Surgical','Other'] as $c)
                            <label class="{{ $chk }}"><input type="checkbox" wire:model="categoryFilter" value="{{ $c }}" class="{{ $cb }}"/>{{ $c }}</label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Price filters --}}
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="{{ $sec }}">
                    <label class="{{ $lc }}">Unit Price Above (£)</label>
                    <input type="number" min="0" step="0.01" wire:model="unitPriceAbove" placeholder="e.g. 10.00" class="{{ $inp }}"/>
                </div>
                <div class="{{ $sec }}">
                    <label class="{{ $lc }}">Quantity Above</label>
                    <input type="number" min="0" step="1" wire:model="quantityAbove" placeholder="e.g. 100" class="{{ $inp }}"/>
                </div>
                <div class="{{ $sec }}">
                    <label class="{{ $lc }}">Price × Qty Above (£)</label>
                    <input type="number" min="0" step="0.01" wire:model="unitPriceQtyAbove" placeholder="e.g. 500.00" class="{{ $inp }}"/>
                </div>
            </div>

            {{-- Additional --}}
            <div class="mt-4 {{ $sec }}">
                <span class="{{ $lc }}">Additional Filters</span>
                <div class="flex flex-wrap gap-4">
                    <label class="{{ $chk }}"><input type="checkbox" wire:model="orderedAboveDT" class="{{ $cb }}"/>Ordered above Drug Tariff</label>
                    <label class="{{ $chk }}"><input type="checkbox" wire:model="orderedAboveDTClawback" class="{{ $cb }}"/>Ordered above Drug Tariff Clawback</label>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <button wire:click="resetFilters" class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition-colors hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Reset All
            </button>
            <button wire:click="applyAdvancedFilters" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Apply Filters
            </button>
        </div>
    </div>
</div>
