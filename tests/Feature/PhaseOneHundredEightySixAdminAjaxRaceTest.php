<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredEightySixAdminAjaxRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_listing_form_cancels_stale_model_city_and_user_requests(): void
    {
        $view = file_get_contents(resource_path('views/admin/listings/form.blade.php'));

        $this->assertNotFalse($view);
        $this->assertStringContainsString('attributesController: null', $view);
        $this->assertStringContainsString('citiesController: null', $view);
        $this->assertStringContainsString('usersController: null', $view);
        $this->assertSame(3, substr_count($view, 'signal: controller.signal'));
        $this->assertStringContainsString("if (error.name !== 'AbortError') this.userResults = [];", $view);
        $this->assertStringContainsString('loadCities(false)', $view);
    }
}
