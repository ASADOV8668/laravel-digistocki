<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseSixtyFiveNotificationBadgeQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_layout_loads_unread_count_once_for_both_badges(): void
    {
        $user = User::factory()->create();

        DB::enableQueryLog();
        $this->actingAs($user)->get(route('home'))->assertOk();

        $notificationQueries = collect(DB::getQueryLog())->filter(
            fn (array $query): bool => str_contains($query['query'], 'notifications')
        );

        $this->assertCount(1, $notificationQueries);
        DB::disableQueryLog();
    }
}
