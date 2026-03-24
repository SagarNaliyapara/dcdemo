<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class OrderService
{
    public function __construct(
        private readonly OrderHistoryFilterService $orderHistoryFilterService,
    ) {}

    public function updateOrderFlag(int $orderId, string $flag): void
    {
        Order::where('id', $orderId)->update(['flag' => $flag]);
    }

    public function updateOrderNote(int $orderId, string $note): void
    {
        Order::where('id', $orderId)->update(['notes' => $note]);
    }

    public function getPaginatedOrders(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage);
    }

    public function getAllOrders(array $filters): Collection
    {
        return $this->buildQuery($filters)->get();
    }

    public function getTotalAmount(array $filters): float
    {
        return (float) ($this->buildQuery($filters)
            ->selectRaw('COALESCE(SUM(quantity * price), 0) as total')
            ->value('total') ?? 0);
    }

    public function getSelectedIds(array $filters): array
    {
        return $this->buildQuery($filters)
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->toArray();
    }

    private function buildQuery(array $filters): Builder
    {
        $query = Order::query();

        $this->applySearch($query, $filters['search'] ?? '');
        $this->applyDateRange($query, $filters['dateFilter'] ?? 'all', $filters['startDate'] ?? '', $filters['endDate'] ?? '');
        $this->applyMinOrderDate($query, $filters['minOrderDate'] ?? null);
        $this->applyLegacyFilters($query, $filters);
        $this->applyGroupedFilters($query, $filters['groupedFilters'] ?? []);

        $sortField = $filters['sortField'] ?? 'orderdate';
        $sortDirection = $filters['sortDirection'] ?? 'desc';

        return $query->orderBy($sortField, $sortDirection);
    }

    private function applyLegacyFilters(Builder $query, array $filters): void
    {
        $this->applyStockFilter($query, $filters['stockFilter'] ?? []);
        $this->applyDtCategory($query, $filters['dtCategory'] ?? []);
        $this->applySupplierFilter($query, $filters['supplierFilter'] ?? []);
        $this->applyFlagFilter($query, $filters['flagFilter'] ?? []);
        $this->applyCategoryFilter($query, $filters['categoryFilter'] ?? []);
        $this->applyPriceFilters($query, $filters);
        $this->applyAdditionalFilters($query, $filters);
    }

    private function applyGroupedFilters(Builder $query, array $groupedFilters): void
    {
        if (($groupedFilters['groups'] ?? []) === []) {
            return;
        }

        $this->orderHistoryFilterService->applyOrderFilters($query, $groupedFilters);
    }

    private function applyMinOrderDate($query, ?string $minDate): void
    {
        if ($minDate !== null && $minDate !== '') {
            $query->where('orderdate', '>=', $minDate);
        }
    }

    private function applySearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function ($q) use ($like): void {
            $q->where('order_number', 'like', $like)
                ->orWhere('product_description', 'like', $like)
                ->orWhere('pipcode', 'like', $like)
                ->orWhere('supplier_id', 'like', $like)
                ->orWhere('response', 'like', $like)
                ->orWhere('notes', 'like', $like);
        });
    }

    private function applyDateRange($query, string $dateFilter, string $startDate, string $endDate): void
    {
        [$start, $end] = $this->resolveDateRange($dateFilter, $startDate, $endDate);

        if ($start && $end) {
            $query->whereBetween('orderdate', [$start, $end]);
        }
    }

    private function resolveDateRange(string $dateFilter, string $startDate, string $endDate): array
    {
        return match ($dateFilter) {
            'today' => [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()],
            'yesterday' => [Carbon::now()->subDay()->startOfDay(), Carbon::now()->subDay()->endOfDay()],
            'last3days' => [Carbon::now()->subDays(3)->startOfDay(), Carbon::now()->endOfDay()],
            'last7days' => [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()->endOfDay()],
            'thismonth' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'lastmonth' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'custom' => $startDate && $endDate
                ? [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]
                : [null, null],
            default => [null, null],
        };
    }

    private function applyStockFilter($query, array $stockFilter): void
    {
        if (! empty($stockFilter)) {
            $query->whereIn('stock_status', $stockFilter);
        }
    }

    private function applyDtCategory($query, array $dtCategory): void
    {
        if (! empty($dtCategory)) {
            $query->whereIn('price_range', $dtCategory);
        }
    }

    private function applySupplierFilter($query, array $supplierFilter): void
    {
        if (! empty($supplierFilter)) {
            $query->whereIn('supplier_id', $supplierFilter);
        }
    }

    private function applyFlagFilter($query, array $flagFilter): void
    {
        if (! empty($flagFilter)) {
            $query->whereIn('flag', $flagFilter);
        }
    }

    private function applyCategoryFilter($query, array $categoryFilter): void
    {
        if (! empty($categoryFilter)) {
            $query->whereIn('category', $categoryFilter);
        }
    }

    private function applyPriceFilters($query, array $filters): void
    {
        if (($filters['unitPriceAbove'] ?? '') !== '') {
            $query->where('price', '>', (float) $filters['unitPriceAbove']);
        }

        if (($filters['quantityAbove'] ?? '') !== '') {
            $query->where('quantity', '>', (float) $filters['quantityAbove']);
        }

        if (($filters['unitPriceQtyAbove'] ?? '') !== '') {
            $query->whereRaw('(quantity * price) > ?', [(float) $filters['unitPriceQtyAbove']]);
        }
    }

    private function applyAdditionalFilters($query, array $filters): void
    {
        if (! empty($filters['orderedAboveDT'])) {
            $query->whereNotNull('dt_price')->whereColumn('price', '>', 'dt_price');
        }

        if (! empty($filters['orderedAboveDTClawback'])) {
            $query->whereNotNull('max_price')->whereColumn('price', '>', 'max_price');
        }
    }
}
