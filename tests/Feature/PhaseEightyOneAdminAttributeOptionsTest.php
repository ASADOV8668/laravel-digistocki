<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseEightyOneAdminAttributeOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_option_based_attributes_require_options_and_remove_duplicate_values(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();

        $this->actingAs($admin)
            ->post(route('admin.attributes.store'), [
                'name' => 'رنگ‌های تست',
                'type' => 'multi_select',
            ])
            ->assertSessionHasErrors('options');

        $this->actingAs($admin)
            ->post(route('admin.attributes.store'), [
                'name' => 'رنگ‌های تست',
                'type' => 'multi_select',
                'options' => "قرمز، آبی, قرمز\nآبی",
            ])
            ->assertRedirect();

        $this->assertSame(['قرمز', 'آبی'], Attribute::where('name', 'رنگ‌های تست')->firstOrFail()->options);
    }
}
