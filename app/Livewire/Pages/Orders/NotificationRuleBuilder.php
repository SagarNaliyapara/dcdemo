<?php

namespace App\Livewire\Pages\Orders;

use App\Livewire\Forms\NotificationRuleForm;
use App\Models\NotificationRule;
use App\Services\NotificationRulePreviewService;
use App\Services\NotificationRuleService;
use App\Services\OrderHistoryFilterService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Notification Rule Builder')]
class NotificationRuleBuilder extends Component
{
    public NotificationRuleForm $form;

    public ?int $ruleId = null;

    public array $preview = [
        'match_count' => 0,
        'preview_count' => 0,
        'rows' => [],
    ];

    public function mount(?NotificationRule $rule = null): void
    {
        if ($rule !== null) {
            abort_unless($rule->user_id === auth()->id(), 404);

            $this->ruleId = $rule->id;
            $this->form->populateFrom($rule);
        } else {
            $this->form->recipientEmail = auth()->user()?->email ?? '';
            $this->form->filters = [
                'groups' => [$this->newFilterGroup()],
            ];
            $this->form->status = 'active';
        }

        $this->refreshPreview();
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'form.')) {
            $this->normalizeFilterGroups();
            $this->refreshPreview();
        }
    }

    public function addFilterGroup(): void
    {
        $this->form->filters['groups'][] = $this->newFilterGroup();
        $this->refreshPreview();
    }

    public function removeFilterGroup(string $groupId): void
    {
        if (count($this->form->filters['groups']) === 1) {
            return;
        }

        $this->form->filters['groups'] = array_values(array_filter(
            $this->form->filters['groups'],
            fn (array $group): bool => $group['id'] !== $groupId,
        ));

        $this->refreshPreview();
    }

    public function addFilter(string $groupId): void
    {
        foreach ($this->form->filters['groups'] as &$group) {
            if ($group['id'] !== $groupId) {
                continue;
            }

            $group['filters'][] = $this->newFilter();
            break;
        }

        unset($group);

        $this->refreshPreview();
    }

    public function removeFilter(string $groupId, string $filterId): void
    {
        foreach ($this->form->filters['groups'] as &$group) {
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

        $this->refreshPreview();
    }

    public function refreshPreview(): void
    {
        $this->preview = app(NotificationRulePreviewService::class)->previewFromData($this->previewData(), 20);
    }

    public function save(): void
    {
        $originalFilters = $this->form->filters;

        $this->form->filters = [
            'groups' => $this->normalizedGroups(),
        ];

        try {
            $this->form->validate();
        } catch (ValidationException $e) {
            $this->form->filters = $originalFilters;
            throw $e;
        }

        if ($this->ruleId !== null) {
            $rule = NotificationRule::query()
                ->where('id', $this->ruleId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            app(NotificationRuleService::class)->update($rule, $this->form);
            session()->flash('action', 'Notification rule updated.');
        } else {
            app(NotificationRuleService::class)->create(auth()->id(), $this->form);
            session()->flash('action', 'Notification rule created.');
        }

        $this->redirectRoute('orders.notification-rules', navigate: true);
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

    private function previewData(): array
    {
        return [
            'match_type' => $this->form->matchType,
            'filters_json' => ['groups' => $this->normalizedGroups()],
            'date_scope_type' => $this->form->dateScopeType,
            'date_scope_value' => $this->form->dateScopeValue,
            'date_scope_unit' => $this->form->dateScopeUnit,
        ];
    }

    private function normalizedGroups(): array
    {
        return collect($this->form->filters['groups'] ?? [])
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

    private function newFilterGroup(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'logic' => 'and',
            'filters' => [$this->newFilter()],
        ];
    }

    private function normalizeFilterGroups(): void
    {
        $allowedOperators = collect($this->filterFieldDefinitions)
            ->mapWithKeys(fn (array $definition): array => [$definition['key'] => $definition['operators']])
            ->all();

        foreach ($this->form->filters['groups'] as &$group) {
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
        return view('livewire.orders.notification-rule-builder', [
            'filterFieldDefinitions' => $this->filterFieldDefinitions,
        ])->layout('layouts.app', ['title' => $this->ruleId !== null ? 'Edit Notification Rule' : 'Create Notification Rule']);
    }
}
