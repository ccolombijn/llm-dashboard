<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ProviderRepositoryInterface;
use App\Traits\BuildsPrompts;
use App\Traits\HandlesResponses;
use Gemini\Client as GeminiClient;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class GeminiRepository implements ProviderRepositoryInterface
{
    use BuildsPrompts, HandlesResponses;

    private ?GeminiClient $gemini = null;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function generate(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.gemini', 'gemini-1.5-flash');
            $prompt = $this->buildGeminiPrompt($data);

            if (! empty($data['stream'])) {
                return $this->streamGeminiResponse($model, $prompt);
            }

            $response = $this->getGeminiClient()->generativeModel($model)->generateContent($prompt);

            return response()->json([
                'response' => $response->text(),
                'tokens_in' => $response->usageMetadata?->promptTokenCount ?? null,
                'tokens_out' => $response->usageMetadata?->candidatesTokenCount ?? null,
            ]);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'Gemini');
        }
    }

    public function getAvailableModels(): array
    {
        try {
            $response = $this->getGeminiClient()->models()->list();

            return collect($response->models)
                ->map(fn($model) => ['id' => $model->name, 'display_name' => $model->displayName])
                ->sortBy('id')->values()->all();
        } catch (Throwable $e) {
            //Log::error('Failed to retrieve models from Gemini.', ['exception' => $e]);
            return [];
        }
    }

    public function getTextResponse(string $model, string $prompt): string
    {
        $response = $this->getGeminiClient()->generativeModel($model)->generateContent($prompt);
        return $response->text();
    }

    private function streamGeminiResponse(string $model, array|string $prompt): StreamedResponse
    {
        Log::debug('Gemini: Starting streaming response.', ['model' => $model, 'prompt' => $prompt]);
        $gemini = $this->getGeminiClient();
        $stream = $gemini->generativeModel($model)->streamGenerateContent(...(is_array($prompt) ? $prompt : [Content::parse(part: $prompt, role: Role::USER)]));

        return $this->streamResponse($stream, fn($chunk) => $chunk->text());
    }

    private function buildGeminiPrompt(array $data): string|array
    {
        $basePromptParts = $this->buildBasePromptParts($data);

        if (isset($data['history'])) {
            $history = [];

            if (!empty($basePromptParts['system_prompt'])) {
                $history[] = Content::parse(part: $basePromptParts['system_prompt'], role: Role::USER);
                $history[] = Content::parse(part: 'Ok, begrepen.', role: Role::MODEL);
            }
            if (!empty($basePromptParts['file_context'])) {
                $history[] = Content::parse(part: $basePromptParts['file_context'], role: Role::USER);
                $history[] = Content::parse(part: 'Ok, ik heb de bestanden gelezen.', role: Role::MODEL);
            }

            foreach ($data['history'] as $item) {
                $role = ($item['role'] ?? 'user') === 'model' ? Role::MODEL : Role::USER;
                $partText = (string) ($item['text'] ?? '');
                $history[] = Content::parse(part: $partText, role: $role);
            }

            return [...$history, Content::parse(part: $basePromptParts['prompt'], role: Role::USER)];
        }

        return trim(implode("\n\n", array_filter($basePromptParts)));
    }

    private function getGeminiClient(): GeminiClient
    {
        if (!$this->gemini) {
            if (empty(config('gemini.api_key'))) {
                throw new \RuntimeException('Gemini API key is not configured.');
            }
            $this->gemini = $this->app->make(GeminiClient::class);
        }
        return $this->gemini;
    }
}
