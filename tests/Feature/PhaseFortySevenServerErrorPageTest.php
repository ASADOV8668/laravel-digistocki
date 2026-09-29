<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseFortySevenServerErrorPageTest extends TestCase
{
    public function test_server_error_view_is_branded_and_points_to_support(): void
    {
        $this->view('errors.500')
            ->assertSee('در حال برطرف‌کردن مشکل هستیم')
            ->assertSee('ارتباط با پشتیبانی')
            ->assertSee('noindex, nofollow');
    }
}
