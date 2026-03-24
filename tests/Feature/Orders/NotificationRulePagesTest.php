<?php

namespace Tests\Feature\Orders;

use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRulePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_notification_rules_pages(): void
    {
        $user = User::factory()->create();
        $rule = NotificationRule::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $this->get(route('orders.notification-rules'))->assertOk();
        $this->get(route('orders.notification-rules.create'))->assertOk();
        $this->get(route('orders.notification-rules.edit', $rule))->assertOk();
    }

    public function test_user_cannot_edit_someone_elses_rule(): void
    {
        $user = User::factory()->create();
        $otherUserRule = NotificationRule::factory()->create();

        $this->actingAs($user);

        $this->get(route('orders.notification-rules.edit', $otherUserRule))
            ->assertNotFound();
    }
}
