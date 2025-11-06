<?php

namespace App\Http\Controllers;

use App\Contracts\AIRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * @param AIRepositoryInterface $aiRepository
     * @return \Illuminate\View\View
     */
    public function index(AIRepositoryInterface $aiRepository)
    {
        // Determine which LLM APIs are configured based on environment variables
        $availableApis = [
            'openai' => $aiRepository->isProviderConfigured('openai'),
            'gemini' => $aiRepository->isProviderConfigured('gemini'),
            'anthropic' => $aiRepository->isProviderConfigured('anthropic'),
            'mistral' => $aiRepository->isProviderConfigured('mistral'),
        ];

        // You might still want to pass some general info, but 'llms' as previously defined
        // might be redundant if 'availableApis' covers the intent.
        // For now, let's just pass the available APIs.
        return view('dashboard', [
            'availableApis' => $availableApis,
        ]);
    }
}
