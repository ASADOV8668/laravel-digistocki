<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $this->admin->save();
    }

    public function test_all_admin_list_pages_use_fifty_items_by_default(): void
    {
        $pages = [
            ['admin.listings.index', 'listings'],
            ['admin.users.index', 'users'],
            ['admin.storefronts.index', 'stores'],
            ['admin.reports.index', 'reports'],
            ['admin.brands.index', 'brands'],
            ['admin.phone-models.index', 'models'],
            ['admin.attributes.index', 'attributes'],
            ['admin.notifications.index', 'notifications'],
        ];

        foreach ($pages as [$route, $viewKey]) {
            $response = $this->actingAs($this->admin)->get(route($route));

            $response->assertOk()->assertViewHas($viewKey, fn ($paginator) => $paginator->perPage() === 50);
            $response->assertSee('نمایش در صفحه')->assertSee('value="50"', false);
        }
    }

    public function test_admin_can_change_page_size_without_losing_filters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', [
            'q' => 'فرشید',
            'role' => 'user',
            'per_page' => 100,
        ]));

        $response->assertOk()
            ->assertViewHas('users', fn ($users) => $users->perPage() === 100)
            ->assertSee('name="q" value="فرشید"', false)
            ->assertSee('name="role" value="user"', false)
            ->assertSee('value="100"', false);
    }
}
