<?php

namespace App\Livewire\Forms;

use App\Models\NotificationRule;
use Livewire\Form;

class NotificationRuleForm extends Form
{
    public ?int $ruleId = null;

    public string $name = '';

    public string $channel = 'email';

    public string $dataSource = 'orders';

    public string $status = 'draft';

    public string $dateScopeType = 'last_30_days';

    public string $dateScopeValue = '';

    public string $dateScopeUnit = 'day';

    public string $matchType = 'all';

    public array $filters = [
        'groups' => [],
    ];

    public string $recipientEmail = '';

    public int $emailRowLimit = 300;

    public string $frequency = 'daily';

    public string $sendTime = '08:00';

    public string $dayOfWeek = '1';

    public string $dayOfMonth = '1';

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'channel' => ['required', 'in:email'],
            'dataSource' => ['required', 'in:orders'],
            'status' => ['required', 'in:active,inactive,draft'],
            'dateScopeType' => ['required', 'in:today,yesterday,last_7_days,last_30_days,this_week,this_month,custom_rolling'],
            'dateScopeValue' => ['nullable', 'integer', 'min:1', 'max:365'],
            'dateScopeUnit' => ['nullable', 'in:day,week,month'],
            'matchType' => ['required', 'in:all,any'],
            'filters' => ['required', 'array'],
            'filters.groups' => ['required', 'array'],
            'filters.groups.*.logic' => ['required', 'in:and,or'],
            'filters.groups.*.filters' => ['required', 'array', 'min:1'],
            'filters.groups.*.filters.*.field' => ['required', 'string', 'max:50'],
            'filters.groups.*.filters.*.operator' => ['required', 'string', 'max:50'],
            'recipientEmail' => ['required', 'email', 'max:255'],
            'emailRowLimit' => ['required', 'integer', 'min:1', 'max:1000'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'sendTime' => ['required', 'date_format:H:i'],
            'dayOfWeek' => ['required_if:frequency,weekly', 'nullable', 'integer', 'between:0,6'],
            'dayOfMonth' => ['required_if:frequency,monthly', 'nullable', 'integer', 'between:1,31'],
        ];
    }

    public function messages(): array
    {
        return [
            'filters.groups.required' => 'Add at least one filter group.',
            'filters.groups.*.filters.required' => 'Each group must have at least one filter.',
            'recipientEmail.required' => 'An email address is required.',
            'recipientEmail.email' => 'Please enter a valid email address.',
            'sendTime.date_format' => 'Please select a valid send time.',
            'dayOfWeek.required_if' => 'Please select a weekly day.',
            'dayOfMonth.required_if' => 'Please select a monthly day.',
        ];
    }

    public function toRecordData(): array
    {
        return [
            'name' => $this->name !== '' ? $this->name : null,
            'channel' => $this->channel,
            'data_source' => $this->dataSource,
            'status' => $this->status,
            'date_scope_type' => $this->dateScopeType,
            'date_scope_value' => $this->dateScopeType === 'custom_rolling' ? (int) $this->dateScopeValue : null,
            'date_scope_unit' => $this->dateScopeType === 'custom_rolling' ? $this->dateScopeUnit : null,
            'match_type' => $this->matchType,
            'filters_json' => [
                'groups' => $this->filters['groups'] ?? [],
            ],
            'recipient_email' => $this->recipientEmail,
            'email_row_limit' => $this->emailRowLimit,
            'frequency' => $this->frequency,
            'send_time' => $this->sendTime,
            'day_of_week' => $this->frequency === 'weekly' ? (int) $this->dayOfWeek : null,
            'day_of_month' => $this->frequency === 'monthly' ? (int) $this->dayOfMonth : null,
        ];
    }

    public function populateFrom(NotificationRule $rule): void
    {
        $this->ruleId = $rule->id;
        $this->name = $rule->name ?? '';
        $this->channel = $rule->channel;
        $this->dataSource = $rule->data_source;
        $this->status = $rule->status;
        $this->dateScopeType = $rule->date_scope_type;
        $this->dateScopeValue = $rule->date_scope_value !== null ? (string) $rule->date_scope_value : '';
        $this->dateScopeUnit = $rule->date_scope_unit ?? 'day';
        $this->matchType = $rule->match_type;
        $this->filters = ['groups' => $this->mapGroupsForEditor($rule->filters_json['groups'] ?? [])];
        $this->recipientEmail = $rule->recipient_email;
        $this->emailRowLimit = $rule->email_row_limit;
        $this->frequency = $rule->frequency;
        $this->sendTime = $rule->send_time;
        $this->dayOfWeek = (string) ($rule->day_of_week ?? '1');
        $this->dayOfMonth = (string) ($rule->day_of_month ?? '1');
    }

    private function mapGroupsForEditor(array $groups): array
    {
        return collect($groups)
            ->map(function (array $group): array {
                return [
                    'id' => $group['id'] ?? (string) str()->uuid(),
                    'logic' => $group['logic'] ?? 'and',
                    'filters' => collect($group['filters'] ?? [])
                        ->map(function (array $filter): array {
                            $value = $filter['value'] ?? '';

                            return [
                                'id' => $filter['id'] ?? (string) str()->uuid(),
                                'field' => $filter['field'] ?? 'supplier',
                                'operator' => $filter['operator'] ?? 'equals',
                                'value' => $this->normalizeEditorValue($filter['operator'] ?? 'equals', $value),
                                'secondary_value' => $this->normalizeEditorSecondaryValue($filter['operator'] ?? 'equals', $value),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeEditorValue(string $operator, mixed $value): string
    {
        if (in_array($operator, ['in', 'not_in'], true) && is_array($value)) {
            return implode(', ', $value);
        }

        if ($operator === 'between' && is_array($value)) {
            return (string) ($value[0] ?? '');
        }

        return is_array($value) ? '' : (string) $value;
    }

    private function normalizeEditorSecondaryValue(string $operator, mixed $value): string
    {
        if ($operator === 'between' && is_array($value)) {
            return (string) ($value[1] ?? '');
        }

        return '';
    }
}
