<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Services\CapabilityService;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoiceController extends Controller
{
    public function transcribe(Request $request, GeminiService $gemini, CapabilityService $capabilities)
    {
        $caps = $capabilities->forUser($request->user());
        abort_unless(($caps['active'] ?? false) && ($caps['can_voice'] ?? false), 403, 'تایپ صوتی برای این حساب فعال نیست.');

        if (! ($caps['unlimited'] ?? false)) {
            $used = AiInteraction::where('user_id', $request->user()->id)
                ->whereDate('created_at', today())
                ->count();
            abort_if($used >= (int) ($caps['daily_ai_requests'] ?? 0), 429, 'سقف روزانه پردازش هوش مصنوعی شما تکمیل شده است.');
        }

        $data = $request->validate([
            'audio' => ['required', 'file', 'max:15360', 'mimetypes:audio/webm,video/webm,audio/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,video/mp4'],
            'locale' => ['required', 'string', 'in:fa-IR,en-US,ar-SA'],
        ]);

        $file = $data['audio'];
        $bytes = file_get_contents($file->getRealPath());
        abort_if($bytes === false || $bytes === '', 422, 'فایل صوتی قابل خواندن نیست.');

        $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: 'audio/webm';

        try {
            $result = $gemini->transcribeVoice($mime, $bytes, $data['locale'], [
                'user_id' => $request->user()->id,
                'input_bytes' => strlen($bytes),
            ]);
        } catch (\Throwable $e) {
            Log::warning('farast.voice.transcription_failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'رونویسی صوتی انجام نشد. دوباره تلاش کنید.',
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'text' => $result['text'],
            'interaction_id' => $result['interaction_id'] ?? null,
            'request_id' => $result['request_id'] ?? null,
        ]);
    }
}
