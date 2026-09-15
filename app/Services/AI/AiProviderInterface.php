<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    public function name(): string;

    /** @return array<string, mixed> */
    public function capabilities(): array;

    /** @param array<string, mixed> $request */
    public function execute(array $request): AiProviderResult;
}
