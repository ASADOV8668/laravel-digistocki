<?php

namespace Tests\Feature;

use App\Models\SystemOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwentyNineSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_page_renders_configured_contact_actions(): void
    {
        SystemOption::insert([
            ['key' => 'support_phone', 'value' => '02112345678', 'type' => 'string'],
            ['key' => 'support_email', 'value' => 'support@example.com', 'type' => 'string'],
            ['key' => 'support_office_address', 'value' => 'تهران، خیابان نمونه', 'type' => 'string'],
        ]);

        $this->get(route('support.index'))
            ->assertOk()
            ->assertSee('tel:02112345678')
            ->assertSee('mailto:support@example.com')
            ->assertSee('تهران، خیابان نمونه');
    }

    public function test_support_page_shows_empty_state_when_contacts_are_not_configured(): void
    {
        $this->get(route('support.index'))
            ->assertOk()
            ->assertSee('اطلاعات تماس پشتیبانی هنوز ثبت نشده است.');
    }
}
