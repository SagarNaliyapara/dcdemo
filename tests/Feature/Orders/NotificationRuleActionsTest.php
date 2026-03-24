<?php

namespace Tests\Feature\Orders;

use App\Livewire\Pages\Orders\NotificationRuleBuilder;
use App\Livewire\Pages\Orders\NotificationRules;
use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationRuleActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_can_create_a_notification_rule(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(NotificationRuleBuilder::class)
            ->set('form.name', 'Supplier alert')
            ->set('form.status', 'active')
            ->set('form.dateScopeType', 'last_30_days')
            ->set('form.matchType', 'all')
            ->set('form.recipientEmail', 'ops@example.com')
            ->set('form.emailRowLimit', 50)
            ->set('form.frequency', 'daily')
            ->set('form.sendTime', '08:00')
            ->set('form.filters.groups.0.filters.0.field', 'supplier')
            ->set('form.filters.groups.0.filters.0.operator', 'equals')
            ->set('form.filters.groups.0.filters.0.value', 'AAH')
            ->call('save')
            ->assertRedirect(route('orders.notification-rules'));

        $this->assertDatabaseHas('notification_rules', [
            'user_id' => $user->id,
            'name' => 'Supplier alert',
            'recipient_email' => 'ops@example.com',
            'status' => 'active',
        ]);
    }

    public function test_builder_can_update_an_existing_notification_rule(): void
    {
        $user = User::factory()->create();
        $rule = NotificationRule::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old name',
            'recipient_email' => 'old@example.com',
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationRuleBuilder::class, ['rule' => $rule])
            ->set('form.name', 'Updated name')
            ->set('form.recipientEmail', 'new@example.com')
            ->set('form.filters.groups.0.filters.0.operator', 'equals')
            ->set('form.filters.groups.0.filters.0.value', 'Alliance')
            ->call('save')
            ->assertRedirect(route('orders.notification-rules'));

        $rule->refresh();

        $this->assertSame('Updated name', $rule->name);
        $this->assertSame('new@example.com', $rule->recipient_email);
        $this->assertSame('Alliance', $rule->filters_json['groups'][0]['filters'][0]['value']);
    }

    public function test_rules_page_can_delete_a_notification_rule(): void
    {
        $user = User::factory()->create();
        $rule = NotificationRule::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationRules::class)
            ->call('confirmDelete', $rule->id)
            ->assertSet('deletingRuleId', $rule->id)
            ->call('deleteRule')
            ->assertSet('deletingRuleId', null);

        $this->assertDatabaseMissing('notification_rules', [
            'id' => $rule->id,
        ]);
    }
}
