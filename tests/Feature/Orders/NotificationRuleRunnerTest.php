<?php

namespace Tests\Feature\Orders;

use App\Jobs\SendNotificationRuleJob;
use App\Mail\NotificationRuleMail;
use App\Models\NotificationRule;
use App\Models\Order;
use App\Services\NotificationRuleRunnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationRuleRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_runner_sends_email_and_updates_run_metadata(): void
    {
        Mail::fake();

        $rule = NotificationRule::factory()->active()->create([
            'filters_json' => [
                'groups' => [
                    [
                        'id' => 'group_1',
                        'logic' => 'and',
                        'filters' => [
                            [
                                'id' => 'filter_1',
                                'field' => 'supplier',
                                'operator' => 'equals',
                                'value' => 'AAH',
                            ],
                        ],
                    ],
                ],
            ],
            'email_row_limit' => 5,
        ]);

        Order::create([
            'order_number' => 'ORD-100001',
            'product_description' => 'Amoxicillin',
            'supplier_id' => 'AAH',
            'approved_qty' => 12,
            'quantity' => 12,
            'price' => 5.5000,
            'dt_price' => 5.1000,
            'orderdate' => now()->subHour(),
        ]);

        $resultCount = app(NotificationRuleRunnerService::class)->run($rule);

        $rule->refresh();

        $this->assertSame(1, $resultCount);
        $this->assertSame(1, $rule->last_result_count);
        $this->assertNotNull($rule->last_run_at);
        $this->assertNotNull($rule->next_run_at);
        $this->assertNull($rule->last_error_message);

        Mail::assertSent(NotificationRuleMail::class, function (NotificationRuleMail $mail) use ($rule): bool {
            return $mail->rule->is($rule)
                && $mail->orders->count() === 1
                && $mail->totalMatches === 1;
        });
    }

    public function test_runner_skips_email_when_no_rows_match(): void
    {
        Mail::fake();

        $rule = NotificationRule::factory()->active()->create([
            'filters_json' => [
                'groups' => [
                    [
                        'id' => 'group_1',
                        'logic' => 'and',
                        'filters' => [
                            [
                                'id' => 'filter_1',
                                'field' => 'supplier',
                                'operator' => 'equals',
                                'value' => 'Alliance',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Order::create([
            'order_number' => 'ORD-100002',
            'product_description' => 'Metformin',
            'supplier_id' => 'AAH',
            'approved_qty' => 10,
            'quantity' => 10,
            'price' => 2.0000,
            'dt_price' => 1.9000,
            'orderdate' => now()->subHour(),
        ]);

        $resultCount = app(NotificationRuleRunnerService::class)->run($rule);

        $rule->refresh();

        $this->assertSame(0, $resultCount);
        $this->assertSame(0, $rule->last_result_count);
        $this->assertNotNull($rule->last_run_at);

        Mail::assertNothingSent();
    }

    public function test_command_queues_only_due_active_rules(): void
    {
        Queue::fake();

        $dueRule = NotificationRule::factory()->active()->create([
            'next_run_at' => now()->subMinute(),
        ]);

        NotificationRule::factory()->inactive()->create();

        NotificationRule::factory()->active()->create([
            'next_run_at' => now()->addHour(),
        ]);

        $this->artisan('notifications:run-rules')
            ->assertSuccessful();

        $dueRule->refresh();

        $this->assertNotNull($dueRule->last_queued_at);

        Queue::assertPushed(SendNotificationRuleJob::class, function (SendNotificationRuleJob $job) use ($dueRule): bool {
            return $job->rule->is($dueRule);
        });

        Queue::assertPushed(SendNotificationRuleJob::class, 1);
    }
}
