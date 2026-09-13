<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Models\PricingRule;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserCapability;
use App\Services\CapabilityService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(CapabilityService $capabilities)
    {
        $users = User::latest()->limit(100)->get();
        $settings = [];
        foreach (['weekly_free_pages','max_file_mb','daily_ai_requests','can_type','can_ai','can_voice','can_export_docx','can_export_pdf','can_feedback','can_support','gemini_model'] as $key) {
            $settings[$key] = SiteSetting::read($key, CapabilityService::DEFAULTS[$key] ?? 'gemini-3.8-flash');
        }

        return view('admin.index', [
            'rules' => PricingRule::orderBy('id')->get(),
            'users' => $users,
            'stats' => [
                'users' => User::count(),
                'documents' => \App\Models\TypingDocument::count(),
                'ai' => AiInteraction::count(),
                'ai_today' => AiInteraction::whereDate('created_at', today())->count(),
            ],
            'settings' => $settings,
            'secretStatus' => [
                'gemini' => (bool) SiteSetting::read('gemini_api_key'),
                'files' => (bool) SiteSetting::read('files_api_key'),
            ],
            'capabilities' => $capabilities,
        ]);
    }

    public function updateSettings(Request $r)
    {
        $data = $r->validate([
            'weekly_free_pages' => ['required','integer','min:0','max:999999'],
            'max_file_mb' => ['required','integer','min:1','max:2048'],
            'daily_ai_requests' => ['required','integer','min:0','max:999999'],
            'gemini_model' => ['required','string','max:120'],
            'can_type' => ['nullable','boolean'], 'can_ai' => ['nullable','boolean'], 'can_voice' => ['nullable','boolean'],
            'can_export_docx' => ['nullable','boolean'], 'can_export_pdf' => ['nullable','boolean'], 'can_feedback' => ['nullable','boolean'], 'can_support' => ['nullable','boolean'],
            'gemini_api_key' => ['nullable','string','max:500'], 'files_api_key' => ['nullable','string','max:500'],
        ]);

        foreach (['weekly_free_pages','max_file_mb','daily_ai_requests','gemini_model'] as $key) SiteSetting::write($key, $data[$key]);
        foreach (CapabilityService::BOOLEAN_KEYS as $key) SiteSetting::write($key, $r->boolean($key) ? '1' : '0');
        if (filled($data['gemini_api_key'] ?? null)) SiteSetting::write('gemini_api_key', trim($data['gemini_api_key']), true);
        if (filled($data['files_api_key'] ?? null)) SiteSetting::write('files_api_key', trim($data['files_api_key']), true);

        return back()->with('status', 'تنظیمات کلی ذخیره شد.');
    }

    public function updatePricing(Request $r)
    {
        $d=$r->validate(['key'=>'required|string','value'=>'required|integer|min:0','label'=>'required|string|max:160']);
        PricingRule::updateOrCreate(['key'=>$d['key']],['value'=>$d['value'],'label'=>$d['label'],'active'=>true]);
        return back()->with('status', 'قیمت‌گذاری به‌روزرسانی شد.');
    }

    public function updateUserCapabilities(Request $r, User $user)
    {
        abort_if($user->isAdmin(), 403);
        $data = $r->validate([
            'active' => ['nullable','boolean'], 'can_type' => ['nullable','boolean'], 'can_ai' => ['nullable','boolean'], 'can_voice' => ['nullable','boolean'],
            'can_export_docx' => ['nullable','boolean'], 'can_export_pdf' => ['nullable','boolean'], 'can_feedback' => ['nullable','boolean'], 'can_support' => ['nullable','boolean'],
            'weekly_free_pages' => ['nullable','integer','min:0','max:999999'], 'max_file_mb' => ['nullable','integer','min:1','max:2048'], 'daily_ai_requests' => ['nullable','integer','min:0','max:999999'],
        ]);
        $values = [];
        foreach (['active','can_type','can_ai','can_voice','can_export_docx','can_export_pdf','can_feedback','can_support'] as $key) $values[$key] = $r->has($key) ? $r->boolean($key) : null;
        foreach (['weekly_free_pages','max_file_mb','daily_ai_requests'] as $key) $values[$key] = array_key_exists($key, $data) && $data[$key] !== '' ? $data[$key] : null;
        UserCapability::updateOrCreate(['user_id'=>$user->id], $values);
        return back()->with('status', 'دسترسی‌های کاربر ذخیره شد.');
    }

    public function toggleUser(User $user){abort_if($user->isAdmin(),403);$user->update(['is_blocked'=>!$user->is_blocked]);return back();}
}
