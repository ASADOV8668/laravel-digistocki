<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredThirtyFiveListingShareButtonTest extends TestCase
{
    public function test_listing_page_has_navigator_share_button(): void
    {
        $view = file_get_contents(resource_path('views/listings/show.blade.php'));

        $this->assertStringContainsString('x-data="shareLink(@js(url()->current()))"', $view);
        $this->assertStringContainsString('@click="share()"', $view);
        $this->assertStringContainsString('<x-heroicon-o-share', $view);
        $this->assertStringContainsString("'لینک کپی شد'", $view);
    }
}
