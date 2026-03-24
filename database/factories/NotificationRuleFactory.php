<?php

namespace Database\Factories;

use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationRule>
 */
class NotificationRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'High value supplier orders',
            'channel' => 'email',
            'data_source' => 'orders',
            'status' => 'active',
            'date_scope_type' => 'last_30_days',
            'date_scope_value' => null,
            'date_scope_unit' => null,
            'match_type' => 'all',
            'filters_json' => [
                'groups' => [
                    [
                        'id' => 'group_1',
                        'logic' => 'and',
                        'filters' => [
                            [
                                'id' => 'filter_1',
                                'field' => 'supplier',
                                'operator' => 'in',
                                'value_type' => 'list',
                                'value' => ['AAH'],
                            ],
                        ],
                    ],
                ],
            ],
            'recipient_email' => fake()->safeEmail(),
            'email_row_limit' => 300,
            'frequency' => 'daily',
            'send_time' => '08:00',
            'day_of_week' => null,
            'day_of_month' => null,
            'last_queued_at' => null,
            'last_run_at' => null,
            'next_run_at' => now()->subMinute(),
            'last_result_count' => null,
            'last_error_message' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'next_run_at' => now()->subMinute(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => 'inactive',
            'next_run_at' => null,
        ]);
    }
}
