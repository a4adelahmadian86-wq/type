<?php

namespace App\Http\Controllers;

use App\Services\CapabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function show(Request $request, CapabilityService $capabilities)
    {
        return view('account.show', [
            'user' => $request->user(),
            'capabilities' => $capabilities->forUser($request->user()),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email|max:190',
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
        ])->save();

        return back()->with('status', 'اطلاعات حساب به‌روزرسانی شد.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if ($user->password && ! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'رمز فعلی نادرست است.']);
        }

        $user->forceFill(['password' => $data['password']])->save();

        return back()->with('status', 'رمز عبور با موفقیت تغییر کرد.');
    }
}
