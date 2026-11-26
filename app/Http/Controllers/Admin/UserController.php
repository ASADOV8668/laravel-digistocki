<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ListingImportParser;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use Concerns\InteractsWithAdminPagination;

    public function edit(User $user)
    {
        abort_if($user->isSeniorAdmin(), 403);
        $importParsers = ListingImportParser::query()->where('is_active', true)->orderBy('title')->get();

        return view('admin.users.edit', compact('user', 'importParsers'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        abort_if($user->isSeniorAdmin(), 403);
        abort_if($request->user()->is($user) && $request->input('role') !== 'admin', 422, 'نقش حساب خودتان را نمی‌توانید حذف کنید.');
        abort_if($request->user()->is($user) && ! $request->boolean('is_active'), 422, 'حساب خودتان را نمی‌توانید غیرفعال کنید.');
        abort_if($user->isAdmin() && $request->input('role') !== 'admin' && User::query()->where('role', 'admin')->count() <= 1, 422, 'حداقل یک مدیر باید در سیستم باقی بماند.');

        $validated = $request->validated();
        abort_unless($validated['role'] === $user->administrationRole() || $request->user()->isSeniorAdmin(), 403);
        $data = collect($validated)->except(['password', 'role', 'is_active', 'can_post_listings'])->all();
        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
            $data['password_set_at'] = now();
        }
        $user->update($data);
        $user->forceFill([
            ...User::attributesForAdministrationRole($validated['role']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'can_post_listings' => (bool) ($validated['can_post_listings'] ?? false),
            'listing_import_parser_id' => $validated['listing_import_parser_id'] ?? null,
        ])->save();

        return redirect()->route('admin.users.index')->with('status', 'اطلاعات کاربر به‌روزرسانی شد.');
    }

    public function destroy(Request $request, User $user, ImageService $imageService): RedirectResponse|JsonResponse
    {
        abort_if($request->user()->is($user), 422, 'حساب کاربری خودتان را نمی‌توانید حذف کنید.');
        abort_if($user->isSeniorAdmin(), 403);
        abort_if($user->listings()->exists(), 422, 'این کاربر آگهی دارد و تا زمان حذف آگهی‌ها قابل حذف نیست.');
        abort_if($user->isAdmin() && User::query()->where('role', 'admin')->count() <= 1, 422, 'آخرین مدیر سیستم قابل حذف نیست.');

        DB::transaction(function () use ($user): void {
            // حذف از مسیر مدل باعث اجرای پاک‌سازی فایل‌های لوگو و مجوز فروشگاه می‌شود.
            $user->storefront?->delete();
            $user->notifications()->delete();
            $user->delete();
        });

        $message = 'کاربر بدون آگهی با موفقیت حذف شد.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('admin.users.index')->with('status', $message);
    }

    public function create()
    {
        $importParsers = ListingImportParser::query()->where('is_active', true)->orderBy('title')->get();

        return view('admin.users.create', compact('importParsers'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        abort_if($validated['role'] !== 'user' && ! $request->user()->isSeniorAdmin(), 403);
        $user = User::create([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'national_id' => $validated['national_id'] ?? null,
            'email' => $validated['email'] ?? null,
            'password' => $validated['password'],
            'password_set_at' => now(),
        ]);
        $user->forceFill([
            ...User::attributesForAdministrationRole($validated['role']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'can_post_listings' => (bool) ($validated['can_post_listings'] ?? false),
            'listing_import_parser_id' => $validated['listing_import_parser_id'] ?? null,
            'mobile_verified_at' => now(),
        ])->save();

        return redirect()->route('admin.users.index')->with('status', 'کاربر جدید با موفقیت ایجاد شد.');
    }

    public function index(Request $request)
    {
        $perPage = $this->adminPerPage($request);
        $perPageOptions = $this->adminPerPageOptions();
        $role = $request->input('role');
        $role = is_string($role) && array_key_exists($role, User::ADMINISTRABLE_ROLES) ? $role : null;

        $activeOnly = $request->input('active') === '1';

        $userStats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'admins' => User::query()->where('role', 'admin')->where('is_senior_admin', false)->count(),
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
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->when($role !== null, function (Builder $query) use ($role) {
                $attributes = User::attributesForAdministrationRole($role);
                $query->where('role', $attributes['role']);
                if ($role === 'admin') {
                    $query->where('is_senior_admin', false);
                } else {
                    $query->where('department_role', $attributes['department_role']);
                }
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'userStats', 'perPageOptions', 'activeOnly', 'role'));
    }

    public function toggleActive(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_if($request->user()->is($user), 422, 'حساب کاربری خودتان را نمی‌توانید غیرفعال کنید.');
        abort_if($user->isSeniorAdmin(), 403);
        $user->forceFill(['is_active' => ! $user->is_active])->save();
        $message = $user->is_active ? 'کاربر فعال شد.' : 'کاربر غیرفعال شد.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'is_active' => $user->is_active]);
        }

        return back()->with('status', $message);
    }

    public function toggleRole(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_if($user->isSeniorAdmin(), 403);
        abort_unless($request->user()->isSeniorAdmin(), 403);
        abort_if($request->user()->is($user), 422, 'نقش حساب خودتان را نمی‌توانید تغییر دهید.');
        $data = $request->validate(['role' => ['sometimes', 'required', \Illuminate\Validation\Rule::in(array_keys(User::ADMINISTRABLE_ROLES))]]);
        $role = $data['role'] ?? ($user->isAdmin() ? 'user' : 'admin');
        abort_if($role !== 'admin' && $user->isAdmin() && User::query()->where('role', 'admin')->count() <= 1, 422, 'حداقل یک مدیر باید در سیستم باقی بماند.');

        $user->forceFill(User::attributesForAdministrationRole($role))->save();
        $message = 'نقش کاربر به «'.User::ADMINISTRABLE_ROLES[$role].'» تغییر کرد.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'role' => $user->administrationRole()]);
        }

        return back()->with('status', $message);
    }

    public function toggleListingPermission(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_if($user->isSeniorAdmin(), 403);
        $user->forceFill(['can_post_listings' => ! $user->can_post_listings])->save();
        $message = $user->can_post_listings ? 'اجازه ثبت آگهی برای کاربر فعال شد.' : 'اجازه ثبت آگهی برای کاربر غیرفعال شد.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'can_post_listings' => $user->can_post_listings]);
        }

        return back()->with('status', $message);
    }
}
