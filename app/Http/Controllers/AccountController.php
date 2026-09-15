<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function profile()
    {
        $user = auth()->user()->load('wallet');
        return view('account.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);
        $user->update($data);
        return back()->with('status', 'اطلاعات پروفایل ذخیره شد.');
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'email_updates' => ['nullable', 'boolean'],
            'ticket_updates' => ['nullable', 'boolean'],
            'product_updates' => ['nullable', 'boolean'],
            'analytics' => ['nullable', 'boolean'],
        ]);
        $request->user()->update([
            'notification_preferences' => [
                'email_updates' => (bool) ($data['email_updates'] ?? false),
                'ticket_updates' => (bool) ($data['ticket_updates'] ?? false),
                'product_updates' => (bool) ($data['product_updates'] ?? false),
            ],
            'privacy_preferences' => ['analytics' => (bool) ($data['analytics'] ?? false)],
        ]);
        return back()->with('status', 'تنظیمات اعلان و حریم خصوصی ذخیره شد.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $request->user()->update(['password' => $data['password']]);
        return back()->with('status', 'رمز عبور با موفقیت تغییر کرد.');
    }
}
