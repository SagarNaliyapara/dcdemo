<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_scheduled_reports_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('orders.scheduled-reports'))
            ->assertOk();
    }
}
