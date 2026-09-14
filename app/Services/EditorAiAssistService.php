<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class EditorAiAssistService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    public function assist(string $operation, string $text, array $context = []): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('متن برای بررسی خالی است.');
        }

        $key = SiteSetting::read('gemini_api_key') ?: config('services.gemini.key');
        $model = SiteSetting::read('gemini_model') ?: config('services.gemini.model', 'gemini-3.8-flash');
        if (! $key) {
            throw new RuntimeException('کلید Gemini تنظیم نشده است.');
        }

        $prompt = $this->prompt($operation, $text, $context);
        $requestId = (string) Str::uuid();
        $interaction = AiInteraction::create([
            'user_id' => $context['user_id'] ?? null,
            'provider' => 'gemini',
            'model' => $model,
            'operation' => 'editor_'.$operation,
            'request_id' => $requestId,
            'prompt_hash' => hash('sha256', $prompt),
            'input_bytes' => strlen($text),
            'status' => 'started',
            'input_meta' => ['operation' => $operation],
        ]);

        $started = hrtime(true);
        try {
            $response = Http::timeout(45)
                ->retry(2, 500)
                ->withHeaders([
                    'x-goog-api-key' => $key,
                    'Content-Type' => 'application/json',
                    'X-Farast-Request-Id' => $requestId,
                ])
                ->post(self::ENDPOINT, [
                    'model' => $model,
                    'input' => [['type' => 'text', 'text' => $prompt]],
                    'store' => false,
                    'system_instruction' => 'Return only valid JSON. Never alter the original text unless the selected action explicitly requests a correction.',
                    'response_format' => [
                        'type' => 'text',
                        'mime_type' => 'application/json',
                        'schema' => $this->schema($operation),
                    ],
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Gemini HTTP '.$response->status().' '.$response->body());
            }

            $raw = collect($response->json('outputs', []))
                ->filter(fn ($output) => ($output['type'] ?? null) === 'text')
                ->pluck('text')
                ->implode('');
            if ($raw === '') {
                $raw = collect($response->json('steps', []))
                    ->flatMap(fn ($step) => $step['content'] ?? [])
                    ->filter(fn ($content) => ($content['type'] ?? null) === 'text')
                    ->pluck('text')
                    ->implode('');
            }
            $json = json_decode(trim($raw), true);
            if (! is_array($json)) {
                throw new RuntimeException('پاسخ JSON هوش مصنوعی معتبر نیست.');
            }

            $interaction->update([
                'provider_interaction_id' => $response->json('id'),
                'latency_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'output_bytes' => strlen($raw),
                'status' => 'completed',
                'output_meta' => ['operation' => $operation],
            ]);

            $json['interaction_id'] = $interaction->id;
            $json['request_id'] = $requestId;
            return $json;
        } catch (\Throwable $e) {
            $interaction->update([
                'latency_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
            ]);
            throw $e;
        }
    }

    private function prompt(string $operation, string $text, array $context): string
    {
        return match ($operation) {
            'selection' => "تو دستیار رابط ویرایشگر فراست هستی. متن انتخاب‌شده را فقط از نظر الگوی رایج سند حرفه‌ای تحلیل کن. اگر متن احتمالاً عنوان، زیرعنوان، پاراگراف عادی، نقل‌قول یا فهرست است، حداکثر سه پیشنهاد متداول بده. پیشنهادها باید کم‌ریسک باشند و متن را بازنویسی نکنند. متن انتخاب‌شده:\n\n{$text}\n\nپاسخ فقط JSON با فیلدهای kind و suggestions بده. هر suggestion شامل label, action, reason باشد. action فقط یکی از style_title, style_heading1, style_heading2, style_subtitle, style_normal, bold, color باشد.",
            'word' => "تو غلط‌یاب فارسی فراست هستی. واژه زیر را در بافت جمله بررسی کن. اگر غلط املایی یا واژه‌ای بسیار مشکوک است، is_suspicious=true و حداکثر پنج پیشنهاد نزدیک بده. اگر درست یا نام خاص/اصطلاح معتبر است، is_suspicious=false بده. هرگز واژه را فقط به دلیل ناآشنا بودن غلط اعلام نکن. واژه: {$text}\nبافت: ".($context['sentence'] ?? '')."\nپاسخ فقط JSON با is_suspicious و suggestions و reason.",
            'punctuation' => "متن فارسی زیر را فقط از نظر علائم نگارشی بررسی کن. هیچ واژه، ترتیب، جمله یا محتوایی را تغییر نده. فقط علائم نگارشی متداول را در جای لازم اضافه یا اصلاح کن. خروجی باید متن کامل اصلاح‌شده باشد و هیچ توضیحی نداشته باشد. متن:\n\n{$text}",
            default => throw new RuntimeException('عملیات هوش مصنوعی ناشناخته است.'),
        };
    }

    private function schema(string $operation): array
    {
        if ($operation === 'selection') {
            return ['type' => 'object', 'properties' => ['kind' => ['type' => 'string'], 'suggestions' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['label' => ['type' => 'string'], 'action' => ['type' => 'string'], 'reason' => ['type' => 'string']], 'required' => ['label', 'action', 'reason']]]], 'required' => ['kind', 'suggestions']];
        }
        if ($operation === 'word') {
            return ['type' => 'object', 'properties' => ['is_suspicious' => ['type' => 'boolean'], 'suggestions' => ['type' => 'array', 'items' => ['type' => 'string']], 'reason' => ['type' => 'string']], 'required' => ['is_suspicious', 'suggestions', 'reason']];
        }
        return ['type' => 'object', 'properties' => ['text' => ['type' => 'string']], 'required' => ['text']];
    }
}
