<div
    x-data="{
        showAdvancedFilter: false,
        showScheduleModal: false,
        init() {
            $wire.$on('close-advanced-filter', () => { this.showAdvancedFilter = false })
            $wire.$on('schedule-report-saved', () => { this.showScheduleModal = false })
        }
    }"
    class="app-page"
>
    @if(session('action'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 3500)"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
        >
            {{ session('action') }}
        </div>
    @endif

    <section class="app-hero">
        <div class="max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Orders Workspace</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Order History</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Search processed orders, build grouped filters, and turn any result set into a scheduled email workflow without leaving this page.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('orders.notification-rules') }}" wire:navigate class="app-button">
                Notification Rules
            </a>
            <a href="{{ route('orders.scheduled-reports') }}" wire:navigate class="app-button app-button-soft">
                Scheduled Reports
            </a>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Filtered Orders</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format($this->orders->total()) }}</p>
            <p class="mt-1 text-sm text-slate-500">Across current search and filters</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Order Value</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">£{{ number_format($this->totalAmount, 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">Total value across the current result set</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Selected Rows</p>
            <p class="mt-3 text-3xl font-semibold text-blue-700">{{ count($selectedOrders) }}</p>
            <p class="mt-1 text-sm text-slate-500">Ready for bulk actions</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Page Size</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $perPage }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $this->hasActiveFilters ? 'Grouped filters active' : 'No advanced filters active' }}</p>
        </div>
    </div>

    <section class="app-card">
        <div class="app-card-body space-y-4">
            <div class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_200px_180px_170px]">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by order number, description, supplier, PIP code, notes..."
                        class="app-input pl-11"
                    />
                </div>

                <select wire:model.live="dateFilter" class="app-select">
                    <option value="all">All time</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="last3days">Last 3 days</option>
                    <option value="last7days">Last 7 days</option>
                    <option value="thismonth">This month</option>
                    <option value="lastmonth">Last month</option>
                    <option value="custom">Custom range</option>
                </select>

                <button
                    type="button"
                    @click="showAdvancedFilter = true"
                    class="app-button justify-between {{ $this->hasActiveFilters ? 'border-blue-200 bg-blue-50 text-blue-700' : '' }}"
                >
                    <span>Grouped Filters</span>
                    <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white">{{ $this->hasActiveFilters ? collect($filterGroups)->sum(fn ($group) => count($group['filters'] ?? [])) : 0 }}</span>
                </button>

                <select wire:model.live="perPage" class="app-select">
                    <option value="10">10 rows</option>
                    <option value="25">25 rows</option>
                    <option value="50">50 rows</option>
                    <option value="100">100 rows</option>
                </select>
            </div>

            @if($dateFilter === 'custom')
                <div class="grid gap-3 md:grid-cols-2">
                    <input type="date" wire:model.live="startDate" class="app-input" />
                    <input type="date" wire:model.live="endDate" class="app-input" />
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[20px] border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Active Scope</span>
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $dateFilter === 'all' ? 'All time' : str($dateFilter)->replace('_', ' ')->headline() }}</span>
                    @if(trim($search) !== '')
                        <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Search: {{ $search }}</span>
                    @endif
                    @if($this->hasActiveFilters)
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Grouped filters applied</span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    @if($this->canSchedule)
                        <button type="button" @click="showScheduleModal = true" class="app-button app-button-soft">
                            Schedule Report
                        </button>
                    @endif
                    <a href="{{ route('orders.notification-rules.create') }}" wire:navigate class="app-button app-button-primary">
                        Create Notification Rule
                    </a>
                </div>
            </div>
        </div>
    </section>

    <x-orders.table
        :orders="$this->orders"
        :sort-field="$sortField"
        :sort-direction="$sortDirection"
    />

    @if($this->orders->hasPages())
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Showing <span class="font-semibold text-slate-900">{{ $this->orders->firstItem() }}</span>
                to <span class="font-semibold text-slate-900">{{ $this->orders->lastItem() }}</span>
                of <span class="font-semibold text-slate-900">{{ $this->orders->total() }}</span>
            </p>
            {{ $this->orders->links() }}
        </div>
    @endif

    <x-orders.row-actions :selected-count="count($selectedOrders)" />

    <x-orders.schedule-report-modal
        :schedule-form="$scheduleForm"
        :date-filter="$dateFilter"
        :search="$search"
        :has-active-filters="$this->hasActiveFilters"
    />

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
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="showAdvancedFilter = false"></div>

        <div class="relative max-h-[90vh] w-full max-w-6xl overflow-y-auto" @click.stop>
            <x-orders.filter-builder
                :groups="$filterGroups"
                :field-definitions="$filterFieldDefinitions"
                match-type-binding="filterMatchType"
                groups-binding="filterGroups"
                add-group-method="addFilterGroup"
                remove-group-method="removeFilterGroup"
                add-filter-method="addFilter"
                remove-filter-method="removeFilter"
                title="Grouped Filters"
                caption="Build rule-style filters directly on order history. Use groups to combine precise logic before you schedule or save a notification."
            />

            <div class="mt-4 flex items-center justify-between rounded-[22px] border border-slate-200 bg-white px-5 py-4 shadow-[0_14px_36px_rgba(17,34,68,0.08)]">
                <button type="button" wire:click="resetFilters" class="app-button app-button-danger">Reset Filters</button>
                <button type="button" wire:click="applyAdvancedFilters" class="app-button app-button-primary">Apply Filters</button>
            </div>
        </div>
    </div>
</div>
