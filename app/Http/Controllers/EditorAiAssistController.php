<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Services\CapabilityService;
use App\Services\EditorAiAssistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EditorAiAssistController extends Controller
{
    public function __invoke(Request $request, EditorAiAssistService $ai, CapabilityService $capabilities)
    {
        $caps = $capabilities->forUser($request->user());
        abort_unless(($caps['active'] ?? false) && ($caps['can_ai'] ?? false), 403, 'قابلیت هوش مصنوعی برای این حساب فعال نیست.');

        if (! ($caps['unlimited'] ?? false)) {
            $used = AiInteraction::where('user_id', $request->user()->id)
                ->whereDate('created_at', today())
                ->count();
            abort_if($used >= (int) ($caps['daily_ai_requests'] ?? 0), 429, 'سقف روزانه پردازش هوش مصنوعی شما تکمیل شده است.');
        }

        $data = $request->validate([
            'operation' => ['required', 'string', 'in:selection,word,punctuation'],
            'text' => ['required', 'string', 'max:20000'],
            'sentence' => ['nullable', 'string', 'max:4000'],
        ]);

        try {
            return response()->json(array_merge(
                ['ok' => true],
                $ai->assist($data['operation'], $data['text'], [
                    'user_id' => $request->user()->id,
                    'sentence' => $data['sentence'] ?? '',
                ])
            ));
        } catch (\Throwable $e) {
            Log::warning('farast.editor.ai_assist_failed', [
                'user_id' => $request->user()->id,
                'operation' => $data['operation'],
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'message' => 'پیشنهاد هوش مصنوعی در این لحظه در دسترس نیست.'], 502);
        }
    }
}
