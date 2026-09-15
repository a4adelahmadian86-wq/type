<?php

namespace App\Services\AI;

use App\Services\AI\Providers\GeminiEditorProvider;
use App\Services\AI\Providers\GeminiProviderAdapter;
use InvalidArgumentException;

final class AiProviderRegistry
{
    /** @var array<string, AiProviderInterface> */
    private array $providers = [];

    public function __construct(GeminiEditorProvider $gemini)
    {
        $this->register(new GeminiProviderAdapter($gemini));
    }

    public function register(AiProviderInterface $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function get(string $name): AiProviderInterface
    {
        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException('ai_provider_not_registered');
        }

        return $this->providers[$name];
    }

    /** @return array<string, array<string, mixed>> */
    public function capabilities(): array
    {
        $result = [];
        foreach ($this->providers as $name => $provider) {
            $result[$name] = $provider->capabilities();
        }

        return $result;
    }
}
