<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Honest empty-state pages for navigation items that are registered
 * but do not yet have a full feature implementation.
 */
class ModulePageController extends Controller
{
    public function show(Request $request, string $key)
    {
        $catalog = [
            'folders' => [
                'title' => 'پوشه‌ها',
                'eyebrow' => 'اسناد',
                'icon' => 'fa-folder-tree',
                'body' => 'سامانهٔ پوشه‌بندی هنوز فعال نشده است. اسناد از فهرست «اسناد من» در دسترس‌اند.',
            ],
            'shared' => [
                'title' => 'اشتراک‌گذاری',
                'eyebrow' => 'اسناد',
                'icon' => 'fa-share-nodes',
                'body' => 'اشتراک‌گذاری تیمی در این نسخه پیاده‌سازی نشده است.',
            ],
            'archive' => [
                'title' => 'بایگانی',
                'eyebrow' => 'اسناد',
                'icon' => 'fa-box-archive',
                'body' => 'بایگانی اسناد هنوز در دسترس نیست.',
            ],
            'templates' => [
                'title' => 'قالب‌ها',
                'eyebrow' => 'ویرایشگر',
                'icon' => 'fa-layer-group',
                'body' => 'قالب‌های آماده هنوز اضافه نشده‌اند.',
            ],
            'default' => [
                'title' => 'این بخش',
                'eyebrow' => 'فضای کاری',
                'icon' => 'fa-puzzle-piece',
                'body' => 'این مسیر ثبت شده اما قابلیت کامل هنوز آماده نیست؛ داده جعلی نمایش داده نمی‌شود.',
            ],
        ];

        $page = $catalog[$key] ?? array_merge($catalog['default'], [
            'title' => $key,
        ]);

        return view('modules.empty', compact('page'));
    }
}
