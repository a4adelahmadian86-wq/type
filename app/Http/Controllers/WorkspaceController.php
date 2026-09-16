<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TypingDocument;
use App\Services\CapabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WorkspaceController extends Controller
{
    public function recent(Request $request)
    {
        $user = $request->user();

        $documents = TypingDocument::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'deleted')
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'status', 'page_count', 'updated_at', 'created_at']);

        $ai = Schema::hasTable('ai_interactions')
            ? AiInteraction::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(15)
                ->get(['id', 'operation', 'status', 'provider', 'latency_ms', 'created_at', 'document_id'])
            : collect();

        $orders = Schema::hasTable('orders')
            ? Order::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get(['id', 'status', 'total_rials', 'created_at', 'document_id'])
            : collect();

        return view('workspace.recent', compact('documents', 'ai', 'orders'));
    }

    public function tasks(Request $request)
    {
        $user = $request->user();

        $openTickets = Schema::hasTable('tickets')
            ? Ticket::query()
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['closed', 'resolved'])
                ->latest()
                ->limit(20)
                ->get()
            : collect();

        $draftDocs = TypingDocument::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['draft', 'pending'])
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'status', 'page_count', 'updated_at']);

        return view('workspace.tasks', compact('openTickets', 'draftDocs'));
    }

    public function actions(Request $request, CapabilityService $capabilities)
    {
        $user = $request->user();
        $caps = $capabilities->forUser($user);

        $unpaid = TypingDocument::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'deleted')
            ->where('price_rials', '>', 0)
            ->whereNotIn('status', ['paid'])
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'status', 'price_rials', 'page_count', 'updated_at']);

        $needsAttention = [];
        if (! $user->is_verified) {
            $needsAttention[] = [
                'title' => 'تأیید حساب',
                'body' => 'حساب شما هنوز تأیید نشده است. برخی سهمیه‌ها پس از تأیید فعال می‌شوند.',
                'icon' => 'fa-user-check',
            ];
        }
        if (! ($caps['active'] ?? false)) {
            $needsAttention[] = [
                'title' => 'دسترسی محدود',
                'body' => 'قابلیت‌های عملیاتی حساب شما توسط سامانه محدود شده است.',
                'icon' => 'fa-lock',
            ];
        }
        if ($unpaid->isNotEmpty()) {
            $needsAttention[] = [
                'title' => 'اسناد نیازمند تسویه',
                'body' => number_format($unpaid->count()).' سند هنوز هزینه خروجی تسویه‌نشده دارند.',
                'icon' => 'fa-file-invoice-dollar',
            ];
        }

        return view('workspace.actions', compact('unpaid', 'needsAttention', 'caps'));
    }

    public function shortcuts(CapabilityService $capabilities)
    {
        $caps = $capabilities->forUser(auth()->user());

        $items = [
            ['label' => 'داشبورد', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'show' => true],
            ['label' => 'ویرایشگر', 'route' => 'editor', 'icon' => 'fa-pen-to-square', 'show' => (bool) ($caps['can_type'] ?? false)],
            ['label' => 'کتابخانه فایل', 'route' => 'library', 'icon' => 'fa-folder-open', 'show' => true],
            ['label' => 'کیف پول', 'route' => 'wallet', 'icon' => 'fa-wallet', 'show' => true],
            ['label' => 'پشتیبانی', 'route' => 'support', 'icon' => 'fa-headset', 'show' => (bool) ($caps['can_support'] ?? false)],
            ['label' => 'اعلان‌ها', 'route' => 'announcements', 'icon' => 'fa-bell', 'show' => true],
            ['label' => 'تنظیمات حساب', 'route' => 'account.show', 'icon' => 'fa-user-gear', 'show' => true],
        ];

        if (auth()->user()->isAdmin()) {
            $items[] = ['label' => 'پنل مدیریت', 'route' => 'admin.index', 'icon' => 'fa-user-shield', 'show' => true];
            $items[] = ['label' => 'مالی ادمین', 'route' => 'admin.finance', 'icon' => 'fa-money-bill-transfer', 'show' => true];
        }

        $items = array_values(array_filter($items, fn ($i) => $i['show']));

        return view('workspace.shortcuts', compact('items'));
    }
}
