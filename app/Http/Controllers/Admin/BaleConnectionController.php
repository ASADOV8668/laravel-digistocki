<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BaleBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BaleConnectionController extends Controller
{
    public function show(Request $request, BaleBot $bot)
    {
        abort_unless($bot->enabled(), 404);

        return view('admin.bale', [
            'connected' => DB::table('bale_admin_connections')->where('user_id', $request->user()->id)->exists(),
        ]);
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
