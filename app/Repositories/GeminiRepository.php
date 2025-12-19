<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ProviderRepositoryInterface;
use App\Traits\BuildsPrompts;
use App\Traits\HandlesResponses;
use Gemini\Client as GeminiClient;
use Gemini\Data\Content;
use Gemini\Data\FunctionCall;
use Gemini\Data\FunctionDeclaration;
use Gemini\Data\FunctionResponse;
use Gemini\Data\CodeExecution;
use Gemini\Data\GoogleSearch;
use Gemini\Data\Tool;
use Gemini\Data\Part;
use Gemini\Enums\Role;
use Gemini\Data\ImageConfig;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Responses\GenerativeModel\CountTokensResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class GeminiRepository implements ProviderRepositoryInterface
{
    use BuildsPrompts, HandlesResponses;

    private ?GeminiClient $gemini = null;

    public function __construct(
        private readonly Application $app,
    ) {}
    /**
     * Generate a response from the Gemini model.
     * @param array $data
     * @return JsonResponse|StreamedResponse
     */
    public function generate(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.gemini', 'gemini-2.5-flash');
            $prompt = $this->buildGeminiPrompt($data);

            if (! empty($data['stream'])) {
                return $this->streamGeminiResponse($model, $prompt);
            }
            $modelInstance = $this->getGeminiClient()->generativeModel($model);

            if (! empty($data['google_search'])) {
                $modelInstance = $modelInstance->withTool(new Tool(googleSearch: new GoogleSearch()));
            }

            if (! empty($data['config'])) {
                $modelInstance = $this->withConfig($modelInstance, $data['config']);
            }

            $response = $modelInstance->generateContent($prompt);
            Log::debug('Gemini: Received response.', ['response' => $response->toArray()]);

            return response()->json([
                'response' => $response->text(),
                'tokens_in' => $response->usageMetadata?->promptTokenCount ?? null,
                'tokens_out' => $response->usageMetadata?->candidatesTokenCount ?? null,
            ]);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'Gemini');
        }
    }

    /**
     * Apply configuration to the Gemini model.
     *
     * @param mixed $model
     * @param array $config
     * @return self
     */
    public function withConfig($model, array $config): self
    {
        // Gemini client does not support per-request configuration in this implementation.
        // Configuration is typically set at the model level.

        $configObject = new GenerationConfig(
            responseMimeType: new ResponseMimeType($config['response_mime_type'] ?? 'text/plain'),
            responseSchema: new Schema(
                type: new DataType($config['response_schema']['type'] ?? DataType::STRING),
            ),
            //maxTokens: $config['max_tokens'] ?? null,
            temperature: $config['temperature'] ?? null,
            topP: $config['top_p'] ?? null,
            topK: $config['top_k'] ?? null,
            presencePenalty: $config['presence_penalty'] ?? null,
            frequencyPenalty: $config['frequency_penalty'] ?? null,
        );
        return $model->withGenerationConfig($configObject);
    }

    /**
     * Handle a function call from the Gemini model.
     *
     * @param FunctionCall $functionCall
     * @return Content
     */
    function handleFunctionCall(FunctionCall $functionCall): Content
    {
        if ($functionCall->name === 'addition') {
            return new Content(
                parts: [
                    new Part(
                        functionResponse: new FunctionResponse(
                            name: 'addition',
                            response: ['answer' => $functionCall->args['number1'] + $functionCall->args['number2']],
                        ),
                        thoughtSignature: 'some-signature' // Optional: Required for some models (e.g. Gemini 3 Pro)
                    )
                ],
                role: Role::USER
            );
        }

        //Handle other function calls
        return new Content(
            parts: [
                new Part(
                    text: 'Function call not handled.'
                ),
            ],
            role: Role::MODEL
        );
    }

    /**
     * Handle code execution function call.
     *
     * @param FunctionCall $functionCall
     * @return void
     */
    function handleCodeExecution(FunctionCall $functionCall): void
    {
        if ($functionCall->name !== 'code_execution' || empty($functionCall->args['code'])) {
            Log::warning('Gemini: Invalid code execution request.', ['functionCall' => (array) $functionCall]);
            return;
        }

        $code = $functionCall->args['code'];
        Log::info('Gemini: Executing code.', ['code' => $code]);

        try {
            ob_start();
            eval($code);
            Log::info('Gemini: Code execution output.', ['output' => ob_get_clean()]);
        } catch (Throwable $e) {
            Log::error('Gemini: Code execution failed.', ['exception' => $e]);
        }
    }

    /**
     * Handle cache for Gemini responses.
     *
     * @param array $params
     * @return void
     */
    function handleCache(array $params): void
    {
        $cacheKey = 'gemini_response:' . md5(json_encode($params));
        $response = $params['response'] ?? null;
        $cacheDuration = config('ai.cache_duration', 60);

        if ($response && $cacheDuration > 0) {
            Cache::put($cacheKey, $response, now()->addMinutes($cacheDuration));
        }
    }

    /**
     * Get a list of available Gemini models.
     *
     * @return array
     */
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

    /**
     * Get a text response from the Gemini model.
     *
     * @param string $model
     * @param string $prompt
     * @return string
     */
    public function getTextResponse(string $model, string $prompt): string
    {
        $response = $this->getGeminiClient()->generativeModel($model)->generateContent($prompt);
        return $response->text();
    }

    /**
     * Stream a response from the Gemini model.
     *
     * @param string $model
     * @param array|string $prompt
     * @return StreamedResponse
     */
    private function streamGeminiResponse(string $model, array|string $prompt): StreamedResponse
    {
        Log::debug('Gemini: Starting streaming response.', ['model' => $model, 'prompt' => $prompt]);
        $gemini = $this->getGeminiClient();
        $stream = $gemini->generativeModel($model)->streamGenerateContent(...(is_array($prompt) ? $prompt : [Content::parse(part: $prompt, role: Role::USER)]));

        return $this->streamResponse($stream, fn($chunk) => $chunk->text());
    }

    /**
     * Build a Gemini prompt from the given data.
     *
     * @param array $data
     * @return string|array
     */
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

    /**
     * Get the Gemini client instance.
     *
     * @return GeminiClient
     */
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
    /**
     * Get the token count for a given prompt.
     *
     * @param array $data
     * @return int
     */
    private function getTokens(array $data): int
    {
        $model = $data['model'] ?? config('ai.models.gemini', 'gemini-2.5-flash');
        $response = Gemini::generativeModel(model: $model)->countTokens($data['prompt']);
        return $response->totalTokens;;
    }
}
