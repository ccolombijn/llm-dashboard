<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AIRepositoryInterface;
use App\Contracts\ProfileRepositoryInterface;
use App\Contracts\ProviderRepositoryInterface;
use Anthropic\Client as AnthropicClient;
use App\Traits\BuildsPrompts;
use Illuminate\Contracts\Foundation\Application;
use Gemini\Data\Content;
use Gemini\Data\Part;
use App\Traits\FilePathResolver;
use Gemini\Client as GeminiClient;
use HelgeSverre\Mistral\Mistral as MistralClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use OpenAI\Client as OpenAIClient;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AIRepository implements AIRepositoryInterface
{
    use FilePathResolver, BuildsPrompts;
    private ?OpenAIClient $openai = null;
    private ?GeminiClient $gemini = null;
    private ?AnthropicClient $anthropic = null;
    private ?MistralClient $mistral = null;

    public function __construct(
        private readonly Application $app,
        private readonly ProfileRepositoryInterface $profileRepository
    ) {}

    /**
     * Get available AI profile names.
     */
    public function getAvailableProfiles(): array
    {
        return $this->profileRepository->getProfileNames();
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
        $providers = ['openai', 'gemini', 'anthropic', 'mistral']; // Define all supported providers
        foreach ($providers as $provider) {
            if ($this->isProviderConfigured($provider)) {
                try {
                    $allModels[$provider] = $this->getProviderRepository($provider)->getAvailableModels();
                } catch (Throwable $e) {
                    Log::error("Failed to retrieve models from {$provider}.", ['exception' => $e]);
                    $allModels[$provider] = [];
                }
            }
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

        $providerRepository = $this->getProviderRepository($provider);
        return $providerRepository->generate($data);
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
     * Get text response from the specified provider.
     */
    private function getTextResponse(string $provider, string $model, string $prompt): string
    {
        $providerRepository = $this->getProviderRepository($provider);
        return $providerRepository->getTextResponse($model, $prompt);
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

    private function getProviderRepository(string $provider): ProviderRepositoryInterface
    {
        $repositoryClass = match ($provider) {
            'openai' => OpenAIRepository::class,
            'gemini' => GeminiRepository::class,
            'anthropic' => AnthropicRepository::class,
            'mistral' => MistralRepository::class,
            default => throw new InvalidArgumentException("Unsupported AI provider: [{$provider}]"),
        };

        return $this->app->make($repositoryClass);
    }

    public function chat(string $provider, array $messages): array
    {
        try {
            return match ($provider) {
                'openai' => $this->chatOpenAI($messages),
                'anthropic' => $this->chatAnthropic($messages),
                'gemini' => $this->chatGemini($messages),
                'mistral' => $this->chatMistral($messages),
                default => throw new InvalidArgumentException("Provider [{$provider}] not supported for chat."),
            };
        } catch (Throwable $e) {
            Log::error("Chat generation failed for provider {$provider}", ['exception' => $e]);
            return ['role' => 'assistant', 'content' => "Error: " . $e->getMessage()];
        }
    }

    private function chatOpenAI(array $messages): array
    {
        $client = \OpenAI::client(config('openai.api_key'));
        $response = $client->chat()->create([
            'model' => config('ai.models.openai', 'gpt-4o'),
            'messages' => $messages,
        ]);

        return ['role' => 'assistant', 'content' => $response->choices[0]->message->content];
    }

    private function chatAnthropic(array $messages): array
    {
        $client = \Anthropic::client(config('services.anthropic.api_key'));

        $system = '';
        $filteredMessages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system .= $msg['content'] . "\n";
            } else {
                $filteredMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }

        $response = $client->messages()->create([
            'model' => config('ai.models.anthropic', 'claude-3-5-sonnet-20240620'),
            'max_tokens' => 4096,
            'system' => trim($system),
            'messages' => $filteredMessages,
        ]);

        return ['role' => 'assistant', 'content' => $response->content[0]->text];
    }

    private function chatGemini(array $messages): array
    {
        $client = \Gemini::client(config('gemini.api_key'));
        $model = $client->generativeModel(config('ai.models.gemini', 'gemini-1.5-flash'));

        $systemInstruction = null;
        $history = [];

        // Extract the last message to use as the new user prompt
        $lastMessage = array_pop($messages);

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = $msg['content'];
            } else {
                $role = $msg['role'] === 'user' ? 'user' : 'model';
                $history[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
            }
        }

        if ($systemInstruction) {
            $model = $model->withSystemInstruction(new Content(parts: [new Part(text: $systemInstruction)]));
        }

        $chat = $model->startChat(history: $history);
        $response = $chat->sendMessage($lastMessage['content']);

        return ['role' => 'assistant', 'content' => $response->text()];
    }

    private function chatMistral(array $messages): array
    {
        $client = new MistralClient(config('mistral.api_key'));
        $response = $client->chat()->create([
            'model' => config('ai.models.mistral', 'mistral-small-latest'),
            'messages' => $messages,
        ]);

        return ['role' => 'assistant', 'content' => $response['choices'][0]['message']['content']];
    }
}
