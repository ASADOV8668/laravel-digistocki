<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredSevenEnvironmentTemplateTest extends TestCase
{
    public function test_environment_template_matches_the_local_subdirectory_configuration(): void
    {
        $template = file_get_contents(base_path('.env.example'));

        $this->assertIsString($template);
        $this->assertStringContainsString('APP_NAME=Digistocki', $template);
        $this->assertStringContainsString('APP_TIMEZONE=Asia/Tehran', $template);
        $this->assertStringContainsString('APP_URL=http://localhost/app', $template);
        $this->assertStringContainsString('ASSET_URL=http://localhost/app', $template);
        $this->assertStringContainsString('APP_LOCALE=fa', $template);
        $this->assertStringContainsString('DB_CONNECTION=mysql', $template);
        $this->assertStringContainsString('DB_DATABASE=app', $template);
        $this->assertStringContainsString('SESSION_DRIVER=file', $template);
    }
}
