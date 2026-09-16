<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Services\CapabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AiWorkspaceController extends Controller
{
    public function history(Request $request)
    {
        abort_unless(Schema::hasTable('ai_interactions'), 404);

        $items = AiInteraction::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(25);

        return view('ai.history', compact('items'));
    }

    public function quota(Request $request, CapabilityService $capabilities)
    {
        $caps = $capabilities->forUser($request->user());

        $usedToday = Schema::hasTable('ai_interactions')
            ? AiInteraction::query()
                ->where('user_id', $request->user()->id)
                ->whereDate('created_at', today())
                ->count()
            : 0;

        $usedTotal = Schema::hasTable('ai_interactions')
            ? AiInteraction::query()->where('user_id', $request->user()->id)->count()
            : 0;

        return view('ai.quota', compact('caps', 'usedToday', 'usedTotal'));
    }
}
