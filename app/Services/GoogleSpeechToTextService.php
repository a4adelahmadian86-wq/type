<?php

namespace App\Services;

use App\Models\AiInteraction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleSpeechToTextService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const RECOGNIZE_URL = 'https://speech.googleapis.com/v1/speech:recognize';
    private const SCOPE = 'https://www.googleapis.com/auth/cloud-platform';

    public function enabled(): bool
    {
        $path = trim((string) config('services.google_speech.credentials_file'));
        return (bool) config('services.google_speech.enabled') && $path !== '' && is_file($path);
    }

    public function transcribe(string $mime, string $bytes, string $locale, array $context = []): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Google Cloud Speech-to-Text is not configured.');
        }

        [$encoding, $sampleRate] = $this->recognitionFormat($mime);
        $requestId = (string) Str::uuid();
        $interaction = AiInteraction::create([
            'user_id' => $context['user_id'] ?? null,
            'provider' => 'google-cloud-speech',
            'model' => 'v1-default',
            'operation' => 'voice_transcription',
            'request_id' => $requestId,
            'prompt_hash' => hash('sha256', 'google-cloud-speech-v1-fa-en-ar-faithful-transcription'),
            'input_bytes' => (int) ($context['input_bytes'] ?? strlen($bytes)),
            'status' => 'started',
            'input_meta' => ['mime' => $mime, 'locale' => $locale, 'encoding' => $encoding],
        ]);

        $config = [
            'languageCode' => $locale,
            'enableAutomaticPunctuation' => true,
            'enableWordConfidence' => false,
            'profanityFilter' => false,
        ];
        if ($encoding !== null) $config['encoding'] = $encoding;
        if ($sampleRate !== null) $config['sampleRateHertz'] = $sampleRate;

        $started = hrtime(true);
        try {
            $response = Http::timeout(65)
                ->withToken($this->accessToken())
                ->acceptJson()
                ->asJson()
                ->post(self::RECOGNIZE_URL, [
                    'config' => $config,
                    'audio' => ['content' => base64_encode($bytes)],
                ]);

            $latency = (int) ((hrtime(true) - $started) / 1_000_000);
            if (! $response->successful()) {
                throw new RuntimeException('Google Speech HTTP '.$response->status().' '.$response->body());
            }

            $parts = [];
            foreach ((array) $response->json('results', []) as $result) {
                $transcript = trim((string) data_get($result, 'alternatives.0.transcript', ''));
                if ($transcript !== '') $parts[] = $transcript;
            }
            $text = trim(implode("\n", $parts));
            if ($text === '') throw new RuntimeException('Google Speech returned an empty transcript.');

            $interaction->update([
                'latency_ms' => $latency,
                'output_bytes' => strlen($text),
                'status' => 'completed',
                'output_meta' => ['http_status' => $response->status(), 'locale' => $locale, 'engine' => 'google-cloud-speech-v1'],
            ]);

            Log::info('farast.voice.google.completed', ['request_id' => $requestId, 'interaction_id' => $interaction->id, 'latency_ms' => $latency]);

            return ['text' => $text, 'interaction_id' => $interaction->id, 'request_id' => $requestId, 'engine' => 'google-cloud-speech-v1'];
        } catch (\Throwable $e) {
            $interaction->update([
                'latency_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
            ]);
            throw $e;
        }
    }

    private function recognitionFormat(string $mime): array
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));
        return match ($mime) {
            'audio/webm' => ['WEBM_OPUS', 48000],
            'audio/ogg', 'application/ogg' => ['OGG_OPUS', 48000],
            'audio/wav', 'audio/x-wav' => [null, null],
            default => throw new RuntimeException('This audio format is not supported by Google Speech V1 in FARAST.'),
        };
    }

    private function accessToken(): string
    {
        $credentials = $this->credentials();
        $cacheKey = 'farast.google-speech.token.'.hash('sha256', (string) ($credentials['client_email'] ?? ''));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials) {
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_UNESCAPED_SLASHES));
            $unsigned = $header.'.'.$claims;
            $ok = openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
            if (! $ok) throw new RuntimeException('Could not sign Google service account JWT.');
            $assertion = $unsigned.'.'.$this->base64Url($signature);

            $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
            if (! $response->successful()) throw new RuntimeException('Google OAuth HTTP '.$response->status().' '.$response->body());
            $token = (string) $response->json('access_token');
            if ($token === '') throw new RuntimeException('Google OAuth did not return an access token.');
            return $token;
        });
    }

    private function credentials(): array
    {
        $path = trim((string) config('services.google_speech.credentials_file'));
        if ($path === '' || ! is_file($path)) throw new RuntimeException('Google Speech credentials file does not exist.');
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new RuntimeException('Google service account credentials are invalid.');
        }
        return $json;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
