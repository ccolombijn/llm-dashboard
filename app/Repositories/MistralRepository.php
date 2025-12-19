<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ProviderRepositoryInterface;
use App\Traits\BuildsPrompts;
use App\Traits\HandlesResponses;
use HelgeSverre\Mistral\Mistral as MistralClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class MistralRepository implements ProviderRepositoryInterface
{
    use BuildsPrompts, HandlesResponses;

    private ?MistralClient $mistral = null;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function generate(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.mistral', 'mistral-large-latest');
            $messages = $this->buildMistralMessages($data);

            if (! empty($data['stream'])) {
                return $this->streamMistralResponse($model, $messages);
            }

            /** @var \HelgeSverre\Mistral\Responses\Chat\CreateResponse $response */
            $response = $this->getMistralClient()->chat()->create(['model' => $model, 'messages' => $messages]);

            return response()->json([
                'response' => $response->choices[0]->message->content,
                'tokens_in' => $response->usage->promptTokens,
                'tokens_out' => $response->usage->completionTokens,
            ]);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'Mistral');
        }
    }

    public function getAvailableModels(): array
    {
        try {
            $response = $this->getMistralClient()->models()->list();
            $modelListDto = $response->dtoOrFail();

            return collect($modelListDto->data)->sortBy('id')->values()->all();
        } catch (Throwable $e) {
            //Log::error('Failed to retrieve models from Mistral.', ['exception' => $e]);
            return [];
        }
    }

    public function getTextResponse(string $model, string $prompt): string
    {
        /** @var \HelgeSverre\Mistral\Responses\Chat\CreateResponse $response */
        $response = $this->getMistralClient()->chat()->create(['model' => $model, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        return $response->choices[0]->message->content;
    }

    private function streamMistralResponse(string $model, array $messages): StreamedResponse
    {
        $stream = $this->getMistralClient()->chat()->createStreamed([
            'model' => $model,
            'messages' => $messages,
        ]);

        return $this->streamResponse($stream, fn($chunk) => $chunk->choices[0]->delta->content ?? null);
    }

    private function buildMistralMessages(array $data): array
    {
        return $this->buildOpenAIMessages($data); // Mistral uses the same message format as OpenAI
    }

    private function getMistralClient(): MistralClient
    {
        if (!$this->mistral) {
            if (empty(config('mistral.api_key'))) {
                throw new \RuntimeException('Mistral API key is not configured.');
            }
            $this->mistral = $this->app->make(MistralClient::class);
        }
        return $this->mistral;
    }

    private function getTokens(array $data): int
    {
        //$model = $data['model'] ?? config('ai.models.mistral', 'mistral-large-latest');
        //return TokenizerX::count($data['prompt'], $model);
        // Calculate the number of words in the prompt
        // str_word_count is a standard way to count words in English text
        $wordCount = str_word_count($data['prompt']);

        // Apply the conversion factor (1000 / 750 = 1.3333...)
        $estimatedTokens = $wordCount * (1000 / 750);

        // Return the result rounded up to the nearest integer
        return (int) ceil($estimatedTokens);
    }
}
