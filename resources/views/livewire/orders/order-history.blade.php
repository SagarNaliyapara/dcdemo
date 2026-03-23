{{-- Single root element required by Livewire --}}
<div
    x-data="{
        showAdvancedFilter: false,
        showScheduleModal: false,
        init() {
            $wire.$on('close-advanced-filter', () => { this.showAdvancedFilter = false })
            $wire.$on('schedule-report-saved', () => { this.showScheduleModal = false })
        }
    }"
    class="relative flex flex-col gap-5 p-4 lg:p-6"
>

    {{-- Flash --}}
    @if(session('action'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 3500)"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300"
        >
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('action') }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="flex flex-col gap-1">
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Order History</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Browse, filter and export all processed orders.</p>
    </div>

    {{-- Toolbar (search, date filter, buttons, tags) --}}
    <x-orders.toolbar
        :date-filter="$dateFilter"
        :start-date="$startDate"
        :end-date="$endDate"
        :search="$search"
        :has-active-filters="$this->hasActiveFilters"
        :can-schedule="$this->canSchedule"
        :per-page="$perPage"
        :active-filters="[
            'stockFilter'            => $stockFilter,
            'dtCategory'             => $dtCategory,
            'supplierFilter'         => $supplierFilter,
            'flagFilter'             => $flagFilter,
            'categoryFilter'         => $categoryFilter,
            'unitPriceAbove'         => $unitPriceAbove,
            'quantityAbove'          => $quantityAbove,
            'unitPriceQtyAbove'      => $unitPriceQtyAbove,
            'orderedAboveDT'         => $orderedAboveDT,
            'orderedAboveDTClawback' => $orderedAboveDTClawback,
        ]"
    />

    {{-- Table --}}
    <x-orders.table
        :orders="$this->orders"
        :sort-field="$sortField"
        :sort-direction="$sortDirection"
    />

    {{-- Pagination --}}
    @if($this->orders->hasPages())
        <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Showing <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $this->orders->firstItem() }}</span>
                – <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $this->orders->lastItem() }}</span>
                of <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $this->orders->total() }}</span> orders
            </p>
            {{ $this->orders->links() }}
        </div>
    @else
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->orders->total() }} order(s) found.</p>
    @endif

    {{-- Row actions --}}
    <x-orders.row-actions :selected-count="count($selectedOrders)" />

    {{-- Summary cards --}}
    <x-orders.summary-cards
        :orders="$this->orders"
        :total-amount="$this->totalAmount"
        :selected-count="count($selectedOrders)"
        :per-page="$perPage"
    />

    {{-- Advanced filter modal --}}
    <x-orders.advanced-filter-modal />

    {{-- Schedule report modal --}}
    <x-orders.schedule-report-modal
        :schedule-form="$scheduleForm"
        :date-filter="$dateFilter"
        :search="$search"
        :has-active-filters="$this->hasActiveFilters"
    />

</div>
