<?php

namespace Tests\Feature\Orders;

use App\Jobs\SendNotificationRuleJob;
use App\Livewire\Pages\Orders\NotificationRuleBuilder;
use App\Livewire\Pages\Orders\NotificationRules;
use App\Models\NotificationRule;
use App\Models\Order;
use App\Models\User;
use App\Services\NotificationRulePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationRuleEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_rejects_invalid_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(NotificationRuleBuilder::class)
            ->set('form.recipientEmail', 'not-an-email')
            ->set('form.emailRowLimit', 0)
            ->set('form.filters.groups', [])
            ->call('save')
            ->assertHasErrors([
                'form.recipientEmail',
                'form.emailRowLimit',
                'form.filters.groups',
            ]);
    }

    public function test_preview_is_capped_to_twenty_rows_for_large_result_sets(): void
    {
        $user = User::factory()->create();
        $rule = NotificationRule::factory()->create([
            'user_id' => $user->id,
            'filters_json' => [
                'groups' => [
                    [
                        'logic' => 'and',
                        'filters' => [
                            ['field' => 'supplier', 'operator' => 'equals', 'value' => 'AAH'],
                        ],
                    ],
                ],
            ],
        ]);

        foreach (range(1, 25) as $index) {
            Order::create([
                'order_number' => 'ORD-'.$index,
                'product_description' => 'Product '.$index,
                'supplier_id' => 'AAH',
                'approved_qty' => 5,
                'quantity' => 5,
                'price' => 1.5000,
                'orderdate' => now()->subMinutes($index),
            ]);
        }

        $preview = app(NotificationRulePreviewService::class)->previewFromRule($rule);

        $this->assertSame(25, $preview['match_count']);
        $this->assertSame(20, $preview['preview_count']);
        $this->assertCount(20, $preview['rows']);
    }

    public function test_run_now_ignores_duplicate_rapid_clicks_when_rule_is_already_queued(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $rule = NotificationRule::factory()->create([
            'user_id' => $user->id,
            'last_queued_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationRules::class)
            ->call('runNow', $rule->id)
            ->call('runNow', $rule->id);

        Queue::assertPushed(SendNotificationRuleJob::class, 1);
    }

    public function test_duplicate_action_creates_distinct_draft_copies_each_time(): void
    {
        $user = User::factory()->create();
        $rule = NotificationRule::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationRules::class)
            ->call('duplicateRule', $rule->id)
            ->call('duplicateRule', $rule->id);

        $copies = NotificationRule::query()
            ->where('user_id', $user->id)
            ->where('status', 'draft')
            ->where('id', '!=', $rule->id)
            ->get();

        $this->assertCount(2, $copies);
        $this->assertTrue($copies->every(fn (NotificationRule $copy): bool => str($copy->name)->contains('Copy')));
    }
}
