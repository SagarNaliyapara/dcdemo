<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class OrderHistoryFilterService
{
    public function availableFilterFields(): array
    {
        return [
            'order_number' => ['column' => 'order_number', 'type' => 'string'],
            'description' => ['column' => 'product_description', 'type' => 'string'],
            'pipcode' => ['column' => 'pipcode', 'type' => 'string'],
            'supplier' => ['column' => 'supplier_id', 'type' => 'string'],
            'category' => ['column' => 'category', 'type' => 'string'],
            'stock_status' => ['column' => 'stock_status', 'type' => 'string'],
            'flag' => ['column' => 'flag', 'type' => 'string'],
            'response' => ['column' => 'response', 'type' => 'string'],
            'quantity' => ['column' => 'quantity', 'type' => 'number'],
            'approved_quantity' => ['column' => 'approved_qty', 'type' => 'number'],
            'price' => ['column' => 'price', 'type' => 'number'],
            'dt_price' => ['column' => 'dt_price', 'type' => 'number'],
            'max_price' => ['column' => 'max_price', 'type' => 'number'],
            'subtotal' => ['expression' => 'COALESCE(quantity, 0) * COALESCE(price, 0)', 'type' => 'number'],
            'order_date' => ['column' => 'orderdate', 'type' => 'datetime'],
        ];
    }

    public function applyOrderFilters(Builder $query, array $payload): Builder
    {
        $groups = collect(Arr::get($payload, 'groups', []))
            ->filter(fn (mixed $group): bool => is_array($group) && ! empty(Arr::get($group, 'filters', [])))
            ->values();

        if ($groups->isEmpty()) {
            return $query;
        }

        $topLevelBoolean = Arr::get($payload, 'match_type', 'all') === 'any' ? 'orWhere' : 'where';

        $query->where(function (Builder $groupedQuery) use ($groups, $topLevelBoolean): void {
            foreach ($groups as $index => $group) {
                $method = $index === 0 ? 'where' : $topLevelBoolean;

                $groupedQuery->{$method}(function (Builder $nestedQuery) use ($group): void {
                    $groupLogic = Arr::get($group, 'logic', 'and') === 'or' ? 'orWhere' : 'where';

                    foreach (Arr::get($group, 'filters', []) as $filterIndex => $filter) {
                        $filterMethod = $filterIndex === 0 ? 'where' : $groupLogic;
                        $this->applySingleFilter($nestedQuery, $filter, $filterMethod);
                    }
                });
            }
        });

        return $query;
    }

    private function applySingleFilter(Builder $query, array $filter, string $booleanMethod): void
    {
        $definition = $this->availableFilterFields()[Arr::get($filter, 'field')] ?? null;
        $operator = Arr::get($filter, 'operator');

        if (! is_array($definition) || ! is_string($operator)) {
            return;
        }

        $column = Arr::get($definition, 'column');
        $expression = Arr::get($definition, 'expression');
        $value = Arr::get($filter, 'value');

        if ($expression !== null) {
            $this->applyExpressionFilter($query, $expression, $operator, $value, $booleanMethod, Arr::get($definition, 'type', 'string'));

            return;
        }

        if (! is_string($column)) {
            return;
        }

        $this->applyColumnFilter($query, $column, $operator, $value, $booleanMethod, Arr::get($definition, 'type', 'string'));
    }

    private function applyColumnFilter(
        Builder $query,
        string $column,
        string $operator,
        mixed $value,
        string $booleanMethod,
        string $type,
    ): void {
        match ($operator) {
            'equals' => $this->applyBasicComparison($query, $column, '=', $value, $booleanMethod),
            'not_equals' => $this->applyBasicComparison($query, $column, '!=', $value, $booleanMethod),
            'contains' => $this->applyLikeComparison($query, $column, '%'.$this->stringValue($value).'%', $booleanMethod),
            'starts_with' => $this->applyLikeComparison($query, $column, $this->stringValue($value).'%', $booleanMethod),
            'ends_with' => $this->applyLikeComparison($query, $column, '%'.$this->stringValue($value), $booleanMethod),
            'in' => $this->applyInComparison($query, $column, Arr::wrap($value), $booleanMethod, false),
            'not_in' => $this->applyInComparison($query, $column, Arr::wrap($value), $booleanMethod, true),
            'gt' => $this->applyBasicComparison($query, $column, '>', $this->castValue($value, $type), $booleanMethod),
            'gte' => $this->applyBasicComparison($query, $column, '>=', $this->castValue($value, $type), $booleanMethod),
            'lt' => $this->applyBasicComparison($query, $column, '<', $this->castValue($value, $type), $booleanMethod),
            'lte' => $this->applyBasicComparison($query, $column, '<=', $this->castValue($value, $type), $booleanMethod),
            'between' => $this->applyBetweenComparison($query, $column, $value, $booleanMethod, $type),
            'is_true' => $query->{$booleanMethod}($column, true),
            'is_false' => $query->{$booleanMethod}($column, false),
            default => null,
        };
    }

    private function applyExpressionFilter(
        Builder $query,
        string $expression,
        string $operator,
        mixed $value,
        string $booleanMethod,
        string $type,
    ): void {
        match ($operator) {
            'equals' => $this->applyRawComparison($query, $expression, '=', $this->castValue($value, $type), $booleanMethod),
            'not_equals' => $this->applyRawComparison($query, $expression, '!=', $this->castValue($value, $type), $booleanMethod),
            'gt' => $this->applyRawComparison($query, $expression, '>', $this->castValue($value, $type), $booleanMethod),
            'gte' => $this->applyRawComparison($query, $expression, '>=', $this->castValue($value, $type), $booleanMethod),
            'lt' => $this->applyRawComparison($query, $expression, '<', $this->castValue($value, $type), $booleanMethod),
            'lte' => $this->applyRawComparison($query, $expression, '<=', $this->castValue($value, $type), $booleanMethod),
            'between' => $this->applyRawBetweenComparison($query, $expression, $value, $booleanMethod, $type),
            default => null,
        };
    }

    private function applyBasicComparison(Builder $query, string $column, string $operator, mixed $value, string $booleanMethod): void
    {
        if ($this->isBlank($value)) {
            return;
        }

        $query->{$booleanMethod}($column, $operator, $value);
    }

    private function applyLikeComparison(Builder $query, string $column, string $value, string $booleanMethod): void
    {
        if (trim($value, '%') === '') {
            return;
        }

        $query->{$booleanMethod}($column, 'like', $value);
    }

    private function applyInComparison(Builder $query, string $column, array $values, string $booleanMethod, bool $negated): void
    {
        $normalizedValues = array_values(array_filter(array_map(
            fn (mixed $item): ?string => $this->isBlank($item) ? null : (string) $item,
            $values,
        ), fn (mixed $item): bool => $item !== null));

        if ($normalizedValues === []) {
            return;
        }

        $query->{$booleanMethod}(function (Builder $nestedQuery) use ($column, $normalizedValues, $negated): void {
            if ($negated) {
                $nestedQuery->whereNotIn($column, $normalizedValues);

                return;
            }

            $nestedQuery->whereIn($column, $normalizedValues);
        });
    }

    private function applyBetweenComparison(Builder $query, string $column, mixed $value, string $booleanMethod, string $type): void
    {
        $values = Arr::wrap($value);
        $start = $this->castValue($values[0] ?? null, $type);
        $end = $this->castValue($values[1] ?? null, $type);

        if ($this->isBlank($start) || $this->isBlank($end)) {
            return;
        }

        $query->{$booleanMethod}(function (Builder $nestedQuery) use ($column, $start, $end): void {
            $nestedQuery->whereBetween($column, [$start, $end]);
        });
    }

    private function applyRawComparison(Builder $query, string $expression, string $operator, mixed $value, string $booleanMethod): void
    {
        if ($this->isBlank($value)) {
            return;
        }

        $query->{$booleanMethod}(function (Builder $nestedQuery) use ($expression, $operator, $value): void {
            $nestedQuery->whereRaw("{$expression} {$operator} ?", [$value]);
        });
    }

    private function applyRawBetweenComparison(Builder $query, string $expression, mixed $value, string $booleanMethod, string $type): void
    {
        $values = Arr::wrap($value);
        $start = $this->castValue($values[0] ?? null, $type);
        $end = $this->castValue($values[1] ?? null, $type);

        if ($this->isBlank($start) || $this->isBlank($end)) {
            return;
        }

        $query->{$booleanMethod}(function (Builder $nestedQuery) use ($expression, $start, $end): void {
            $nestedQuery->whereRaw("{$expression} BETWEEN ? AND ?", [$start, $end]);
        });
    }

    private function castValue(mixed $value, string $type): mixed
    {
        if ($this->isBlank($value)) {
            return null;
        }

        return match ($type) {
            'number' => (float) $value,
            'datetime' => Carbon::parse((string) $value),
            default => (string) $value,
        };
    }

    private function stringValue(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function isBlank(mixed $value): bool
    {
        if (is_array($value)) {
            return $value === [];
        }

        return $value === null || $value === '';
    }
}
