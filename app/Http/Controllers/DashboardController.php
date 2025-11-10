<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DotEnvService;
use App\Contracts\AIRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly AIRepositoryInterface $aiRepository,
        private readonly DotEnvService $dotEnvService
    ) {}

    public function index(): View
    {
        $availableApis = [
            'openai' => $this->aiRepository->isProviderConfigured('openai'),
            'gemini' => $this->aiRepository->isProviderConfigured('gemini'),
            'anthropic' => $this->aiRepository->isProviderConfigured('anthropic'),
            'mistral' => $this->aiRepository->isProviderConfigured('mistral'),
        ];

        $prompts = config('ai.prompts', []);

        return view('dashboard', compact('availableApis', 'prompts'));
    }

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
}
