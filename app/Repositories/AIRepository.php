<?php

declare(strict_types=1);

namespace App\Repositories;

use Anthropic\Client as AnthropicClient;
use App\Services\PomlService;
use App\Contracts\AIRepositoryInterface;
use Illuminate\Contracts\Foundation\Application;
use App\Traits\FilePathResolver;
use Gemini\Client as GeminiClient;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use HelgeSverre\Mistral\Mistral as MistralClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use OpenAI\Client as OpenAIClient;
use Spatie\PdfToText\Exceptions\BinaryNotFoundException;
use Spatie\PdfToText\Pdf;
use Sbsaga\Toon\Facades\Toon;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AIRepository implements AIRepositoryInterface
{
    use FilePathResolver;
    private ?OpenAIClient $openai = null;
    private ?GeminiClient $gemini = null;
    private ?AnthropicClient $anthropic = null;
    private ?MistralClient $mistral = null;

    public function __construct(
        private readonly Application $app
    ) {}

    /**
     * Get available AI profile names.
     */
    public function getAvailableProfiles(): array
    {
        $storage = Storage::disk('public');
        $path = 'json/profiles';

        if (! $storage->exists($path)) {
            Log::warning("AI profiles directory not found: {$path}");

            return [];
        }

        $files = $storage->files($path);

        return collect($files)
            ->filter(fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'json')
            ->map(fn($file) => pathinfo($file, PATHINFO_FILENAME))
            ->values()
            ->all();
    }

    /**
     * Check if a specific AI provider is configured based on the presence of its API key.
     */
    public function isProviderConfigured(string $provider): bool
    {
        return match ($provider) {
            'openai' => !empty(config('openai.api_key')), // The key is directly in the openai config
            'gemini' => !empty(config('gemini.api_key')), // The key is directly in the gemini config
            'anthropic' => !empty(config('services.anthropic.api_key')),
            'mistral' => !empty(config('mistral.api_key')),
            default => false,
        };
    }

    /**
     * Get all available models from all configured providers.
     */
    public function getAvailableModels(): array
    {
        $allModels = [];

        // OpenAI
        try {
            $response = $this->getOpenAIClient()->models()->list();
            $allModels['openai'] = collect($response->data)
                ->map(fn($model) => ['id' => $model->id, 'owned_by' => $model->ownedBy])
                ->sortBy('id')->values()->all();
        } catch (Throwable $e) {
            Log::error('Failed to retrieve models from OpenAI.', ['exception' => $e]);
            $allModels['openai'] = [];
        }

        // Gemini
        try {
            $response = $this->getGeminiClient()->models()->list();
            $allModels['gemini'] = collect($response->models)
                ->map(fn($model) => ['id' => $model->name, 'display_name' => $model->displayName])
                ->sortBy('id')->values()->all();
        } catch (Throwable $e) {
            Log::error('Failed to retrieve models from Gemini.', ['exception' => $e]);
            $allModels['gemini'] = [];
        }

        // Mistral
        try {
            $response = $this->getMistralClient()->models()->list();
            $modelListDto = $response->dtoOrFail();
            $allModels['mistral'] = collect($modelListDto->data)->sortBy('id')->values()->all();
        } catch (Throwable $e) {
            Log::error('Failed to retrieve models from Mistral.', ['exception' => $e]);
            $allModels['mistral'] = [];
        }

        // Anthropic
        try {
            // The official SDK does not seem to have a model list method.
            // We will use a direct API call as the primary method.
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => config('services.anthropic.version', '2023-06-01'),
            ])->get('https://api.anthropic.com/v1/models');

            $response->throw(); // Throw an exception for 4xx/5xx responses

            $allModels['anthropic'] = $response->json('data', []);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve models from Anthropic.', ['exception' => $e]);
            $allModels['anthropic'] = [];
        }

        return $allModels;
    }
    /**
     * Generate AI response based on the specified provider.
     */
    public function generate(array $data, ?string $provider = null): JsonResponse|StreamedResponse
    {
        $task = array_key_exists($data['prompt'], config('ai.prompts', [])) ? $data['prompt'] : 'chat';
        $taskConfig = $this->getTaskConfig($task);

        $provider = $taskConfig['provider'];
        // If a model is specified in the task config, inject it into the data array.
        if (!isset($data['model']) && $taskConfig['model']) {
            $data['model'] = $taskConfig['model'];
        }

        return match ($provider) {
            'openai' => $this->generateWithOpenAI($data),
            'gemini' => $this->generateWithGemini($data),
            'anthropic' => $this->generateWithAnthropic($data),
            'mistral' => $this->generateWithMistral($data),
            default => throw new InvalidArgumentException("Unsupported AI provider: [{$provider}]")
        };
    }

    /**
     * Handles generation using the OpenAI client.
     */
    private function generateWithOpenAI(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.openai', 'gpt-5-nano');
            $messages = $this->buildOpenAIMessages($data);

            if (! empty($data['stream'])) {
                return $this->streamOpenAIResponse($model, $messages);
            }

            return $this->generateResponse('openai', $model, $messages);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'OpenAI');
        }
    }
    /**
     * Build OpenAI messages with given data.
     */
    private function buildOpenAIMessages(array $data): array
    {
        $messages = [];

        if (! empty($data['history'])) {
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
    /**
     * Handles streaming generation using the OpenAI client.
     */
    private function streamOpenAIResponse(string $model, array $messages): StreamedResponse
    {
        $stream = $this->getOpenAIClient()->chat()->createStreamed([
            'model' => $model,
            'messages' => $messages,
        ]);

        return $this->streamResponse($stream, fn($chunk) => $chunk->choices[0]->delta->content ?? null);
    }

    /**
     * Handles generation using the Gemini client.
     */
    private function generateWithGemini(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.gemini', 'gemini-2.5-flash');
            $prompt = $this->buildGeminiPrompt($data);

            if (! empty($data['stream'])) {
                return $this->streamGeminiResponse($model, $prompt);
            }
            // Non-streaming response
            return $this->generateResponse('gemini', $model, $prompt);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'Gemini');
        }
    }
    /**
     * Handles streaming generation using the Gemini client.
     */
    private function streamGeminiResponse(string $model, $prompt): StreamedResponse
    {
        Log::debug('Gemini: Starting streaming response.', ['model' => $model, 'prompt' => $prompt]);
        $gemini = $this->getGeminiClient();
        // The prompt is always an array of Content parts
        $stream = $gemini->generativeModel($model)->streamGenerateContent(...$prompt);

        return $this->streamResponse($stream, fn($chunk) => $chunk->text());
    }

    /**
     * Build prompt with given data for Gemini.
     */
    private function buildGeminiPrompt(array $data): string|array
    {
        $basePromptParts = $this->buildBasePromptParts($data);

        if (isset($data['history'])) {
            $history = [];

            // Add system prompt and file context as initial user messages if not already in history
            if (!empty($basePromptParts['system_prompt'])) {
                $history[] = Content::parse(part: $basePromptParts['system_prompt'], role: Role::USER);
                $history[] = Content::parse(part: 'Ok, begrepen.', role: Role::MODEL); // Acknowledge the system prompt
            }
            if (!empty($basePromptParts['file_context'])) {
                $history[] = Content::parse(part: $basePromptParts['file_context'], role: Role::USER);
                $history[] = Content::parse(part: 'Ok, ik heb de bestanden gelezen.', role: Role::MODEL); // Acknowledge the file context
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
     * Handles generation using the Anthropic client.
     */
    private function generateWithAnthropic(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.anthropic', 'claude-3-haiku-20240307');
            $messages = $this->buildAnthropicMessages($data);
            $systemPrompt = $this->buildBasePromptParts($data)['system_prompt'];

            if (! empty($data['stream'])) {
                return $this->streamAnthropicResponse($model, $messages, $systemPrompt);
            }

            return $this->generateResponse('anthropic', $model, $messages, $systemPrompt);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'Anthropic');
        }
    }

    /**
     * Handles streaming generation using the Anthropic client.
     */
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

        if (empty($allMessages)) {
            return [];
        }

        // Merge consecutive messages from the same role to comply with Anthropic's format.
        $messages = [];
        $lastMessage = array_shift($allMessages);

        foreach ($allMessages as $currentMessage) {
            if ($currentMessage['role'] === $lastMessage['role']) {
                $lastMessage['content'] .= "\n\n" . $currentMessage['content'];
            } else {
                $messages[] = $lastMessage;
                $lastMessage = $currentMessage;
            }
        }
        $messages[] = $lastMessage;

        return $messages;
    }

    /**
     * Handles generation using the Mistral client.
     */
    private function generateWithMistral(array $data): JsonResponse|StreamedResponse
    {
        try {
            $model = $data['model'] ?? config('ai.models.mistral', 'mistral-large-latest');
            $messages = $this->buildMistralMessages($data);

            if (! empty($data['stream'])) {
                return $this->streamMistralResponse($model, $messages);
            }

            return $this->generateResponse('mistral', $model, $messages);
        } catch (Throwable $e) {
            return $this->handleErrorResponse($e, 'Mistral');
        }
    }

    /**
     * Handles streaming generation using the Mistral client.
     */
    private function streamMistralResponse(string $model, array $messages): StreamedResponse
    {
        $stream = $this->getMistralClient()->chat()->createStreamed([
            'model' => $model,
            'messages' => $messages,
        ]);

        return $this->streamResponse($stream, fn($chunk) => $chunk->choices[0]->delta->content ?? null);
    }

    /**
     * Build Mistral messages with given data. This is identical to OpenAI's structure.
     */
    private function buildMistralMessages(array $data): array
    {
        // The helgesverre/mistral package uses the same message format as OpenAI's
        return $this->buildOpenAIMessages($data);
    }


    /**
     * Generic method to handle non-streaming responses from any provider.
     */
    private function generateResponse(string $provider, string $model, array|string $prompt, ?string $systemPrompt = null): JsonResponse
    {
        $responsePayload = [];

        switch ($provider) {
            case 'openai':
                $response = $this->getOpenAIClient()->chat()->create([
                    'model' => $model,
                    'messages' => $prompt,
                ]);
                $responsePayload = [
                    'response' => $response->choices[0]->message->content,
                    'tokens_in' => $response->usage->promptTokens,
                    'tokens_out' => $response->usage->completionTokens,
                ];
                break;

            case 'gemini':
                $response = $this->getGeminiClient()->generativeModel($model)->generateContent($prompt);
                $responsePayload = [
                    'response' => $response->text(),
                    'tokens_in' => $response->usageMetadata?->promptTokenCount ?? null,
                    'tokens_out' => $response->usageMetadata?->candidatesTokenCount ?? null,
                ];
                break;

            case 'anthropic':
                $response = $this->getAnthropicClient()->messages()->create([
                    'model' => $model,
                    'system' => $systemPrompt,
                    'messages' => $prompt,
                    'max_tokens' => 4096,
                ]);
                $responsePayload = [
                    'response' => $response->content[0]->text,
                    'tokens_in' => $response->usage->inputTokens,
                    'tokens_out' => $response->usage->outputTokens,
                ];
                break;

            case 'mistral':
                /** @var \HelgeSverre\Mistral\Responses\Chat\CreateResponse $response */
                $response = $this->getMistralClient()->chat()->create(['model' => $model, 'messages' => $prompt]);

                $responsePayload = [
                    'response' => $response->choices[0]->message->content,
                    'tokens_in' => $response->usage->promptTokens,
                    'tokens_out' => $response->usage->completionTokens,
                ];
                break;

            default:
                throw new InvalidArgumentException("Unsupported AI provider for non-streaming generation: [{$provider}]");
        }

        return response()->json($responsePayload);
    }


    /**
     * Generic method to handle streaming responses from any provider.
     */
    private function streamResponse(iterable $stream, callable $textExtractor): StreamedResponse
    {
        return new StreamedResponse(function () use ($stream, $textExtractor) {
            try {
                foreach ($stream as $chunk) {
                    $text = $textExtractor($chunk);
                    if (! empty($text)) {
                        echo $text;
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                }
            } catch (Throwable $e) {
                Log::error('An unexpected error occurred during the stream.', ['exception' => $e]);
                echo "[ERROR: An unexpected error occurred during the stream.]";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/plain',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Handles exceptions and returns a standardized JSON error response.
     */
    private function handleErrorResponse(Throwable $e, string $provider): JsonResponse
    {
        Log::error("Failed to get a response from {$provider}.", ['exception' => $e]);

        $message = config('app.debug')
            ? $e->getMessage()
            : "Failed to get a response from {$provider}. Please try again later.";

        return response()->json(['error' => $message], 500);
    }

    /**
     * Get the provider and model for a specific task from the configuration.
     *
     * @return array{provider: string, model: ?string}
     */
    private function getTaskConfig(string $task): array
    {
        $handler = config("ai.tasks.{$task}") ?? config('ai.default_handler');

        if (!str_contains($handler, ':')) {
            throw new InvalidArgumentException("Invalid AI handler format for task '{$task}'. Expected 'provider:model', but got '{$handler}'.");
        }

        [$provider, $model] = explode(':', $handler, 2);

        return ['provider' => $provider, 'model' => $model];
    }



    /**
     * Build the base prompt including file contexts.
     */
    private function buildBasePrompt(array $data): string
    {
        $parts = $this->buildBasePromptParts($data);
        // If the prompt was rendered by POML service, it's a complete prompt.
        // We can return it directly, bypassing the standard assembly.
        if (isset($parts['poml_rendered'])) {
            return $parts['poml_rendered'];
        }

        return trim(implode("\n\n", array_filter($parts)));
    }
    /**
     * Build prompt with given data
     * @return array{system_prompt: string, file_context: string, prompt: string}
     */
    private function buildBasePromptParts(array $data): array
    {
        $profileName = $data['profile'] ?? config('ai.default_profile');
        $profileData = null;
        if ($profileName) {
            $profileData = $this->loadProfile($profileName);
        }

        $prompt = (string) ($data['prompt'] ?? '');
        $isPredefinedPrompt = array_key_exists($prompt, config('ai.prompts', []));

        /**
        * Handle predefined prompts and POML templates
        * @example
        * prompt: "code_qa" <-- predefined prompt key; returns poml:ask
        * input: "Your question about the code goes here."
        * file_paths: ['path/to/your/codefile.php', 'path/to/another/file.js']
        */
        // If the prompt is a key for a predefined prompt, get the full text.
        
        if ($isPredefinedPrompt) {
            $promptTemplate = config('ai.prompts.' . $prompt);
            // Check if the prompt is a POML template reference
            if (is_string($promptTemplate) && str_starts_with($promptTemplate, 'poml:')) {
                $templateName = substr($promptTemplate, 5);

                $defaultFiles = $profileData['files'] ?? config('ai.default_files', []);
                $requestFiles = $data['file_paths'] ?? [];
                $allFiles = array_unique(array_merge($defaultFiles, $requestFiles));

                $pomlVariables = [
                    'prompt' => $data['input'] ?? '',
                    'files' => $this->resolveAbsoluteFilePaths($allFiles),
                ];

                return ['poml_rendered' => $this->app->make(PomlService::class)->render($templateName, $pomlVariables)];
            }
            $prompt = str_replace(':input', $data['input'] ?? '', $promptTemplate);
        }

        if (str_contains($prompt, ':history')) {
            $historyString = collect($data['history'] ?? [])->map(fn($item) => ($item['role'] ?? 'user') . ': ' . ($item['text'] ?? ''))->implode("\n");
            $prompt = str_replace(':history', $historyString, $prompt);
        }

        $fileContext = '';
        // Only add file context if it's not a predefined prompt like 'explanation' or 'summarize'
        // We check the original prompt key here, not the full text.
        if (! $isPredefinedPrompt) {
            $defaultFiles = $profileData['files'] ?? config('ai.default_files', []);
            $requestFiles = $data['file_paths'] ?? [];
            $allFiles = array_unique(array_merge($defaultFiles, $requestFiles));

            foreach ($allFiles as $filePath) {
                $fileContent = $this->getFileContentForPrompt($filePath);
                if (null !== $fileContent) {
                    $fileName = basename($filePath);
                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $lang = config('ai.convert_to_toon', true) && in_array($extension, ['json', 'yml', 'yaml']) ? 'toon' : $extension;
                    $fileContext .= "File: `{$fileName}`\n\n```{$lang}\n{$fileContent}\n```\n\n";
                }
            }
        }

        return [
            'system_prompt' => $data['system_prompt'] ?? $profileData['system_prompt'] ?? config('ai.prompts.system_prompt', ''),
            'file_context' => $fileContext,
            'prompt' => $prompt,
        ];
    }
    /**
     * Load AI profile from JSON file.
     * @param string $profileName
     */
    private function loadProfile(string $profileName): ?array
    {
        // Sanitize profile name to prevent directory traversal
        $profileName = basename($profileName);
        $path = "json/profiles/{$profileName}.json";
        $storage = Storage::disk('public');

        if (! $storage->exists($path)) {
            Log::warning("AI profile not found: {$path}");
            return null;
        }

        try {
            $content = $storage->get($path);
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error("Failed to parse AI profile: {$path}", ['exception' => $e]);
            return null;
        }
    }

    // Sanitize and restrict path to storage/app/public
    private function getFileContentForPrompt(string $filePath): ?string
    {
        // Sanitize and restrict path to storage/app/public
        $filePath = str_replace('..', '', $filePath);

        $storage = Storage::disk('public');

        if (! $storage->exists($filePath)) {
            Log::warning('File path could not be found in storage/app/public.', ['path' => $filePath]);
            return null;
        }

        $fullPath = $storage->path($filePath);
        $realPath = realpath($fullPath);

        if (! $realPath || ! str_starts_with($realPath, realpath($storage->path('')))) {
            Log::warning('File path access attempt outside of storage/app/public.', ['path' => $filePath]);
            return null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            try {
                return Pdf::getText($fullPath);
            } catch (BinaryNotFoundException $e) {
                Log::critical('pdftotext binary not found. Please install poppler-utils on your system.', ['exception' => $e]);
                return null;
            } catch (Throwable $e) {
                Log::error("Failed to extract text from PDF: {$fullPath}", ['exception' => $e]);
                return "Error: Could not extract text from PDF file '{$filePath}'.";
            }
        }
        if (config('ai.convert_to_toon', true)) {
            // Convert YAML to TOON
            if (in_array($extension, ['yml', 'yaml'])) {
                try {
                    $yamlContent = $storage->get($filePath);
                    $parsedYaml = Yaml::parse($yamlContent);
                    return (string) Toon::from($parsedYaml);
                } catch (Throwable $e) {
                    Log::error("Failed to convert YAML to TOON for file: {$fullPath}", ['exception' => $e]);

                    return "Error: Could not convert YAML file '{$filePath}' to TOON format.";
                }
            }
            // Convert JSON to TOON
            if ($extension === 'json') {
                try {
                    $jsonContent = $storage->get($filePath);
                    // The Toon class likely has a __toString method
                    return (string) Toon::from($jsonContent);
                } catch (Throwable $e) {
                    Log::error("Failed to convert JSON to TOON for file: {$fullPath}", ['exception' => $e]);

                    return "Error: Could not convert JSON file '{$filePath}' to TOON format.";
                }
            }
        }
        return $storage->get($filePath);
    }

    /**
     * Get text response from the specified provider.
     */
    private function getTextResponse(string $provider, string $model, string $prompt): string
    {
        return match ($provider) {
            'openai' => $this->getOpenAIClient()->chat()->create([
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ])->choices[0]->message->content,
            'gemini' => $this->getGeminiClient()->generativeModel($model)->generateContent($prompt)->text(),
            'anthropic' => $this->getAnthropicClient()->messages()->create([
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 1024,
            ])->content[0]->text,
            'mistral' => (function () use ($model, $prompt) {
                /** @var \HelgeSverre\Mistral\Responses\Chat\CreateResponse $response */
                $response = $this->getMistralClient()->chat()->create(['model' => $model, 'messages' => [['role' => 'user', 'content' => $prompt]]]);

                return $response->choices[0]->message->content;
            })(),
            default => throw new InvalidArgumentException("Unsupported AI provider for prompt suggestions: [{$provider}]"),
        };
    }
    /**
     * Generate prompt suggestions based on the chat context.
     */
    public function suggestPrompts(array $data): array
    {
        try {
            $taskConfig = $this->getTaskConfig('suggest');
            $provider = $taskConfig['provider'];
            $model = $taskConfig['model'];

            $promptForSuggestions = $this->buildBasePrompt($data);

            $responseText = $this->getTextResponse($provider, $model, $promptForSuggestions);

            $suggestionsJson = preg_replace('/^```json\s*|\s*```$/', '', $responseText);

            $decoded = json_decode(trim($suggestionsJson), true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded['suggestions']) || !is_array($decoded['suggestions'])) {
                Log::warning('AI prompt suggestion returned invalid JSON.', ['response' => $suggestionsJson]);
                return [];
            }

            return $decoded['suggestions'];
        } catch (Throwable $e) {
            Log::error('Failed to get prompt suggestions from AI.', ['exception' => $e]);
            throw $e;
        }
    }

    private function getOpenAIClient(): OpenAIClient
    {
        if (!$this->openai) {
            if (!$this->isProviderConfigured('openai')) {
                throw new \RuntimeException('OpenAI API key is not configured.');
            }
            $this->openai = $this->app->make(OpenAIClient::class);
        }
        return $this->openai;
    }

    private function getGeminiClient(): GeminiClient
    {
        if (!$this->gemini) {
            $this->gemini = $this->app->make(GeminiClient::class);
        }
        return $this->gemini;
    }

    private function getAnthropicClient(): AnthropicClient
    {
        if (!$this->anthropic) {
            $this->anthropic = $this->app->make(AnthropicClient::class);
        }
        return $this->anthropic;
    }

    private function getMistralClient(): MistralClient
    {
        if (!$this->mistral) {
            $this->mistral = $this->app->make(MistralClient::class);
        }
        return $this->mistral;
    }
}
