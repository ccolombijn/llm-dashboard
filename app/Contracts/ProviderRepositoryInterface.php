<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface ProviderRepositoryInterface
{
    public function generate(array $data): JsonResponse|StreamedResponse;

    public function getAvailableModels(): array;

    public function getTextResponse(string $model, string $prompt): string;
}
