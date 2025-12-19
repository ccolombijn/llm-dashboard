<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ProviderRepositoryInterface;
use App\Traits\BuildsPrompts;
use App\Traits\HandlesResponses;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use OpenAI\Client as OpenAIClient;
use Rajentrivedi\TokenizerX\TokenizerX;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class OpenAIRepository implements ProviderRepositoryInterface
{
    use BuildsPrompts, HandlesResponses;

    private ?OpenAIClient $openai = null;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function generate(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.openai', 'gpt-4-turbo');
            $messages = $this->buildOpenAIMessages($data);

            if (!empty($data['stream'])) {
                return $this->streamOpenAIResponse($model, $messages);
            }

            return $this->generateResponse($model, $messages);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'OpenAI');
        }
    }

    public function getAvailableModels(): array
    {
        try {
            $response = $this->getOpenAIClient()->models()->list();

            return collect($response->data)
                ->map(fn($model) => ['id' => $model->id, 'owned_by' => $model->ownedBy])
                ->sortBy('id')->values()->all();
        } catch (Throwable $e) {
            //Log::error('Failed to retrieve models from OpenAI.', ['exception' => $e]);
            return [];
        }
    }

    public function getTextResponse(string $model, string $prompt): string
    {
        $response = $this->getOpenAIClient()->chat()->create([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);
        return $response->choices[0]->message->content;
    }
    private function generateResponse(string $model, array $messages): JsonResponse
    {
        $response = $this->getOpenAIClient()->chat()->create([
            'model' => $model,
            'messages' => $messages,
        ]);

        return response()->json([
            'response' => $response->choices[0]->message->content,
            'tokens_in' => $response->usage->promptTokens,
            'tokens_out' => $response->usage->completionTokens,
        ]);
    }

    private function streamOpenAIResponse(string $model, array $messages): StreamedResponse
    {
        $stream = $this->getOpenAIClient()->chat()->createStreamed([
            'model' => $model,
            'messages' => $messages,
        ]);

        return $this->streamResponse($stream, fn($chunk) => $chunk->choices[0]->delta->content ?? null);
    }

    private function buildOpenAIMessages(array $data): array
    {
        $messages = [];

        if (!empty($data['history'])) {
            foreach ($data['history'] as $item) {
                $role = $item['role'] ?? 'user';
                if (in_array($role, ['user', 'assistant', 'system'])) {
                    $messages[] = ['role' => $role, 'content' => (string) ($item['text'] ?? '')];
                }
            }
        }

        $prompt = $this->buildBasePrompt($data);

        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $messages;
    }

    private function getOpenAIClient(): OpenAIClient
    {
        if (!$this->openai) {
            if (empty(config('openai.api_key'))) {
                throw new \RuntimeException('OpenAI API key is not configured.');
            }
            $this->openai = $this->app->make(OpenAIClient::class);
        }

        return $this->openai;
    }
    /**
     * Calculate the number of tokens in the given data.
     */
    private function getTokens(array $data): int
    {
        $model = $data['model'] ?? config('ai.models.openai', 'gpt-4-turbo');
        return TokenizerX::count($data['prompt'], $model);
    }
}
