<?php

namespace Tests\Feature\Orders;

use App\Livewire\Pages\Orders\OrderHistory;
use App\Models\Order;
use App\Models\ScheduledReport;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderHistoryLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_grouped_filters_apply_all_and_any_logic_correctly(): void
    {
        Order::create([
            'order_number' => 'ORD-1',
            'product_description' => 'Amoxicillin',
            'supplier_id' => 'AAH',
            'category' => 'Generic',
            'approved_qty' => 10,
            'quantity' => 10,
            'price' => 7.0000,
            'orderdate' => now()->subHour(),
        ]);

        Order::create([
            'order_number' => 'ORD-2',
            'product_description' => 'Ibuprofen',
            'supplier_id' => 'Alliance',
            'category' => 'OTC',
            'approved_qty' => 8,
            'quantity' => 8,
            'price' => 3.0000,
            'orderdate' => now()->subHour(),
        ]);

        Order::create([
            'order_number' => 'ORD-3',
            'product_description' => 'Metformin',
            'supplier_id' => 'AAH',
            'category' => 'OTC',
            'approved_qty' => 12,
            'quantity' => 12,
            'price' => 2.0000,
            'orderdate' => now()->subHour(),
        ]);

        $service = app(OrderService::class);

        $matchAll = $service->getAllOrders([
            'groupedFilters' => [
                'match_type' => 'all',
                'groups' => [
                    [
                        'logic' => 'and',
                        'filters' => [
                            ['field' => 'supplier', 'operator' => 'equals', 'value' => 'AAH'],
                        ],
                    ],
                    [
                        'logic' => 'and',
                        'filters' => [
                            ['field' => 'category', 'operator' => 'equals', 'value' => 'OTC'],
                        ],
                    ],
                ],
            ],
        ]);

        $matchAny = $service->getAllOrders([
            'groupedFilters' => [
                'match_type' => 'any',
                'groups' => [
                    [
                        'logic' => 'and',
                        'filters' => [
                            ['field' => 'supplier', 'operator' => 'equals', 'value' => 'Alliance'],
                        ],
                    ],
                    [
                        'logic' => 'and',
                        'filters' => [
                            ['field' => 'price', 'operator' => 'gt', 'value' => '5'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(['ORD-3'], $matchAll->pluck('order_number')->all());
        $this->assertSame(['ORD-1', 'ORD-2'], $matchAny->pluck('order_number')->sort()->values()->all());
    }

    public function test_order_history_does_not_create_schedule_without_scope(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(OrderHistory::class)
            ->set('scheduleForm.email', 'ops@example.com')
            ->call('saveScheduledReport');

        $this->assertDatabaseCount('scheduled_reports', 0);
    }

    public function test_order_history_can_create_scheduled_report_from_grouped_filters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(OrderHistory::class)
            ->set('filterGroups.0.filters.0.field', 'supplier')
            ->set('filterGroups.0.filters.0.operator', 'equals')
            ->set('filterGroups.0.filters.0.value', 'AAH')
            ->set('scheduleForm.email', 'ops@example.com')
            ->set('scheduleForm.frequency', 'daily')
            ->set('scheduleForm.sendTime', '08:00')
            ->call('saveScheduledReport')
            ->assertDispatched('schedule-report-saved');

        $report = ScheduledReport::query()->first();

        $this->assertNotNull($report);
        $this->assertSame('AAH', $report->filters_json['groupedFilters']['groups'][0]['filters'][0]['value']);
    }
}
