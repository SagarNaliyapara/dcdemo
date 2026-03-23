@props([
    'dateFilter',
    'startDate'             => '',
    'endDate'               => '',
    'search'                => '',
    'hasActiveFilters'      => false,
    'canSchedule'           => false,
    'perPage'               => 25,
    'activeFilters'         => [],
])

@php
    $af = $activeFilters;
@endphp

<div class="flex flex-col gap-3">

    {{-- ── Row 1: Search + Date + Buttons + Per-page ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

        {{-- Search --}}
        <div class="relative w-full sm:w-72">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                <svg class="h-4 w-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search orders, PIP, supplier…"
                class="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-9 pr-4 text-sm text-zinc-900 placeholder-zinc-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500"
            />
        </div>

        {{-- Date filter --}}
        <div class="flex items-center gap-2">
            <label class="whitespace-nowrap text-xs font-medium text-zinc-500 dark:text-zinc-400">Date range</label>
            <select
                wire:model.live="dateFilter"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            >
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="last3days">Last 3 Days</option>
                <option value="last7days">Last 7 Days</option>
                <option value="thismonth">This Month</option>
                <option value="lastmonth">Last Month</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>

        {{-- Advanced filter --}}
        <button
            @click="showAdvancedFilter = true"
            class="relative inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium shadow-sm transition-colors
                {{ $hasActiveFilters
                    ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-500 dark:bg-blue-900/30 dark:text-blue-300'
                    : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 .707 1.707L13 13.414V20a1 1 0 0 1-1.447.894l-4-2A1 1 0 0 1 7 18v-4.586L3.293 5.707A1 1 0 0 1 3 5V4z"/>
            </svg>
            Advanced Filter
            @if($hasActiveFilters)
                <span class="absolute -right-1.5 -top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[9px] font-bold text-white">!</span>
            @endif
        </button>

        {{-- Schedule Report button --}}
        @if($canSchedule)
            <button
                @click="showScheduleModal = true"
                class="inline-flex items-center gap-2 rounded-lg border border-emerald-500 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 shadow-sm transition-colors hover:bg-emerald-100 dark:border-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Schedule Report
            </button>
        @elseif($dateFilter === 'custom')
            <button
                disabled
                title="Scheduled reports are not available for custom date ranges."
                class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-500"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Schedule Report
                <span class="rounded bg-zinc-200 px-1 py-0.5 text-[9px] font-bold uppercase text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">N/A</span>
            </button>
        @endif

        {{-- Per page --}}
        <div class="ml-auto flex items-center gap-2">
            <label class="whitespace-nowrap text-xs font-medium text-zinc-500 dark:text-zinc-400">Show</label>
            <select
                wire:model.live="perPage"
                class="rounded-lg border border-zinc-300 bg-white px-2 py-2 text-sm text-zinc-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    {{-- ── Custom date range ── --}}
    @if($dateFilter === 'custom')
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Start</label>
                <input type="date" wire:model.live="startDate" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"/>
            </div>
            <span class="text-zinc-400">→</span>
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">End</label>
                <input type="date" wire:model.live="endDate" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"/>
            </div>
        </div>
    @endif

    {{-- ── Active filter tags ── --}}
    @if($hasActiveFilters)
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Active filters:</span>
            @foreach($af['stockFilter'] ?? [] as $f)
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Stock: {{ $f }}</span>
            @endforeach
            @foreach($af['supplierFilter'] ?? [] as $f)
                <span class="inline-flex items-center rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-900/40 dark:text-violet-300">{{ $f }}</span>
            @endforeach
            @foreach($af['categoryFilter'] ?? [] as $f)
                <span class="inline-flex items-center rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-800 dark:bg-teal-900/40 dark:text-teal-300">{{ $f }}</span>
            @endforeach
            @if($af['orderedAboveDT'] ?? false)
                <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900/40 dark:text-orange-300">Above DT</span>
            @endif
            @if($af['orderedAboveDTClawback'] ?? false)
                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/40 dark:text-red-300">Above Clawback</span>
            @endif
            <button wire:click="resetFilters" class="ml-1 text-xs font-medium text-red-600 underline-offset-2 hover:underline dark:text-red-400">Clear all</button>
        </div>
    @endif

</div>
