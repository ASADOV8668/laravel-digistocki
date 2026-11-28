<?php

namespace App\Services;

use App\Models\User;

class AdminPriorityModules
{
    public const LABELS = [
        'listing-packages' => 'بسته‌ها و سهمیهٔ آگهی',
        'finance' => 'مدیریت مالی',
        'marketing' => 'تخفیف و تبلیغات',
        'reputation' => 'اعتبار فروشگاه',
        'access-records' => 'دسترسی و سوابق',
        'notification-center' => 'مرکز اعلان‌ها',
    ];

    public const ROLES = ['admin' => 'مدیر ارشد', 'technical' => 'فنی', 'accounting' => 'حسابداری'];

    public function __construct(private readonly SystemOptions $options) {}

    public function settings(): array
    {
        return json_decode((string) $this->options->get('admin_priority_modules', '{}'), true) ?: [];
    }

    public function save(array $settings): void
    {
        $this->options->set('admin_priority_modules', json_encode($settings, JSON_THROW_ON_ERROR));
    }

    public function isSenior(User $user): bool
    {
        return method_exists($user, 'isSeniorAdmin') ? $user->isSeniorAdmin() : $user->isAdmin();
    }

    public function allows(User $user, string $module): bool
    {
        $settings = $this->settings()[$module] ?? [];
        if (! ($settings['enabled'] ?? true)) {
            return false;
        }

        $role = $settings['role'] ?? null;

        return $role === null || $this->isSenior($user)
            || (method_exists($user, 'administrationRole') && $user->administrationRole() === $role);
    }
}
