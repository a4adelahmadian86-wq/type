<?php

namespace Tests\Unit;

use App\Services\AI\Providers\GeminiEditorProvider;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class GeminiEditorProviderRuntimeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_gemini_provider_reads_structured_text_from_new_steps_response(): void
    {
        Mockery::mock('alias:App\\Models\\SiteSetting')
            ->shouldReceive('read')
            ->andReturnUsing(static fn (string $key, mixed $default = null) => match ($key) {
                'gemini_api_key' => 'test-gemini-key',
                'gemini_model' => 'gemini-3.8-flash',
                default => $default,
            });

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/interactions' => Http::response([
                'id' => 'int_test_123',
                'status' => 'completed',
                'steps' => [[
                    'type' => 'model_output',
                    'content' => [[
                        'type' => 'text',
                        'text' => '{"text":"سلام دنیا"}',
                    ]],
                ]],
            ], 200),
        ]);

        $operation = [
            'name' => 'selection.rewrite',
            'result' => 'text',
        ];

        $result = (new GeminiEditorProvider())->execute($operation, [
            'text' => 'سلام',
            'context' => [],
        ], 'request-test-123');

        $this->assertSame('سلام دنیا', $result['result']['text']);
        $this->assertSame('int_test_123', $result['provider_interaction_id']);
        $this->assertSame(200, $result['http_status']);
        $this->assertNotEmpty($result['prompt_hash']);

        Http::assertSent(function ($request) {
            return $request->header('x-goog-api-key')[0] === 'test-gemini-key'
                && $request->header('X-Farast-Request-Id')[0] === 'request-test-123'
                && $request->header('Api-Revision')[0] === '2026-05-20'
                && $request['store'] === false
                && ($request['response_format']['type'] ?? null) === 'text'
                && ($request['response_format']['mime_type'] ?? null) === 'application/json';
        });
    }

    public function test_gemini_provider_maps_auth_failure_to_stable_error_code(): void
    {
        Mockery::mock('alias:App\\Models\\SiteSetting')
            ->shouldReceive('read')
            ->andReturnUsing(static fn (string $key, mixed $default = null) => match ($key) {
                'gemini_api_key' => 'test-gemini-key',
                'gemini_model' => 'gemini-3.8-flash',
                default => $default,
            });

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/interactions' => Http::response(['error' => ['message' => 'invalid key']], 401),
        ]);

        $this->expectExceptionMessage('ai_provider_auth_failed');

        (new GeminiEditorProvider())->execute([
            'name' => 'selection.rewrite',
            'result' => 'text',
        ], [
            'text' => 'سلام',
            'context' => [],
        ], 'request-test-401');
    }
}
