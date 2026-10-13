<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;

trait InteractsWithAdminPagination
{
    protected function adminPerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 50);

        return in_array($perPage, $this->adminPerPageOptions(), true) ? $perPage : 50;
    }

    protected function adminPerPageOptions(): array
    {
        return [25, 50, 100, 200, 500];
    }
}
