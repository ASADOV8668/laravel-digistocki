<x-admin-layout title="کنترل اولویت‌ها">
    <x-slot name="header">
        <h1 class="text-xl font-black">کنترل اولویت‌ها</h1>
    </x-slot>

    <form method="POST" action="{{ route('admin.priority-modules.update') }}" class="admin-card space-y-4 p-5">
        @csrf
        @method('PUT')
        <p class="text-sm">این تنظیمات دسترسی به بخش‌های مدیریتی را کنترل می‌کنند. مدیر ارشد به بخش‌های فعال دسترسی دارد. نقش انتخابی همچنان به مجوزهای فعلی آن بخش نیاز دارد.</p>
        @if (session('status'))
            <p role="status" class="text-sm text-success">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul role="alert" class="list-inside list-disc text-sm text-error">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        @foreach (\App\Services\AdminPriorityModules::LABELS as $key => $label)
            <fieldset class="grid gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700 sm:grid-cols-2">
                <legend class="px-1 font-bold">{{ $label }}</legend>
                <label class="block text-sm">وضعیت بخش
                    <select name="modules[{{ $key }}][enabled]" class="public-input mt-1 w-full">
                        <option value="1" @selected(old("modules.$key.enabled", $settings[$key]['enabled'] ?? true))>فعال</option>
                        <option value="0" @selected(!old("modules.$key.enabled", $settings[$key]['enabled'] ?? true))>غیرفعال</option>
                    </select>
                </label>
                <label class="block text-sm">نقش مسئول
                    <select name="modules[{{ $key }}][role]" class="public-input mt-1 w-full">
                        <option value="">طبق مجوزهای فعلی</option>
                        @foreach (\App\Services\AdminPriorityModules::ROLES as $role => $roleLabel)
                            <option value="{{ $role }}" @selected(old("modules.$key.role", $settings[$key]['role'] ?? null) === $role)>{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                </label>
            </fieldset>
        @endforeach
        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white">ذخیرهٔ تنظیمات</button>
    </form>
</x-admin-layout>
