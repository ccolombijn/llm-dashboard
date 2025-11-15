<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DotEnvService;
use App\Contracts\AIRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\File;
final class DashboardController extends Controller
{
    public function __construct(
        private readonly AIRepositoryInterface $aiRepository,
        private readonly DotEnvService $dotEnvService
    ) {}
    /**
     * Display the dashboard with AI providers, models, and prompts.
     *
     * @return View
     */
    public function index(): View
    {
        $availableApis = [
            'openai' => $this->aiRepository->isProviderConfigured('openai'),
            'gemini' => $this->aiRepository->isProviderConfigured('gemini'),
            'anthropic' => $this->aiRepository->isProviderConfigured('anthropic'),
            'mistral' => $this->aiRepository->isProviderConfigured('mistral'),
        ];

        $prompts = $this->getPrompts();

        $path = storage_path('app/public/json/prompts.json');
        $customPromptKeys = [];
        if (file_exists($path)) {
            $jsonPrompts = json_decode(file_get_contents($path), true) ?? [];
            $customPromptKeys = array_keys($jsonPrompts);
        }

        return view('dashboard', compact('availableApis', 'prompts', 'customPromptKeys'));
    
    }
    /**
     * Retrieve prompts from storage or config.
     *
     * @return array<string, string>
     */
    private function getPrompts(): array
    {
        $defaultPrompts = config('ai.prompts', []);
        $path = storage_path('app/public/json/prompts.json');

        if (! file_exists($path)) {
            return $defaultPrompts;
        }

        $jsonPrompts = json_decode(file_get_contents($path), true) ?? [];
        return array_merge($defaultPrompts, $jsonPrompts);

    }
    /**
     * Display the dashboard with AI providers, models, and prompts.
     *
     * @return View
     */
    public function updateApiKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:openai,gemini,anthropic,mistral'],
            'api_key' => ['required', 'string'],
        ]);

        $envKey = strtoupper($validated['provider']) . '_API_KEY';
        $apiKey = $validated['api_key'];

        $this->dotEnvService->setKey($envKey, $apiKey);

        return redirect()->route('dashboard')->with('success', ucfirst($validated['provider']) . ' API key updated successfully!');
    }

    /**
     * Deletes the API key for a given provider by clearing its value in the .env file.
     *
     * @param string $provider The AI provider (e.g., 'openai').
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyApiKey(string $provider): RedirectResponse
    {
        $envKeyMapping = [
            'openai' => 'OPENAI_API_KEY',
            'anthropic' => 'ANTHROPIC_API_KEY',
            'mistral' => 'MISTRAL_API_KEY',
            'gemini' => 'GEMINI_API_KEY',
        ];

        if (! array_key_exists($provider, $envKeyMapping)) {
            return back()->with('error', 'Invalid provider specified for API key deletion.');
        }

        $this->dotEnvService->setKey($envKeyMapping[$provider], '');

        return redirect()->route('dashboard')->with('success', ucfirst($provider) . ' API key has been deleted.');
    }
    /**
     * Update the default AI handler in the .env file.
     *
     * @return RedirectResponse
     */
    public function updateDefaultHandler(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:openai,gemini,anthropic,mistral'],
            'model' => ['required', 'string'],
        ]);

        $defaultHandler = "{$validated['provider']}:{$validated['model']}";
        $this->dotEnvService->setKey('AI_DEFAULT_HANDLER', $defaultHandler);

        return redirect()->route('dashboard')->with('success', 'Default handler updated successfully!');
    }
    /**
     * Display the prompt creation form.
     *
     * @return View
     */
    public function createPrompt(): View
    {
        return view('prompts.create');
    }
    /**
     * Store a newly created prompt in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function storePrompt(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'regex:/^[a-z_]+$/', 'max:255'],
            'prompt' => ['required', 'string'],
        ]);

        $path = storage_path('app/public/json/prompts.json');

        $jsonPrompts = [];
        if (file_exists($path)) {
            $jsonPrompts = json_decode(file_get_contents($path), true) ?? [];
        }
         // Prevent creating a key that already exists in the custom prompts file.
         if (array_key_exists($validated['key'], $jsonPrompts)) {
            return back()->withInput()->withErrors(['key' => 'A prompt with this key already exists.']);
        }

        $jsonPrompts[$validated['key']] = $validated['prompt'];

        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, json_encode($jsonPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect()->route('dashboard')->with('success', 'Prompt created successfully!');
    }
     /**
     * Display the prompt edit form.
     *
     * @param  string  $key
     * @return View
     */
    public function editPrompt(string $key): View
    {
        $prompts = $this->getPrompts();

        if (! array_key_exists($key, $prompts)) {
            abort(404);
        }

        return view('prompts.edit', [
            'key' => $key,
            'prompt' => $prompts[$key],
        ]);
    }

    /**
     * Update the specified prompt in storage.
     *
     * @param  Request  $request
     * @param  string  $key
     * @return RedirectResponse
     */
    public function updatePrompt(Request $request, string $key): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
        ]);

        $path = storage_path('app/public/json/prompts.json');

        $jsonPrompts = file_exists($path) ? json_decode(file_get_contents($path), true) ?? [] : [];

        $jsonPrompts[$key] = $validated['prompt'];

        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, json_encode($jsonPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect()->route('dashboard')->with('success', 'Prompt updated successfully!');
    }
    /**
     * Remove the specified prompt from storage.
     *
     * @param  string  $key
     * @return RedirectResponse
     */
    public function destroyPrompt(string $key): RedirectResponse
    {
        $path = storage_path('app/public/json/prompts.json');

        if (! file_exists($path)) {
            return redirect()->route('dashboard')->withErrors(['error' => 'Custom prompts file not found.']);
        }

        $jsonPrompts = json_decode(file_get_contents($path), true) ?? [];

        if (! array_key_exists($key, $jsonPrompts)) {
            return redirect()->route('dashboard')->withErrors(['error' => 'Prompt not found in custom prompts.']);
        }

        unset($jsonPrompts[$key]);

        file_put_contents($path, json_encode($jsonPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (empty($jsonPrompts)) {
            File::delete($path);
        }

        return redirect()->route('dashboard')->with('success', 'Prompt deleted successfully!');
    }
}
