<?php

namespace App\Jobs;

use App\Models\NotificationRule;
use App\Services\NotificationRuleRunnerService;
use App\Services\NotificationRuleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationRuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    public function __construct(
        public readonly NotificationRule $rule,
    ) {}

    public function handle(NotificationRuleRunnerService $runnerService): void
    {
        $runnerService->run($this->rule->fresh() ?? $this->rule);
    }

    public function failed(\Throwable $throwable): void
    {
        app(NotificationRuleService::class)->markFailed($this->rule->fresh() ?? $this->rule, $throwable);
    }
}
