<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PhaseTwoHundredSixteenFlowbiteInteractiveComponentsTest extends TestCase
{
    public function test_profile_delete_confirmation_uses_flowbite_modal_hooks(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-modal-target="confirm-user-deletion"', false)
            ->assertSee('data-modal-toggle="confirm-user-deletion"', false)
            ->assertSee('data-modal-hide="confirm-user-deletion"', false)
            ->assertSee('data-modal-backdrop="static"', false);
    }

    public function test_shared_public_components_use_flowbite_interaction_markers(): void
    {
        $dropdown = file_get_contents(resource_path('views/components/dropdown.blade.php'));
        $modal = file_get_contents(resource_path('views/components/modal.blade.php'));

        $this->assertStringContainsString('data-dropdown-toggle', $dropdown);
        $this->assertStringContainsString('class="z-50 hidden', $dropdown);
        $this->assertStringContainsString('data-modal-backdrop="static"', $modal);
        $this->assertStringContainsString('data-modal-hide', $modal);
    }
}
