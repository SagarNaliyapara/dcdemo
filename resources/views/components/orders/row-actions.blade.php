@props(['selectedCount' => 0])

@php
    $bb = 'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium shadow-sm transition-colors disabled:pointer-events-none disabled:opacity-40';
    $bp = 'border-blue-500 bg-blue-600 text-white hover:bg-blue-700';
    $bg = 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600';
    $dis = $selectedCount === 0 ? 'disabled' : '';
@endphp

<div class="flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
    <span class="mr-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
        Actions
        @if($selectedCount > 0)
            <span class="ml-1 rounded-full bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $selectedCount }}</span>
        @endif
    </span>

    <button wire:click="reOrder" {{ $dis }} class="{{ $bb }} {{ $bp }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>Re Order
    </button>
    <button wire:click="addToExcessStock" {{ $dis }} class="{{ $bb }} {{ $bg }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
        </svg>Add To Excess Stock
    </button>
    <button wire:click="addToBulkOrder" {{ $dis }} class="{{ $bb }} {{ $bg }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>Add To Bulk Order
    </button>
    <button wire:click="addToAvailabilityTracker" {{ $dis }} class="{{ $bb }} {{ $bg }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>Add To Availability Tracker
    </button>
    <button wire:click="addToReturnsManagement" {{ $dis }} class="{{ $bb }} {{ $bg }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
        </svg>Add To Returns Management
    </button>
</div>
