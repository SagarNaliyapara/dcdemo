<?php

namespace App\Services;

use App\Livewire\Forms\EditScheduledReportForm;
use App\Livewire\Forms\ScheduledReportForm;
use App\Models\ScheduledReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduledReportService
{
    public function createScheduledReport(int $userId, array $filters, ScheduledReportForm $form): ScheduledReport
    {
        return ScheduledReport::create([
            'user_id' => $userId,
            'name' => $form->name ?: $this->buildAutoName($filters, $form->frequency),
            'filters_json' => $filters,
            'frequency' => $form->frequency,
            'send_time' => $form->sendTime,
            'day_of_week' => $form->frequency === 'weekly' ? (int) $form->dayOfWeek : null,
            'day_of_month' => $form->frequency === 'monthly' ? (int) $form->dayOfMonth : null,
            'email' => $form->email,
            'is_active' => true,
            'next_run_at' => $this->calculateNextRun($form->frequency, $form->sendTime, $form->dayOfWeek, $form->dayOfMonth),
        ]);
    }

    public function updateReport(int $reportId, int $userId, EditScheduledReportForm $form): void
    {
        $report = ScheduledReport::query()
            ->where('id', $reportId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $report->update([
            'name' => $form->name ?: $report->name,
            'frequency' => $form->frequency,
            'send_time' => $form->sendTime,
            'day_of_week' => $form->frequency === 'weekly' ? (int) $form->dayOfWeek : null,
            'day_of_month' => $form->frequency === 'monthly' ? (int) $form->dayOfMonth : null,
            'email' => $form->email,
            'next_run_at' => $this->calculateNextRun($form->frequency, $form->sendTime, $form->dayOfWeek, $form->dayOfMonth),
        ]);
    }

    public function getForUser(int $userId): Collection
    {
        return ScheduledReport::query()
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function delete(int $reportId, int $userId): void
    {
        ScheduledReport::query()
            ->where('id', $reportId)
            ->where('user_id', $userId)
            ->delete();
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

    private function buildAutoName(array $filters, string $frequency): string
    {
        $dateLabel = match ($filters['dateFilter'] ?? 'all') {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last3days' => 'Last 3 Days',
            'last7days' => 'Last 7 Days',
            'thismonth' => 'This Month',
            'lastmonth' => 'Last Month',
            default => 'All Orders',
        };

        return ucfirst($frequency).' – '.$dateLabel.' Order Report';
    }

    private function nextDailyRun(Carbon $now, int $hour, int $minute): Carbon
    {
        $next = $now->copy()->setTime($hour, $minute);

        return $next->isPast() ? $next->addDay() : $next;
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
        $safeDom = min($dayOfMonth, $now->daysInMonth);
        $next = $now->copy()->day($safeDom)->setTime($hour, $minute);

        if ($next->isPast()) {
            $next->addMonth();
            $next->day(min($dayOfMonth, $next->daysInMonth));
        }

        return $next;
    }
}
