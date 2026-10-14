<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseTwoHundredNinetyAdminUserAndStoreCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_user_forms_have_generator_and_disable_autocomplete(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('adminPasswordGenerator', false)
            ->assertSee('ایجاد رمز سخت')
            ->assertSee('autocomplete="off"', false);

        $this->actingAs($this->admin)->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('adminPasswordGenerator', false)
            ->assertSee('کپی رمز')
            ->assertSee('autocomplete="off"', false);
    }

    public function test_admin_user_mobile_is_unique_on_create_and_update_and_password_can_be_changed(): void
    {
        $payload = [
            'name' => 'کاربر مدیریتی',
            'mobile' => '09120001001',
            'email' => 'admin-created@example.test',
            'password' => 'Strong!12345',
            'password_confirmation' => 'Strong!12345',
            'role' => 'user',
            'is_active' => 1,
            'can_post_listings' => 1,
        ];

        $this->actingAs($this->admin)->post(route('admin.users.store'), $payload)->assertRedirect();
        $created = User::where('mobile', '09120001001')->firstOrFail();
        $this->assertTrue(Hash::check('Strong!12345', $created->password));

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            ...$payload,
            'name' => 'کاربر تکراری',
            'email' => 'duplicate@example.test',
        ])->assertSessionHasErrors('mobile');

        $other = User::factory()->create(['mobile' => '09120001002']);
        $this->actingAs($this->admin)->put(route('admin.users.update', $other), [
            'name' => $other->name,
            'mobile' => $created->mobile,
            'role' => 'user',
            'is_active' => 1,
            'can_post_listings' => 1,
        ])->assertSessionHasErrors('mobile');

        $this->actingAs($this->admin)->put(route('admin.users.update', $other), [
            'name' => $other->name,
            'mobile' => '09120001003',
            'password' => 'NewStrong!987',
            'password_confirmation' => 'NewStrong!987',
            'role' => 'user',
            'is_active' => 1,
            'can_post_listings' => 1,
        ])->assertRedirect();
        $this->assertTrue(Hash::check('NewStrong!987', $other->refresh()->password));
    }

    public function test_admin_can_choose_a_user_without_a_store_and_open_the_complete_creation_form(): void
    {
        $available = User::factory()->create(['mobile' => '09120001004']);
        $existing = User::factory()->create(['mobile' => '09120001005']);
        SellerStore::create(['user_id' => $existing->id, 'slug' => 'already-has-store', 'name' => 'فروشگاه قبلی']);

        $this->actingAs($this->admin)->get(route('admin.storefronts.create'))
            ->assertOk()
            ->assertSee($available->mobile)
            ->assertDontSee($existing->mobile)
            ->assertSee('انتخاب صاحب فروشگاه');

        $this->actingAs($this->admin)->get(route('admin.storefronts.create', ['user_id' => $available->id]))
            ->assertRedirect(route('admin.storefronts.edit', $available));

        $this->actingAs($this->admin)->get(route('admin.storefronts.edit', $available))
            ->assertOk()
            ->assertSee('اطلاعات فروشگاه')
            ->assertSee('شناسه اینترنتی انگلیسی', false)
            ->assertSee('مجوز ثبت آگهی');

        $this->actingAs($this->admin)->put(route('admin.storefronts.update', $available), [
            'approval_status' => 'approved',
            'name' => 'فروشگاه دستی',
            'slug' => 'manual-created-store',
            'contact_phone' => $available->mobile,
        ])->assertRedirect(route('admin.storefronts.index'));

        $this->assertDatabaseHas('seller_stores', [
            'user_id' => $available->id,
            'slug' => 'manual-created-store',
            'approval_status' => 'approved',
        ]);
    }
}
