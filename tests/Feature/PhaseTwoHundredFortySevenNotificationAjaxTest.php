<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseTwoHundredFortySevenNotificationAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_one_notification_as_read_with_json(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotification($user, false);

        $this->actingAs($user)
            ->patchJson(route('notifications.read', $notification->id))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read_with_json(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user, false);
        $this->createNotification($user, false);

        $this->actingAs($user)
            ->patchJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    private function createNotification(User $user, bool $read): DatabaseNotification
    {
        $notification = new DatabaseNotification([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\ListingStatusNotification',
            'data' => ['title' => 'اعلان تستی', 'message' => 'پیام تستی'],
            'read_at' => $read ? now() : null,
        ]);
        $notification->notifiable()->associate($user);
        $notification->save();

        return $notification;
    }
}
