<?php

namespace App\Livewire\Pages\Orders;

use App\Livewire\Forms\ScheduledReportForm;
use App\Services\OrderHistoryFilterService;
use App\Services\OrderService;
use App\Services\ScheduledReportService;
use Illuminate\Support\Str;
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

    public string $filterMatchType = 'all';

    public array $filterGroups = [];

    public array $selectedOrders = [];

    public ScheduledReportForm $scheduleForm;

    public function mount(): void
    {
        $this->scheduleForm->email = auth()->user()?->email ?? '';
        $this->ensureDefaultFilterGroup();
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

    public function updatedFilterMatchType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGroups(): void
    {
        $this->normalizeFilterGroups();
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function addFilterGroup(): void
    {
        $this->filterGroups[] = $this->newFilterGroup();
    }

    public function removeFilterGroup(string $groupId): void
    {
        if (count($this->filterGroups) === 1) {
            return;
        }

        $this->filterGroups = array_values(array_filter(
            $this->filterGroups,
            fn (array $group): bool => $group['id'] !== $groupId,
        ));
    }

    public function addFilter(string $groupId): void
    {
        foreach ($this->filterGroups as &$group) {
            if ($group['id'] !== $groupId) {
                continue;
            }

            $group['filters'][] = $this->newFilter();
            break;
        }

        unset($group);
    }

    public function removeFilter(string $groupId, string $filterId): void
    {
        foreach ($this->filterGroups as &$group) {
            if ($group['id'] !== $groupId) {
                continue;
            }

            if (count($group['filters']) === 1) {
                return;
            }

            $group['filters'] = array_values(array_filter(
                $group['filters'],
                fn (array $filter): bool => $filter['id'] !== $filterId,
            ));

            break;
        }

        unset($group);
    }

    public function applyAdvancedFilters(): void
    {
        $this->resetPage();
        $this->dispatch('close-advanced-filter');
    }

    public function resetFilters(): void
    {
        $this->filterMatchType = 'all';
        $this->filterGroups = [$this->newFilterGroup()];
        $this->resetPage();
        $this->dispatch('close-advanced-filter');
    }

    public function selectAll(): void
    {
        $this->selectedOrders = app(OrderService::class)->getSelectedIds($this->filtersArray());
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
        if (! $this->canSchedule) {
            session()->flash('action', 'Apply a search, date filter, or grouped filter before creating a schedule.');

            return;
        }

        $this->scheduleForm->validate();

        app(ScheduledReportService::class)->createScheduledReport(
            userId: auth()->id(),
            filters: $this->filtersArray(),
            form: $this->scheduleForm,
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
        return collect($this->normalizedFilterGroups())
            ->contains(fn (array $group): bool => count($group['filters']) > 0);
    }

    #[Computed]
    public function canSchedule(): bool
    {
        if ($this->dateFilter === 'custom') {
            return false;
        }

        return trim($this->search) !== '' || $this->dateFilter !== 'all' || $this->hasActiveFilters;
    }

    #[Computed]
    public function filterFieldDefinitions(): array
    {
        $definitions = app(OrderHistoryFilterService::class)->availableFilterFields();

        return collect($definitions)
            ->map(function (array $definition, string $key): array {
                $type = $definition['type'];

                return [
                    'key' => $key,
                    'label' => Str::of($key)->replace('_', ' ')->headline()->toString(),
                    'type' => $type,
                    'operators' => match ($type) {
                        'number' => ['equals', 'gt', 'gte', 'lt', 'lte', 'between'],
                        'datetime' => ['gte', 'lte', 'between'],
                        default => ['equals', 'not_equals', 'contains', 'starts_with', 'ends_with', 'in', 'not_in'],
                    },
                ];
            })
            ->values()
            ->all();
    }

    private function filtersArray(): array
    {
        return [
            'search' => $this->search,
            'dateFilter' => $this->dateFilter,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'groupedFilters' => [
                'match_type' => $this->filterMatchType,
                'groups' => $this->normalizedFilterGroups(),
            ],
        ];
    }

    private function normalizedFilterGroups(): array
    {
        return collect($this->filterGroups)
            ->map(function (array $group): array {
                $filters = collect($group['filters'] ?? [])
                    ->map(function (array $filter): ?array {
                        $field = $filter['field'] ?? '';
                        $operator = $filter['operator'] ?? '';
                        $value = trim((string) ($filter['value'] ?? ''));
                        $secondaryValue = trim((string) ($filter['secondary_value'] ?? ''));

                        if ($field === '' || $operator === '') {
                            return null;
                        }

                        if (in_array($operator, ['in', 'not_in'], true)) {
                            $items = array_values(array_filter(array_map(
                                fn (string $item): string => trim($item),
                                explode(',', $value),
                            )));

                            if ($items === []) {
                                return null;
                            }

                            return [
                                'id' => $filter['id'] ?? (string) Str::uuid(),
                                'field' => $field,
                                'operator' => $operator,
                                'value' => $items,
                            ];
                        }

                        if ($operator === 'between') {
                            if ($value === '' || $secondaryValue === '') {
                                return null;
                            }

                            return [
                                'id' => $filter['id'] ?? (string) Str::uuid(),
                                'field' => $field,
                                'operator' => $operator,
                                'value' => [$value, $secondaryValue],
                            ];
                        }

                        if ($value === '') {
                            return null;
                        }

                        return [
                            'id' => $filter['id'] ?? (string) Str::uuid(),
                            'field' => $field,
                            'operator' => $operator,
                            'value' => $value,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => $group['id'] ?? (string) Str::uuid(),
                    'logic' => $group['logic'] ?? 'and',
                    'filters' => $filters,
                ];
            })
            ->filter(fn (array $group): bool => $group['filters'] !== [])
            ->values()
            ->all();
    }

    private function ensureDefaultFilterGroup(): void
    {
        if ($this->filterGroups === []) {
            $this->filterGroups = [$this->newFilterGroup()];
        }
    }

    private function normalizeFilterGroups(): void
    {
        $allowedOperators = collect($this->filterFieldDefinitions)
            ->mapWithKeys(fn (array $definition): array => [$definition['key'] => $definition['operators']])
            ->all();

        foreach ($this->filterGroups as &$group) {
            foreach ($group['filters'] as &$filter) {
                $field = $filter['field'] ?? 'supplier';
                $operators = $allowedOperators[$field] ?? ['equals'];

                if (! in_array($filter['operator'] ?? 'equals', $operators, true)) {
                    $filter['operator'] = $operators[0];
                    $filter['secondary_value'] = '';
                }

                if (($filter['operator'] ?? '') !== 'between') {
                    $filter['secondary_value'] = '';
                }
            }

            unset($filter);
        }

        unset($group);
    }

    private function newFilterGroup(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'logic' => 'and',
            'filters' => [$this->newFilter()],
        ];
    }

    private function newFilter(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'field' => 'supplier',
            'operator' => 'equals',
            'value' => '',
            'secondary_value' => '',
        ];
    }

    public function render()
    {
        return view('livewire.orders.order-history', [
            'filterFieldDefinitions' => $this->filterFieldDefinitions,
        ])->layout('layouts.app', ['title' => 'Order History']);
    }
}
