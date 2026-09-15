<?php

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
        $now = now();
        $existing = DB::table('users')->where('mobile', $mobile)->first();

        if ($existing) {
            // Bypass the model's `hashed` cast because this migration must not
            // re-hash an existing password merely while promoting the account.
            DB::table('users')->where('mobile', $mobile)->update([
                'name' => $existing->name ?: 'مدیر اصلی',
                'role' => 'admin',
                'is_verified' => true,
                'is_blocked' => false,
                'updated_at' => $now,
            ]);

            return;
        }

        // This is a precomputed bcrypt value for the bootstrap account. Insert it
        // directly so Laravel's `hashed` cast cannot hash an already-hashed value.
        DB::table('users')->insert([
            'mobile' => $mobile,
            'name' => 'مدیر اصلی',
            'role' => 'admin',
            'is_verified' => true,
            'is_blocked' => false,
            'password' => '$2y$12$Ei1bXZC7d48cRmspFed5aOLaj1fsS.ay3KqoxpJqpn8X..o24I4/',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Intentionally do not delete or demote the master account on rollback.
    }
};
