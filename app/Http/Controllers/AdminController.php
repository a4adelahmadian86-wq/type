<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Models\Announcement;
use App\Models\EmailLog;
use App\Models\PricingRule;
use App\Models\SiteSetting;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserCapability;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\CapabilityService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function index(CapabilityService $capabilities)
    {
        $users = User::latest()->limit(100)->get();
        $settings = [];
        foreach (['weekly_free_pages', 'max_file_mb', 'daily_ai_requests', 'can_type', 'can_ai', 'can_voice', 'can_export_docx', 'can_export_pdf', 'can_feedback', 'can_support', 'gemini_model'] as $key) {
            $settings[$key] = SiteSetting::read($key, CapabilityService::DEFAULTS[$key] ?? 'gemini-3.8-flash');
        }

        return view('admin.index', [
            'rules' => PricingRule::orderBy('id')->get(),
            'users' => $users,
            'announcements' => Announcement::latest()->limit(20)->get(),
            'stats' => [
                'users' => User::count(),
                'documents' => \App\Models\TypingDocument::count(),
                'ai' => AiInteraction::count(),
                'ai_today' => AiInteraction::whereDate('created_at', today())->count(),
            ],
            'settings' => $settings,
            'secretStatus' => [
                'gemini' => (bool) (SiteSetting::read('gemini_api_key') ?: config('services.gemini.key')),
                'files' => (bool) (SiteSetting::read('files_api_key') ?: env('FILES_API_KEY')),
            ],
            'capabilities' => $capabilities,
        ]);
    }

    public function finance()
    {
        return view('admin.finance', [
            'taxEnabled' => filter_var(SiteSetting::read('tax_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'taxRate' => (float) SiteSetting::read('tax_rate_percent', 10),
            'gateway' => (string) SiteSetting::read('payment_gateway', 'none'),
            'merchantConfigured' => (bool) SiteSetting::read('gateway_merchant_id'),
            'users' => User::orderBy('name')->limit(100)->get(),
        ]);
    }

    public function updateFinance(Request $r)
    {
        $d = $r->validate([
            'tax_enabled' => ['nullable', 'boolean'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_gateway' => ['required', 'in:none'],
            'gateway_merchant_id' => ['nullable', 'string', 'max:200'],
        ]);

        SiteSetting::write('tax_enabled', $r->boolean('tax_enabled') ? '1' : '0');
        SiteSetting::write('tax_rate_percent', (string) $d['tax_rate_percent']);
        SiteSetting::write('payment_gateway', $d['payment_gateway']);

        if (filled($d['gateway_merchant_id'] ?? null)) {
            SiteSetting::write('gateway_merchant_id', trim($d['gateway_merchant_id']), true);
        }

        return back()->with('status', 'تنظیمات مالی ذخیره شد.');
    }

    public function adjustWallet(Request $r, User $user)
    {
        $d = $r->validate([
            'type' => ['required', 'in:credit,debit'],
            'amount_rials' => ['required', 'integer', 'min:1000', 'max:10000000000'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($d, $user) {
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance_rials' => 0]);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $before = (int) $wallet->balance_rials;

            if ($d['type'] === 'debit' && $d['amount_rials'] > $before) {
                throw new \RuntimeException('موجودی کاربر کافی نیست.');
            }

            $after = $d['type'] === 'credit' ? $before + $d['amount_rials'] : $before - $d['amount_rials'];
            $wallet->update(['balance_rials' => $after]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $d['type'],
                'amount_rials' => $d['amount_rials'],
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => 'admin',
                'reference_id' => auth()->id(),
                'description' => $d['description'] ?: ($d['type'] === 'credit' ? 'شارژ توسط مدیر' : 'کسر توسط مدیر'),
                'idempotency_key' => 'admin-'.auth()->id().'-'.bin2hex(random_bytes(12)),
            ]);
        });

        return back()->with('status', 'موجودی کیف پول کاربر به‌روزرسانی شد.');
    }

    public function updateSettings(Request $r)
    {
        $data = $r->validate([
            'weekly_free_pages' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'max_file_mb' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'daily_ai_requests' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'gemini_model' => ['nullable', 'string', 'max:120'],
            'can_type' => ['nullable', 'boolean'],
            'can_ai' => ['nullable', 'boolean'],
            'can_voice' => ['nullable', 'boolean'],
            'can_export_docx' => ['nullable', 'boolean'],
            'can_export_pdf' => ['nullable', 'boolean'],
            'can_feedback' => ['nullable', 'boolean'],
            'can_support' => ['nullable', 'boolean'],
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'files_api_key' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (['weekly_free_pages', 'max_file_mb', 'daily_ai_requests', 'gemini_model'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                SiteSetting::write($key, $data[$key]);
            }
        }

        foreach (CapabilityService::BOOLEAN_KEYS as $key) {
            if ($r->has($key)) {
                SiteSetting::write($key, $r->boolean($key) ? '1' : '0');
            }
        }

        if (filled($data['gemini_api_key'] ?? null)) {
            SiteSetting::write('gemini_api_key', trim($data['gemini_api_key']), true);
        }

        if (filled($data['files_api_key'] ?? null)) {
            SiteSetting::write('files_api_key', trim($data['files_api_key']), true);
        }

        return back()->with('status', 'تنظیمات کلی ذخیره شد.');
    }

    public function updatePricing(Request $r)
    {
        $d = $r->validate([
            'key' => 'required|string',
            'value' => 'required|integer|min:0',
            'label' => 'required|string|max:160',
        ]);

        PricingRule::updateOrCreate(['key' => $d['key']], [
            'value' => $d['value'],
            'label' => $d['label'],
            'active' => true,
        ]);

        return back()->with('status', 'قیمت‌گذاری به‌روزرسانی شد.');
    }

    public function updateUserCapabilities(Request $r, User $user)
    {
        abort_if($user->isAdmin(), 403);

        $data = $r->validate([
            'active' => ['nullable', 'boolean'],
            'can_type' => ['nullable', 'boolean'],
            'can_ai' => ['nullable', 'boolean'],
            'can_voice' => ['nullable', 'boolean'],
            'can_export_docx' => ['nullable', 'boolean'],
            'can_export_pdf' => ['nullable', 'boolean'],
            'can_feedback' => ['nullable', 'boolean'],
            'can_support' => ['nullable', 'boolean'],
            'weekly_free_pages' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'max_file_mb' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'daily_ai_requests' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $values = [];
        foreach (['active', 'can_type', 'can_ai', 'can_voice', 'can_export_docx', 'can_export_pdf', 'can_feedback', 'can_support'] as $key) {
            $values[$key] = $r->has($key) ? $r->boolean($key) : null;
        }

        foreach (['weekly_free_pages', 'max_file_mb', 'daily_ai_requests'] as $key) {
            $values[$key] = array_key_exists($key, $data) && $data[$key] !== '' ? $data[$key] : null;
        }

        UserCapability::updateOrCreate(['user_id' => $user->id], $values);

        return back()->with('status', 'دسترسی‌های کاربر ذخیره شد.');
    }

    public function toggleUser(User $user)
    {
        abort_if($user->isAdmin(), 403);
        $user->update(['is_blocked' => ! $user->is_blocked]);

        return back();
    }

    public function emails()
    {
        $flags = ['email_enabled' => true];
        foreach (array_keys(EmailService::TYPES) as $type) {
            $flags['email_'.$type.'_enabled'] = true;
        }

        foreach ($flags as $key => $default) {
            $flags[$key] = filter_var(SiteSetting::read($key, $default), FILTER_VALIDATE_BOOLEAN);
        }

        $logs = Schema::hasTable('email_logs')
            ? EmailLog::latest()->limit(80)->get()
            : collect();

        $tickets = Schema::hasTable('tickets')
            ? Ticket::with('user')->latest()->limit(30)->get()
            : collect();

        return view('admin.emails', [
            'flags' => $flags,
            'types' => EmailService::TYPES,
            'logs' => $logs,
            'tickets' => $tickets,
            'mailFrom' => config('mail.from.address'),
            'mailer' => config('mail.default'),
        ]);
    }

    public function updateEmailSettings(Request $request)
    {
        SiteSetting::write('email_enabled', $request->boolean('email_enabled') ? '1' : '0');

        foreach (array_keys(EmailService::TYPES) as $type) {
            $key = 'email_'.$type.'_enabled';
            SiteSetting::write($key, $request->boolean($key) ? '1' : '0');
        }

        SiteSetting::write('email_sync', $request->boolean('email_sync') ? '1' : '0');

        return back()->with('status', 'تنظیمات ایمیل ذخیره شد.');
    }

    public function sendTestEmail(Request $request, EmailService $emailService)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $emailService->sendTest($data['test_email']);

            return back()->with('status', 'ایمیل آزمایشی به '.$data['test_email'].' ارسال شد.');
        } catch (\Throwable $e) {
            return back()->withErrors(['test_email' => 'ارسال ناموفق: '.$e->getMessage()]);
        }
    }
}
