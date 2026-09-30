<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredTwentyFiveReportModerationUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminal_report_shows_final_state_instead_of_edit_controls(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $report = Report::create([
            'user_id' => User::factory()->create()->id,
            'reason' => 'گزارش نهایی رابط کاربری',
            'status' => 'resolved',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('وضعیت نهایی')
            ->assertSee($report->reason);
    }
}
