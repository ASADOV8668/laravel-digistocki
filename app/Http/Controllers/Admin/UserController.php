<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->input('role');
        $role = is_string($role) && in_array($role, ['user', 'admin'], true) ? $role : null;

        $userStats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'admins' => User::query()->where('role', 'admin')->count(),
            'can_post' => User::query()->where('can_post_listings', true)->count(),
        ];

        $users = User::query()
            ->withCount('listings')
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function (Builder $search) use ($term) {
                    $search->where('name', 'like', $term)
                        ->orWhere('mobile', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($role !== null, fn (Builder $query) => $query->where('role', $role))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'userStats'));
    }

    public function toggleActive(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 422, 'حساب کاربری خودتان را نمی‌توانید غیرفعال کنید.');
        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('status', $user->is_active ? 'کاربر فعال شد.' : 'کاربر غیرفعال شد.');
    }

    public function toggleRole(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 422, 'نقش حساب خودتان را نمی‌توانید تغییر دهید.');
        abort_if($user->isAdmin() && User::query()->where('role', 'admin')->count() <= 1, 422, 'حداقل یک مدیر باید در سیستم باقی بماند.');

        $user->forceFill(['role' => $user->isAdmin() ? 'user' : 'admin'])->save();

        return back()->with('status', $user->isAdmin() ? 'کاربر به مدیر ارتقا یافت.' : 'نقش مدیر به کاربر عادی تغییر کرد.');
    }

    public function toggleListingPermission(Request $request, User $user)
    {
        $user->forceFill(['can_post_listings' => ! $user->can_post_listings])->save();

        return back()->with('status', $user->can_post_listings ? 'اجازه ثبت آگهی برای کاربر فعال شد.' : 'اجازه ثبت آگهی برای کاربر غیرفعال شد.');
    }
}
