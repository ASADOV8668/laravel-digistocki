<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BaleBot;
use App\Services\BaleManagementRequests;
use App\Services\SystemOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BaleConnectionController extends Controller
{
    public function show(Request $request, BaleBot $bot, BaleManagementRequests $work)
    {
        abort_unless($bot->enabled(), 404);

        $canEditRoles = $work->isExecutive($request->user());

        return view('admin.bale', [
            'connected' => DB::table('bale_admin_connections')->where('user_id', $request->user()->id)->exists(),
            'canEditRoles' => $canEditRoles,
            'technicalId' => $canEditRoles ? $work->technicalId() : null,
            'eligibleAdmins' => $canEditRoles
                ? User::query()->whereKeyNot($request->user()->id)->where('role', 'admin')->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    public function updateManagementRole(Request $request, BaleBot $bot, BaleManagementRequests $work, SystemOptions $options)
    {
        abort_unless($bot->enabled(), 404);
        abort_unless($work->isExecutive($request->user()), 403);

        $validated = $request->validate([
            'technical_admin_id' => ['nullable', 'integer', Rule::notIn([$request->user()->id]),
                Rule::exists('users', 'id')->where('role', 'admin')->where('is_active', true)],
        ]);
        $options->set(BaleManagementRequests::TECHNICAL_ADMIN_OPTION, (int) ($validated['technical_admin_id'] ?? 0));

        return back()->with('status', 'سمت مدیر فنی برای صندوق مشترک به‌روز شد.');
    }

    public function store(Request $request, BaleBot $bot)
    {
        abort_unless($bot->enabled(), 404);
        $validated = $request->validate(['code' => ['required', 'string', 'regex:/\A[a-f0-9]{32}\z/i']]);
        $hash = hash('sha256', strtolower($validated['code']));

        DB::transaction(function () use ($request, $hash) {
            $user = User::query()->lockForUpdate()->find($request->user()->id);
            abort_unless($user?->isAdmin() && $user->is_active, 403);

            $connections = DB::table('bale_admin_connections');
            if ((clone $connections)->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['code' => 'ابتدا اتصال فعلی را قطع کنید.']);
            }

            $linked = $connections->where('code_hash', $hash)->whereNull('user_id')
                ->where('code_expires_at', '>', now())
                ->update(['user_id' => $user->id, 'code_hash' => null, 'code_expires_at' => null]);

            if ($linked !== 1) {
                throw ValidationException::withMessages(['code' => 'کد معتبر نیست یا منقضی شده است. در ربات /start را بفرستید.']);
            }
        });

        return back()->with('status', 'حساب بله متصل شد. در ربات /start را بفرستید.');
    }

    public function destroy(Request $request)
    {
        DB::transaction(function () use ($request) {
            User::query()->lockForUpdate()->find($request->user()->id);
            DB::table('bale_admin_connections')->where('user_id', $request->user()->id)->delete();
        });

        return back()->with('status', 'اتصال حساب بله قطع شد.');
    }
}
