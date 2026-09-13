<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;
        $phone = trim((string) env('FARAST_ADMIN_PHONE', '09151234567'));
        if ($phone === '') return;
        $password = (string) env('FARAST_ADMIN_PASSWORD', '');
        $user = User::where('mobile', $phone)->first();
        if (!$user && $password === '') return;
        $user ??= new User(['mobile' => $phone]);
        $user->name = $user->name ?: 'مدیر اصلی';
        $user->role = 'admin';
        $user->is_verified = true;
        $user->is_blocked = false;
        if ($password !== '' && !$user->password) $user->password = Hash::make($password);
        $user->save();
    }

    public function down(): void
    {
        $phone = trim((string) env('FARAST_ADMIN_PHONE', '09151234567'));
        if ($phone !== '') User::where('mobile', $phone)->where('role', 'admin')->delete();
    }
};
