<?php

namespace Tests\Feature;

use App\Services\SystemOptions;
use App\Support\PersianDate;
use Carbon\Carbon;
use Tests\TestCase;

class PhaseTwoHundredCardAndPersianDateTest extends TestCase
{
    public function test_persian_date_helper_formats_humanizes_and_parses_jalali_dates(): void
    {
        $date = Carbon::create(2026, 9, 23, 12);

        $this->assertSame('1405/07/01', PersianDate::format($date));
        $this->assertSame('1 هفته قبل', PersianDate::human(Carbon::create(2026, 9, 23, 12)));
        $this->assertSame('2026-09-23', PersianDate::parseDate('۱۴۰۵/۰۷/۰۱')->toDateString());
        $this->assertNull(PersianDate::parseDate('تاریخ نادرست'));
    }

    public function test_listing_card_has_reference_layout_and_configurable_status_badge(): void
    {
        $view = file_get_contents(resource_path('views/components/listing-card.blade.php'));

        $this->assertStringContainsString('favoriteToggle', $view);
        $this->assertStringContainsString('show-notification', file_get_contents(resource_path('js/app.js')));
        $this->assertStringContainsString('listing-placeholder.svg', $view);
        $this->assertStringContainsString('آگهی فعال', $view);
        $this->assertStringContainsString('PersianDate::human', $view);
        $this->assertTrue(app(SystemOptions::class)->showListingStatusBadge());
    }

    public function test_admin_filters_use_persian_datepicker_and_server_side_jalali_conversion(): void
    {
        $listings = file_get_contents(resource_path('views/admin/listings/index.blade.php'));
        $reports = file_get_contents(resource_path('views/admin/reports/index.blade.php'));
        $listingController = file_get_contents(app_path('Http/Controllers/Admin/ListingController.php'));

        $this->assertStringContainsString('data-persian-datepicker', $listings);
        $this->assertStringContainsString('data-persian-datepicker', $reports);
        $this->assertStringContainsString('PersianDate::parseDate', $listingController);
        $this->assertFileExists(resource_path('vendor/persian-datepicker/persianDatepicker.min.js'));
    }
}
