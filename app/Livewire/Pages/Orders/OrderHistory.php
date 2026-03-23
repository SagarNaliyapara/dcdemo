<?php

namespace App\Livewire\Pages\Orders;

use App\Livewire\Forms\ScheduledReportForm;
use App\Services\OrderService;
use App\Services\ScheduledReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Order History')]
class OrderHistory extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $dateFilter = 'all';

    public string $startDate = '';

    public string $endDate = '';

    #[Url]
    public string $sortField = 'orderdate';

    #[Url]
    public string $sortDirection = 'desc';

    public int $perPage = 25;

    // Advanced filters
    public array $stockFilter = [];

    public array $dtCategory = [];

    public array $supplierFilter = [];

    public array $flagFilter = [];

    public array $categoryFilter = [];

    public string $unitPriceAbove = '';

    public string $quantityAbove = '';

    public string $unitPriceQtyAbove = '';

    public bool $orderedAboveDT = false;

    public bool $orderedAboveDTClawback = false;

    public array $selectedOrders = [];

    public ScheduledReportForm $scheduleForm;

    public function mount(): void
    {
        $this->scheduleForm->email = auth()->user()?->email ?? '';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function applyAdvancedFilters(): void
    {
        $this->resetPage();
        $this->dispatch('close-advanced-filter');
    }

    public function resetFilters(): void
    {
        $this->stockFilter           = [];
        $this->dtCategory            = [];
        $this->supplierFilter        = [];
        $this->flagFilter            = [];
        $this->categoryFilter        = [];
        $this->unitPriceAbove        = '';
        $this->quantityAbove         = '';
        $this->unitPriceQtyAbove     = '';
        $this->orderedAboveDT        = false;
        $this->orderedAboveDTClawback = false;
        $this->resetPage();
        $this->dispatch('close-advanced-filter');
    }

    public function selectAll(): void
    {
        $service = app(OrderService::class);

        $this->selectedOrders = $service->getSelectedIds($this->filtersArray());
    }

    public function deselectAll(): void
    {
        $this->selectedOrders = [];
    }

    public function updateFlag(int $orderId, string $flag): void
    {
        app(OrderService::class)->updateOrderFlag($orderId, $flag);
    }

    public function updateNote(int $orderId, string $note): void
    {
        app(OrderService::class)->updateOrderNote($orderId, $note);
    }

    public function reOrder(): void
    {
        session()->flash('action', 'Re-order queued for '.count($this->selectedOrders).' order(s).');
        $this->selectedOrders = [];
    }

    public function addToExcessStock(): void
    {
        session()->flash('action', 'Added '.count($this->selectedOrders).' order(s) to Excess Stock.');
        $this->selectedOrders = [];
    }

    public function addToBulkOrder(): void
    {
        session()->flash('action', 'Added '.count($this->selectedOrders).' order(s) to Bulk Order.');
        $this->selectedOrders = [];
    }

    public function addToAvailabilityTracker(): void
    {
        session()->flash('action', 'Added '.count($this->selectedOrders).' order(s) to Availability Tracker.');
        $this->selectedOrders = [];
    }

    public function addToReturnsManagement(): void
    {
        session()->flash('action', 'Added '.count($this->selectedOrders).' order(s) to Returns Management.');
        $this->selectedOrders = [];
    }

    public function saveScheduledReport(): void
    {
        $this->scheduleForm->validate();

        app(ScheduledReportService::class)->createScheduledReport(
            userId:  auth()->id(),
            filters: $this->filtersArray(),
            form:    $this->scheduleForm,
        );

        $this->dispatch('schedule-report-saved');
        session()->flash('action', 'Scheduled report "'.(($this->scheduleForm->name) ?: 'report').'" created successfully.');

        $this->scheduleForm->reset(['name']);
    }

    #[Computed]
    public function orders()
    {
        return app(OrderService::class)->getPaginatedOrders($this->filtersArray(), $this->perPage);
    }

    #[Computed]
    public function totalAmount(): float
    {
        return app(OrderService::class)->getTotalAmount($this->filtersArray());
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return ! empty($this->stockFilter)
            || ! empty($this->dtCategory)
            || ! empty($this->supplierFilter)
            || ! empty($this->flagFilter)
            || ! empty($this->categoryFilter)
            || $this->unitPriceAbove !== ''
            || $this->quantityAbove !== ''
            || $this->unitPriceQtyAbove !== ''
            || $this->orderedAboveDT
            || $this->orderedAboveDTClawback;
    }

    #[Computed]
    public function canSchedule(): bool
    {
        if ($this->dateFilter === 'custom') {
            return false;
        }

        $hasSearch     = trim($this->search) !== '';
        $hasDateFilter = $this->dateFilter !== 'all';

        return $hasSearch || $hasDateFilter || $this->hasActiveFilters;
    }

    private function filtersArray(): array
    {
        return [
            'search'                 => $this->search,
            'dateFilter'             => $this->dateFilter,
            'startDate'              => $this->startDate,
            'endDate'                => $this->endDate,
            'sortField'              => $this->sortField,
            'sortDirection'          => $this->sortDirection,
            'stockFilter'            => $this->stockFilter,
            'dtCategory'             => $this->dtCategory,
            'supplierFilter'         => $this->supplierFilter,
            'flagFilter'             => $this->flagFilter,
            'categoryFilter'         => $this->categoryFilter,
            'unitPriceAbove'         => $this->unitPriceAbove,
            'quantityAbove'          => $this->quantityAbove,
            'unitPriceQtyAbove'      => $this->unitPriceQtyAbove,
            'orderedAboveDT'         => $this->orderedAboveDT,
            'orderedAboveDTClawback' => $this->orderedAboveDTClawback,
        ];
    }

    public function render()
    {
        return view('livewire.orders.order-history')
            ->layout('layouts.app', ['title' => 'Order History']);
    }
}
