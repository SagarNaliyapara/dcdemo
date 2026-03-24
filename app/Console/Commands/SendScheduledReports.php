<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledReportJob;
use App\Models\ScheduledReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled';

    protected $description = 'Dispatch queued jobs for all due scheduled order history reports';

    public function handle(): int
    {
        $now = Carbon::now();
        $reports = ScheduledReport::query()
            ->where('is_active', true)
            ->where('next_run_at', '<=', $now)
            ->get();

        if ($reports->isEmpty()) {
            $this->info('No scheduled reports are due.');

            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($reports as $report) {
            // Skip if already sent today (prevent duplicate runs)
            if ($report->last_run_at && $report->last_run_at->isToday()) {
                $this->line("  Skip  #{$report->id} — already dispatched today.");

                continue;
            }

            SendScheduledReportJob::dispatch($report);

            $this->info('  Queued  #'.$report->id.' "'.$report->name.'" → '.$report->email);
            $dispatched++;
        }

        $this->info("Done. Dispatched {$dispatched} report job(s) to queue.");

        return self::SUCCESS;
    }
}
