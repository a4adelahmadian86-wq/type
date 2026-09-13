<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $mobile = '09151234567';
        $user = User::firstOrNew(['mobile' => $mobile]);
        $user->name = $user->name ?: 'مدیر اصلی';
        $user->role = 'admin';
        $user->is_verified = true;
        $user->is_blocked = false;
        $user->password = '$2y$12$Ei1bXZC7d48cRmspFed5aOLaj1fsS.ay3KqoxpJqpn8X..o24I4/6';
        $user->save();
    }

    public function down(): void
    {
        // Intentionally do not delete or demote the master account on rollback.
    }
};
