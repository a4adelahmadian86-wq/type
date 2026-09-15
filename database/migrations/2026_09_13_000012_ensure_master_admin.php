<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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

        // The User model intentionally uses Laravel's `hashed` cast. This value is
        // already a bcrypt hash, so write it through the query builder to avoid the
        // cast attempting to treat the existing hash as a new password during CI.
        if (! $user->exists) {
            DB::table('users')->insert([
                'mobile' => $mobile,
                'name' => 'مدیر اصلی',
                'role' => 'admin',
                'is_verified' => true,
                'is_blocked' => false,
                'password' => '$2y$12$Ei1bXZC7d48cRmspFed5aOLaj1fsS.ay3KqoxpJqpn8X..o24I4/',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $user->save();
    }

    public function down(): void
    {
        // Intentionally do not delete or demote the master account on rollback.
    }
};
