<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminPriorityModules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PriorityModuleController extends Controller
{
    public function edit(Request $request, AdminPriorityModules $modules): View
    {
        abort_unless($modules->isSenior($request->user()), 403);

        return view('admin.priority-modules.edit', ['settings' => $modules->settings()]);
    }

    public function update(Request $request, AdminPriorityModules $modules): RedirectResponse
    {
        abort_unless($modules->isSenior($request->user()), 403);
        $rules = [];
        foreach (array_keys(AdminPriorityModules::LABELS) as $module) {
            $rules["modules.$module.enabled"] = ['required', 'boolean'];
            $rules["modules.$module.role"] = ['nullable', Rule::in(array_keys(AdminPriorityModules::ROLES))];
        }
        $modules->save($request->validate($rules)['modules']);

        return to_route('admin.priority-modules.edit')->with('status', 'تنظیمات اولویت‌ها ذخیره شد.');
    }
}
