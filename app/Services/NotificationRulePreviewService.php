<?php

namespace App\Services;

use App\Livewire\Forms\NotificationRuleForm;
use App\Models\NotificationRule;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NotificationRulePreviewService
{
    public function __construct(
        private readonly OrderHistoryFilterService $filterService,
    ) {}

    public function preview(NotificationRuleForm $form, int $limit = 20): array
    {
        return $this->previewFromData($form->toRecordData(), $limit);
    }

    public function previewFromRule(NotificationRule $rule, int $limit = 20): array
    {
        return $this->previewFromData($this->ruleData($rule), $limit);
    }

    public function matchedOrders(NotificationRule $rule, ?int $limit = null): Collection
    {
        $query = $this->buildQuery($this->ruleData($rule))
            ->orderByDesc('orderdate');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function previewFromData(array $data, int $limit = 20): array
    {
        $query = $this->buildQuery($data);
        $matchCount = (clone $query)->count();
        $rows = (clone $query)
            ->orderByDesc('orderdate')
            ->limit($limit)
            ->get();

        return [
            'match_count' => $matchCount,
            'preview_count' => $rows->count(),
            'rows' => $rows,
        ];
    }

    private function buildQuery(array $data): Builder
    {
        $query = Order::query()
            ->where('approved_qty', '>', 0);

        $this->applyDateScope($query, $data);
        $this->filterService->applyOrderFilters($query, [
            'match_type' => $data['match_type'] ?? 'all',
            'groups' => $data['filters_json']['groups'] ?? [],
        ]);

        return $query;
    }

    private function applyDateScope(Builder $query, array $data): void
    {
        [$start, $end] = match ($data['date_scope_type'] ?? 'last_30_days') {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'last_7_days' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom_rolling' => $this->customRollingRange($data),
            default => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
        };

        $query->whereBetween('orderdate', [$start, $end]);
    }

    private function customRollingRange(array $data): array
    {
        $value = max(1, (int) ($data['date_scope_value'] ?? 30));
        $unit = $data['date_scope_unit'] ?? 'day';
        $end = now()->endOfDay();
        $start = match ($unit) {
            'month' => now()->subMonths($value)->startOfDay(),
            'week' => now()->subWeeks($value)->startOfDay(),
            default => now()->subDays($value)->startOfDay(),
        };

        return [$start, $end];
    }

    private function ruleData(NotificationRule $rule): array
    {
        return [
            'match_type' => $rule->match_type,
            'filters_json' => $rule->filters_json ?? ['groups' => []],
            'date_scope_type' => $rule->date_scope_type,
            'date_scope_value' => $rule->date_scope_value,
            'date_scope_unit' => $rule->date_scope_unit,
        ];
    }
}
