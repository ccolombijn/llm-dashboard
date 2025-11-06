<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface AIRepositoryInterface
{
    public function getAvailableProfiles(): array;
    public function isProviderConfigured(string $provider): bool;
    public function getAvailableModels(): array;
    public function generate(array $data, ?string $provider = null): JsonResponse|StreamedResponse;
    public function suggestPrompts(array $data): array;
}
