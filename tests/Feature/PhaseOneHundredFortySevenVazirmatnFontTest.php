<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredFortySevenVazirmatnFontTest extends TestCase
{
    public function test_variable_vazirmatn_font_is_declared_for_public_and_admin_assets(): void
    {
        $fontPath = resource_path('fonts/Vazirmatn[wght].woff2');
        $this->assertFileExists($fontPath);

        foreach ([resource_path('css/app.css'), resource_path('css/admin.css')] as $stylesheetPath) {
            $stylesheet = file_get_contents($stylesheetPath);

            $this->assertIsString($stylesheet);
            $this->assertStringContainsString("font-family: 'Vazirmatn'", $stylesheet);
            $this->assertStringContainsString("format('woff2')", $stylesheet);
            $this->assertStringContainsString('font-style: normal', $stylesheet);
            $this->assertStringContainsString('font-weight: 100 900', $stylesheet);
            $this->assertStringNotContainsString('supports variations', $stylesheet);
        }

        $adminStylesheet = file_get_contents(resource_path('css/admin.css'));
        $this->assertIsString($adminStylesheet);
        $this->assertStringContainsString('body.font-sans', $adminStylesheet);
        $this->assertStringContainsString('.tailadmin-shell *', $adminStylesheet);
    }
}
