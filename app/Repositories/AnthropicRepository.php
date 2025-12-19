<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ProviderRepositoryInterface;
use App\Traits\BuildsPrompts;
use App\Traits\HandlesResponses;
use Anthropic\Client as AnthropicClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AnthropicRepository implements ProviderRepositoryInterface
{
    use BuildsPrompts, HandlesResponses;

    private ?AnthropicClient $anthropic = null;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function generate(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.anthropic', 'claude-3-haiku-20240307');
            $messages = $this->buildAnthropicMessages($data);
            $systemPrompt = $this->buildBasePromptParts($data)['system_prompt'];

            if (! empty($data['stream'])) {
                return $this->streamAnthropicResponse($model, $messages, $systemPrompt);
            }

            $response = $this->getAnthropicClient()->messages()->create([
                'model' => $model,
                'system' => $systemPrompt,
                'messages' => $messages,
                'max_tokens' => 4096,
            ]);

            return response()->json([
                'response' => $response->content[0]->text,
                'tokens_in' => $response->usage->inputTokens,
                'tokens_out' => $response->usage->outputTokens,
            ]);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'Anthropic');
        }
    }

    public function getAvailableModels(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => config('services.anthropic.version', '2023-06-01'),
            ])->get('https://api.anthropic.com/v1/models');

            $response->throw();

            return $response->json('data', []);
        } catch (Throwable $e) {
            //Log::error('Failed to retrieve models from Anthropic.', ['exception' => $e]);
            return [];
        }
    }

    public function getTextResponse(string $model, string $prompt): string
    {
        $response = $this->getAnthropicClient()->messages()->create([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 1024,
        ]);
        return $response->content[0]->text;
    }

    private function streamAnthropicResponse(string $model, array $messages, string $systemPrompt): StreamedResponse
    {
        $stream = $this->getAnthropicClient()->messages()->createStreamed([
            'model' => $model,
            'system' => $systemPrompt,
            'messages' => $messages,
            'max_tokens' => 4096,
        ]);

        return $this->streamResponse($stream, fn($chunk) => $chunk->type === 'content_block_delta' ? $chunk->delta->text : null);
    }

    private function buildAnthropicMessages(array $data): array
    {
        $allMessages = [];
        if (! empty($data['history'])) {
            foreach ($data['history'] as $item) {
                $role = $item['role'] ?? 'user';
                if (in_array($role, ['user', 'assistant'])) {
                    $allMessages[] = ['role' => $role, 'content' => (string) ($item['text'] ?? '')];
                }
            }
        }
        $baseParts = $this->buildBasePromptParts($data);
        $prompt = trim(implode("\n\n", array_filter([$baseParts['file_context'], $baseParts['prompt']])));
        $allMessages[] = ['role' => 'user', 'content' => $prompt];
        return $this->mergeConsecutiveMessages($allMessages);
    }

    private function getAnthropicClient(): AnthropicClient
    {
        if (!$this->anthropic) {
            if (empty(config('services.anthropic.api_key'))) {
                throw new \RuntimeException('Anthropic API key is not configured.');
            }
            $this->anthropic = $this->app->make(AnthropicClient::class);
        }
        return $this->anthropic;
    }

    private function mergeConsecutiveMessages(array $messages): array
    {
        if (empty($messages)) {
            return [];
        }
        $mergedMessages = [];
        $lastMessage = array_shift($messages);
        foreach ($messages as $currentMessage) {
            if ($currentMessage['role'] === $lastMessage['role']) {
                $lastMessage['content'] .= "\n\n" . $currentMessage['content'];
            } else {
                $mergedMessages[] = $lastMessage;
                $lastMessage = $currentMessage;
            }
        }
        $mergedMessages[] = $lastMessage;
        return $mergedMessages;
    }
    /**
     * Calculate the number of tokens in the given data.
     */

    private function getTokens(array $data): int
    {
        $wordCount = str_word_count($data['prompt']);
        $estimatedTokens = $wordCount * (1000 / 750);
        return (int) ceil($estimatedTokens);
    }
}
