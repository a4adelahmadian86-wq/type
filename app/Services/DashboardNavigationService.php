<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class DashboardNavigationService
{
    public function forUser(?User $user): array
    {
        $isAdmin = $user?->isAdmin() === true;

        return $isAdmin ? $this->adminNavigation() : $this->memberNavigation($user);
    }

    private function adminNavigation(): array
    {
        $admin = static fn (string $label, string $icon, string $fragment = ''): array => [
            'label' => $label,
            'icon' => $icon,
            'href' => route('admin.index').$fragment,
            'active' => request()->routeIs('admin.index') && ($fragment === '' || request()->getRequestUri() === route('admin.index').$fragment),
        ];

        return [
            [
                'label' => 'میز کار',
                'icon' => 'fa-table-cells-large',
                'items' => [
                    $admin('نمای کلی', 'fa-grid-2'),
                    $admin('کاربران و دسترسی‌ها', 'fa-users', '#users'),
                    $admin('وضعیت عملیات', 'fa-chart-line', '#operations'),
                ],
            ],
            [
                'label' => 'اسناد و فایل‌ها',
                'icon' => 'fa-folder-open',
                'items' => [
                    ['label' => 'اسناد سیستم', 'icon' => 'fa-file-lines', 'href' => null, 'disabled' => true],
                    ['label' => 'فایل‌ها', 'icon' => 'fa-folder', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'ویرایشگر',
                'icon' => 'fa-pen-ruler',
                'items' => [
                    ['label' => 'شروع تایپ', 'icon' => 'fa-plus', 'href' => route('editor'), 'active' => request()->routeIs('editor')],
                    ['label' => 'اسناد اخیر', 'icon' => 'fa-clock-rotate-left', 'href' => route('admin.index').'#users', 'active' => false],
                ],
            ],
            [
                'label' => 'اتوماسیون و گردش کار',
                'icon' => 'fa-diagram-project',
                'items' => [
                    ['label' => 'گردش‌کارها', 'icon' => 'fa-route', 'href' => null, 'disabled' => true],
                    ['label' => 'تاریخچه اجراها', 'icon' => 'fa-clock-rotate-left', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'هوش مصنوعی',
                'icon' => 'fa-wand-magic-sparkles',
                'items' => [
                    $admin('وضعیت سرویس‌ها', 'fa-circle-nodes', '#ai'),
                    $admin('تنظیمات AI', 'fa-sliders', '#ai-settings'),
                ],
            ],
            [
                'label' => 'تیم و همکاری',
                'icon' => 'fa-people-group',
                'items' => [
                    ['label' => 'تیم‌ها', 'icon' => 'fa-users-rectangle', 'href' => null, 'disabled' => true],
                    ['label' => 'اشتراک‌گذاری', 'icon' => 'fa-share-nodes', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'گزارش‌ها و تحلیل‌ها',
                'icon' => 'fa-chart-pie',
                'items' => [
                    ['label' => 'گزارش‌های مدیریتی', 'icon' => 'fa-chart-column', 'href' => null, 'disabled' => true],
                    ['label' => 'مصرف و عملکرد', 'icon' => 'fa-gauge-high', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'اعلان‌ها و پیام‌ها',
                'icon' => 'fa-bell',
                'items' => [
                    ['label' => 'اعلان‌های سامانه', 'icon' => 'fa-bullhorn', 'href' => route('announcements'), 'active' => request()->routeIs('announcements')],
                    ['label' => 'مدیریت اعلان‌ها', 'icon' => 'fa-bullhorn', 'href' => route('admin.index').'#content', 'active' => false],
                ],
            ],
            [
                'label' => 'مدیریت کاربران و دسترسی‌ها',
                'icon' => 'fa-user-shield',
                'items' => [
                    $admin('کاربران', 'fa-users', '#users'),
                    ['label' => 'نقش‌ها و مجوزها', 'icon' => 'fa-key', 'href' => null, 'disabled' => true],
                    ['label' => 'دعوت‌نامه‌ها', 'icon' => 'fa-user-plus', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'مدیریت سازمان',
                'icon' => 'fa-building',
                'items' => [
                    ['label' => 'اطلاعات سازمان', 'icon' => 'fa-building', 'href' => null, 'disabled' => true],
                    ['label' => 'سیاست‌ها و محدودیت‌ها', 'icon' => 'fa-shield-halved', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'امنیت و نظارت',
                'icon' => 'fa-shield-halved',
                'items' => [
                    ['label' => 'گزارش فعالیت‌ها', 'icon' => 'fa-list-check', 'href' => null, 'disabled' => true],
                    ['label' => 'تنظیمات امنیتی', 'icon' => 'fa-lock', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'تنظیمات و پشتیبانی',
                'icon' => 'fa-gear',
                'items' => [
                    ['label' => 'مالی', 'icon' => 'fa-wallet', 'href' => route('admin.finance'), 'active' => request()->routeIs('admin.finance')],
                    ['label' => 'ایمیل', 'icon' => 'fa-envelope', 'href' => route('admin.emails'), 'active' => request()->routeIs('admin.emails')],
                    ['label' => 'شبکه‌های اجتماعی', 'icon' => 'fa-share-nodes', 'href' => route('admin.social'), 'active' => request()->routeIs('admin.social')],
                    ['label' => 'پشتیبانی', 'icon' => 'fa-headset', 'href' => route('support'), 'active' => request()->routeIs('support')],
                ],
            ],
        ];
    }

    private function memberNavigation(?User $user): array
    {
        $capabilities = $user ? app(CapabilityService::class)->forUser($user) : [];
        $can = static fn (string $key): bool => (bool) ($capabilities[$key] ?? false);

        $item = static function (string $label, string $icon, string $routeName, bool $active = false, ?string $requiredCapability = null) use ($can): array {
            if (! Route::has($routeName)) {
                return ['label' => $label, 'icon' => $icon, 'href' => null, 'disabled' => true];
            }

            if ($requiredCapability !== null && ! $can($requiredCapability)) {
                return ['label' => $label, 'icon' => $icon, 'href' => null, 'disabled' => true];
            }

            return ['label' => $label, 'icon' => $icon, 'href' => route($routeName), 'active' => $active];
        };

        return [
            [
                'label' => 'میز کار',
                'icon' => 'fa-table-cells-large',
                'items' => [
                    $item('نمای کلی', 'fa-grid-2', 'dashboard', request()->routeIs('dashboard')),
                    ['label' => 'فعالیت‌های اخیر', 'icon' => 'fa-clock-rotate-left', 'href' => null, 'disabled' => true],
                    ['label' => 'موارد نیازمند اقدام', 'icon' => 'fa-list-check', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'اسناد و فایل‌ها',
                'icon' => 'fa-folder-open',
                'items' => [
                    $item('اسناد من', 'fa-file-lines', 'dashboard', request()->routeIs('dashboard')),
                    $item('فایل‌ها', 'fa-folder', 'library', request()->routeIs('library')),
                    ['label' => 'اسناد اشتراکی', 'icon' => 'fa-share-nodes', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'ویرایشگر',
                'icon' => 'fa-pen-ruler',
                'items' => [
                    $item('شروع تایپ', 'fa-plus', 'editor', request()->routeIs('editor'), 'can_type'),
                    $item('اسناد اخیر', 'fa-clock-rotate-left', 'dashboard', request()->routeIs('dashboard')),
                ],
            ],
            [
                'label' => 'اتوماسیون و گردش کار',
                'icon' => 'fa-diagram-project',
                'items' => [
                    ['label' => 'گردش‌کارها', 'icon' => 'fa-route', 'href' => null, 'disabled' => true],
                    ['label' => 'تاریخچه اجراها', 'icon' => 'fa-clock-rotate-left', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'هوش مصنوعی',
                'icon' => 'fa-wand-magic-sparkles',
                'items' => [
                    $item('ابزارهای AI', 'fa-sparkles', 'editor', request()->routeIs('editor'), 'can_ai'),
                    ['label' => 'درخواست‌ها و تاریخچه', 'icon' => 'fa-clock-rotate-left', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'تیم و همکاری',
                'icon' => 'fa-people-group',
                'items' => [
                    ['label' => 'تیم‌های من', 'icon' => 'fa-users-rectangle', 'href' => null, 'disabled' => true],
                    ['label' => 'اشتراک‌گذاری و همکاری', 'icon' => 'fa-share-nodes', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'گزارش‌ها و تحلیل‌ها',
                'icon' => 'fa-chart-pie',
                'items' => [
                    ['label' => 'گزارش‌های شخصی', 'icon' => 'fa-chart-column', 'href' => null, 'disabled' => true],
                    ['label' => 'مصرف و عملکرد', 'icon' => 'fa-gauge-high', 'href' => null, 'disabled' => true],
                ],
            ],
            [
                'label' => 'اعلان‌ها و پیام‌ها',
                'icon' => 'fa-bell',
                'items' => [
                    $item('اعلان‌های من', 'fa-bullhorn', 'announcements', request()->routeIs('announcements')),
                ],
            ],
            [
                'label' => 'تنظیمات و پشتیبانی',
                'icon' => 'fa-gear',
                'items' => [
                    ['label' => 'تنظیمات حساب', 'icon' => 'fa-user-gear', 'href' => null, 'disabled' => true],
                    $item('پشتیبانی', 'fa-headset', 'support', request()->routeIs('support'), 'can_support'),
                    ['label' => 'راهنما', 'icon' => 'fa-circle-question', 'href' => null, 'disabled' => true],
                ],
            ],
        ];
    }
}
