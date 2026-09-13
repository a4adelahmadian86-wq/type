<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TypingDocument;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminOperationsController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $week = Carbon::now()->startOfWeek();
        $month = Carbon::now()->startOfMonth();

        $orders = Schema::hasTable('orders') ? Order::query() : null;
        $payments = Schema::hasTable('payments') ? Payment::query() : null;

        $stats = [
            'sales_today' => $orders ? (int) $orders->where('status', 'paid')->whereDate('paid_at', $today)->sum('total_rials') : 0,
            'sales_week' => $orders ? (int) $orders->where('status', 'paid')->where('paid_at', '>=', $week)->sum('total_rials') : 0,
            'sales_month' => $orders ? (int) $orders->where('status', 'paid')->where('paid_at', '>=', $month)->sum('total_rials') : 0,
            'orders' => $orders ? (int) $orders->count() : 0,
            'paid_orders' => $orders ? (int) $orders->where('status', 'paid')->count() : 0,
            'failed_payments' => $payments ? (int) $payments->whereIn('status', ['failed', 'canceled'])->count() : 0,
            'active_users' => (int) User::where('is_blocked', false)->count(),
            'new_users' => (int) User::where('created_at', '>=', $today)->count(),
            'typing_jobs' => Schema::hasTable('typing_documents') ? (int) TypingDocument::count() : 0,
            'ai_requests' => Schema::hasTable('ai_interactions') ? (int) AiInteraction::count() : 0,
            'ai_today' => Schema::hasTable('ai_interactions') ? (int) AiInteraction::whereDate('created_at', $today)->count() : 0,
            'support_backlog' => Schema::hasTable('tickets') ? (int) Ticket::whereIn('status', ['open', 'waiting_staff', 'in_progress'])->count() : 0,
            'wallet_liability' => Schema::hasTable('wallets') ? (int) Wallet::sum('balance_rials') : 0,
        ];

        $health = [
            'database' => $this->databaseHealth(),
            'cache' => $this->cacheHealth(),
            'storage' => $this->storageHealth(),
            'queue' => $this->queueHealth(),
            'scheduler' => [
                'status' => 'configured',
                'label' => 'Scheduler تعریف شده است',
                'detail' => 'وظایف زمان‌بندی‌شده از routes/console.php خوانده می‌شوند.',
            ],
        ];

        $recentOrders = $orders
            ? $orders->with('user')->latest()->limit(12)->get()
            : collect();

        $recentPayments = $payments
            ? $payments->with('order')->latest()->limit(12)->get()
            : collect();

        return view('admin.operations', compact('stats', 'health', 'recentOrders', 'recentPayments'));
    }

    private function databaseHealth(): array
    {
        try {
            DB::select('select 1');
            return ['status' => 'ok', 'label' => 'پایگاه‌داده سالم', 'detail' => DB::getDriverName()];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'label' => 'خطا در پایگاه‌داده', 'detail' => 'اتصال برقرار نشد.'];
        }
    }

    private function cacheHealth(): array
    {
        try {
            cache()->put('farast_health_check', now()->timestamp, 30);
            return ['status' => 'ok', 'label' => 'Cache سالم', 'detail' => config('cache.default')];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'label' => 'Cache در دسترس نیست', 'detail' => 'بررسی تنظیمات cache لازم است.'];
        }
    }

    private function storageHealth(): array
    {
        try {
            $path = storage_path('app');
            $ok = is_dir($path) && is_writable($path);
            return [
                'status' => $ok ? 'ok' : 'warning',
                'label' => $ok ? 'Storage قابل نوشتن است' : 'Storage نیازمند بررسی است',
                'detail' => $ok ? 'مسیر محلی قابل استفاده است.' : 'مسیر storage/app وجود ندارد یا قابل نوشتن نیست.',
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'label' => 'Storage بررسی نشد', 'detail' => 'خطای سیستم فایل.'];
        }
    }

    private function queueHealth(): array
    {
        if (! Schema::hasTable('jobs')) {
            return ['status' => 'warning', 'label' => 'Queue جدول jobs ندارد', 'detail' => 'برای پردازش‌های صفی migration مربوط به jobs را بررسی کنید.'];
        }

        $pending = (int) DB::table('jobs')->count();
        $failed = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;

        return [
            'status' => $failed > 0 ? 'warning' : 'ok',
            'label' => $failed > 0 ? 'Queue دارای failed job است' : 'Queue سالم',
            'detail' => "در صف: {$pending} | ناموفق: {$failed}",
        ];
    }
}
