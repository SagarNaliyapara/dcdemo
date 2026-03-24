<?php

namespace App\Services;

use App\Livewire\Forms\NotificationRuleForm;
use App\Models\NotificationRule;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class NotificationRuleService
{
    public function listForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = NotificationRule::query()
            ->where('user_id', $userId)
            ->latest();

        if (($filters['search'] ?? '') !== '') {
            $search = '%'.trim((string) $filters['search']).'%';

            $query->where(function ($nestedQuery) use ($search): void {
                $nestedQuery->where('name', 'like', $search)
                    ->orWhere('recipient_email', 'like', $search)
                    ->orWhere('status', 'like', $search);
            });
        }

        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (($filters['frequency'] ?? 'all') !== 'all') {
            $query->where('frequency', $filters['frequency']);
        }

        return $query->paginate($perPage);
    }

    public function create(int $userId, NotificationRuleForm $form): NotificationRule
    {
        $attributes = $form->toRecordData();
        $attributes['user_id'] = $userId;
        $attributes['name'] ??= $this->buildAutoName($form);
        $attributes['next_run_at'] = $form->status === 'active'
            ? $this->calculateNextRun($form->frequency, $form->sendTime, $form->dayOfWeek, $form->dayOfMonth)
            : null;

        return NotificationRule::create($attributes);
    }

    public function update(NotificationRule $rule, NotificationRuleForm $form): NotificationRule
    {
        $attributes = $form->toRecordData();
        $attributes['name'] ??= $this->buildAutoName($form);
        $attributes['last_error_message'] = null;
        $attributes['next_run_at'] = $form->status === 'active'
            ? $this->calculateNextRun($form->frequency, $form->sendTime, $form->dayOfWeek, $form->dayOfMonth)
            : null;

        if ($form->status !== 'active') {
            $attributes['last_queued_at'] = null;
        }

        $rule->update($attributes);

        return $rule->refresh();
    }

    public function duplicate(NotificationRule $rule): NotificationRule
    {
        $copy = $rule->replicate([
            'last_queued_at',
            'last_run_at',
            'next_run_at',
            'last_result_count',
            'last_error_message',
        ]);

        $copy->name = trim(($rule->name ?? 'Notification rule').' Copy');
        $copy->status = 'draft';
        $copy->last_queued_at = null;
        $copy->last_run_at = null;
        $copy->next_run_at = null;
        $copy->last_result_count = null;
        $copy->last_error_message = null;
        $copy->save();

        return $copy;
    }

    public function activate(NotificationRule $rule): void
    {
        $rule->update([
            'status' => 'active',
            'next_run_at' => $this->calculateNextRun(
                $rule->frequency,
                $rule->send_time,
                (string) ($rule->day_of_week ?? 1),
                (string) ($rule->day_of_month ?? 1),
            ),
            'last_error_message' => null,
            'last_queued_at' => null,
        ]);
    }

    public function deactivate(NotificationRule $rule): void
    {
        $rule->update([
            'status' => 'inactive',
            'next_run_at' => null,
            'last_queued_at' => null,
        ]);
    }

    public function delete(NotificationRule $rule): void
    {
        $rule->delete();
    }

    public function dueRules(): Collection
    {
        return NotificationRule::query()
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('last_queued_at')
                    ->orWhereColumn('last_queued_at', '<', 'next_run_at');
            })
            ->get();
    }

    public function markQueued(NotificationRule $rule): void
    {
        $rule->update([
            'last_queued_at' => now(),
            'last_error_message' => null,
        ]);
    }

    public function isQueued(NotificationRule $rule): bool
    {
        return $rule->last_queued_at !== null;
    }

    public function markSuccessfulRun(NotificationRule $rule, int $resultCount): void
    {
        $rule->update([
            'status' => 'active',
            'last_queued_at' => null,
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(
                $rule->frequency,
                $rule->send_time,
                (string) ($rule->day_of_week ?? 1),
                (string) ($rule->day_of_month ?? 1),
            ),
            'last_result_count' => $resultCount,
            'last_error_message' => null,
        ]);
    }

    public function markFailed(NotificationRule $rule, Throwable $throwable): void
    {
        $rule->update([
            'status' => 'error',
            'last_queued_at' => null,
            'last_error_message' => $throwable->getMessage(),
        ]);
    }

    public function calculateNextRun(string $frequency, string $sendTime, string $dayOfWeek = '1', string $dayOfMonth = '1'): Carbon
    {
        [$hour, $minute] = explode(':', $sendTime);
        $now = Carbon::now();

        return match ($frequency) {
            'weekly' => $this->nextWeeklyRun($now, (int) $dayOfWeek, (int) $hour, (int) $minute),
            'monthly' => $this->nextMonthlyRun($now, (int) $dayOfMonth, (int) $hour, (int) $minute),
            default => $this->nextDailyRun($now, (int) $hour, (int) $minute),
        };
    }

    private function buildAutoName(NotificationRuleForm $form): string
    {
        $dateLabel = match ($form->dateScopeType) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'custom_rolling' => 'Rolling '.$form->dateScopeValue.' '.ucfirst($form->dateScopeUnit),
            default => 'Last 30 Days',
        };

        return ucfirst($form->frequency).' '.$dateLabel.' Notification';
    }

    private function nextDailyRun(Carbon $now, int $hour, int $minute): Carbon
    {
        $candidate = $now->copy()->setTime($hour, $minute);

        return $candidate->isPast() ? $candidate->addDay() : $candidate;
    }

    private function nextWeeklyRun(Carbon $now, int $dayOfWeek, int $hour, int $minute): Carbon
    {
        if ($now->dayOfWeek === $dayOfWeek) {
            $candidate = $now->copy()->setTime($hour, $minute);

            if (! $candidate->isPast()) {
                return $candidate;
            }
        }

        return $now->copy()->next($dayOfWeek)->setTime($hour, $minute);
    }

    private function nextMonthlyRun(Carbon $now, int $dayOfMonth, int $hour, int $minute): Carbon
    {
        $candidate = $now->copy()->day(min($dayOfMonth, $now->daysInMonth))->setTime($hour, $minute);

        if ($candidate->isPast()) {
            $candidate->addMonth();
            $candidate->day(min($dayOfMonth, $candidate->daysInMonth));
        }

        return $candidate;
    }
}
