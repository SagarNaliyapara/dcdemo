<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationRuleJob;
use App\Services\NotificationRuleService;
use Illuminate\Console\Command;

class RunNotificationRules extends Command
{
    protected $signature = 'notifications:run-rules';

    protected $description = 'Dispatch queued jobs for due email notification rules';

    public function handle(NotificationRuleService $notificationRuleService): int
    {
        $rules = $notificationRuleService->dueRules();

        if ($rules->isEmpty()) {
            $this->info('No notification rules are due.');

            return self::SUCCESS;
        }

        foreach ($rules as $rule) {
            $notificationRuleService->markQueued($rule);
            SendNotificationRuleJob::dispatch($rule->fresh());

            $this->info('Queued notification rule #'.$rule->id.' for '.$rule->recipient_email);
        }

        $this->info('Done. Queued '.$rules->count().' notification rule job(s).');

        return self::SUCCESS;
    }
}
