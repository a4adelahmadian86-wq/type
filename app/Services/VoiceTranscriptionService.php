<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VoiceTranscriptionService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    public function transcribe(string $mime, string $bytes, string $locale, array $context = []): array
    {
        $key = SiteSetting::read('gemini_api_key') ?: config('services.gemini.key');
        $model = SiteSetting::read('gemini_model') ?: config('services.gemini.model', 'gemini-3.8-flash');
        abort_unless($key, 503, 'سرویس هوش مصنوعی تنظیم نشده است.');

        $language = match ($locale) {
            'en-US' => 'English (United States)',
            'ar-SA' => 'Arabic (Saudi Arabia)',
            default => 'Persian (Iran)',
        };

        $prompt = <<<PROMPT
You are FARAST's professional speech-to-text transcription engine.
Transcribe only the spoken words in the supplied audio in {$language}.
Do not summarize, rewrite, translate, explain, complete unfinished thoughts, or invent words.
Preserve the speaker's language. Keep numbers and named entities as faithfully as possible.
Use paragraphs only when there is a clearly meaningful pause. Do not add headings or Markdown.
Return plain text only, with no labels, quotation marks, commentary, JSON, or code fences.
PROMPT;

        $requestId = (string) Str::uuid();
        $interaction = AiInteraction::create([
            'user_id' => $context['user_id'] ?? null,
            'provider' => 'gemini',
            'model' => $model,
            'operation' => 'voice_transcription',
            'request_id' => $requestId,
            'prompt_hash' => hash('sha256', $prompt),
            'input_bytes' => (int) ($context['input_bytes'] ?? strlen($bytes)),
            'status' => 'started',
            'input_meta' => ['mime' => $mime, 'locale' => $locale],
        ]);

        $started = hrtime(true);

        try {
            $response = Http::timeout(120)
                ->retry(2, 650)
                ->withHeaders([
                    'x-goog-api-key' => $key,
                    'Content-Type' => 'application/json',
                    'X-Farast-Request-Id' => $requestId,
                ])
                ->post(self::ENDPOINT, [
                    'model' => $model,
                    'input' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'audio', 'data' => base64_encode($bytes), 'mime_type' => $mime],
                    ],
                    'store' => false,
                    'system_instruction' => 'Faithful transcription only. Never invent content.',
                ]);

            $latency = (int) ((hrtime(true) - $started) / 1_000_000);
            if (! $response->successful()) {
                throw new \RuntimeException('Gemini voice HTTP '.$response->status().' '.$response->body());
            }

            $text = collect($response->json('outputs', []))
                ->filter(fn ($output) => ($output['type'] ?? null) === 'text')
                ->pluck('text')
                ->implode('');

            if ($text === '') {
                $text = collect($response->json('steps', []))
                    ->flatMap(fn ($step) => $step['content'] ?? [])
                    ->filter(fn ($content) => ($content['type'] ?? null) === 'text')
                    ->pluck('text')
                    ->implode('');
            }

            $text = trim($text);
            if ($text === '') {
                throw new \RuntimeException('Gemini returned an empty voice transcript.');
            }

            $interaction->update([
                'provider_interaction_id' => $response->json('id'),
                'latency_ms' => $latency,
                'output_bytes' => strlen($text),
                'status' => 'completed',
                'output_meta' => ['http_status' => $response->status(), 'locale' => $locale],
            ]);

            Log::info('farast.voice.completed', [
                'request_id' => $requestId,
                'interaction_id' => $interaction->id,
                'latency_ms' => $latency,
            ]);

            return [
                'text' => $text,
                'interaction_id' => $interaction->id,
                'request_id' => $requestId,
            ];
        } catch (\Throwable $e) {
            $interaction->update([
                'latency_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
            ]);
            throw $e;
        }
    }
}
