<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PhaseFiftyFiveSearchThrottleTest extends TestCase
{
    /** @dataProvider ajaxSearchRoutes */
    public function test_ajax_search_routes_are_rate_limited(string $routeName): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route);
        $this->assertContains('throttle:60,1', $route->middleware());
    }

    public static function ajaxSearchRoutes(): array
    {
        return [
            'autocomplete' => ['listings.autocomplete'],
            'catalog suggestions' => ['listings.search.suggestions'],
            'model attributes' => ['listings.models.attributes'],
            'province cities' => ['locations.provinces.cities'],
        ];
    }
}
