<?php

namespace App\Services;

use App\Models\FreeCredit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FreeQuotaService
{
    public function availablePages(User $user, int $configuredPages): int
    {
        if ($user->isAdmin() || $configuredPages <= 0) return 0;
        $credit = $this->current($user, $configuredPages);
        return $credit->remaining();
    }

    public function consume(User $user, int $pages = 1, int $configuredPages = 1): int
    {
        if ($user->isAdmin() || $pages <= 0 || $configuredPages <= 0) return 0;
        $credit = $this->current($user, $configuredPages, true);
        $use = min($pages, $credit->remaining());
        if ($use > 0) $credit->increment('consumed_pages', $use);
        return $use;
    }

    private function current(User $user, int $configuredPages, bool $lock = false): FreeCredit
    {
        $hash = hash('sha256', trim($user->mobile));
        $week = now()->startOfWeek()->toDateString();
        $credit = FreeCredit::firstOrCreate(
            ['mobile_hash' => $hash, 'week_start' => $week],
            ['user_id' => $user->id, 'granted_pages' => $configuredPages, 'consumed_pages' => 0]
        );
        if ($credit->user_id !== $user->id) $credit->update(['user_id' => $user->id]);
        if ((int)$credit->granted_pages !== $configuredPages) $credit->update(['granted_pages' => $configuredPages]);
        if ($lock) $credit->refresh()->lockForUpdate();
        return $credit;
    }
}
