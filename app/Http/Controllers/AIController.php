<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AIRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;


final class AIController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(private readonly AIRepositoryInterface $aiRepository) {}

    /**
     * Handle a chat request to the OpenAI API.
     */
    public function generate(Request $request): JsonResponse|StreamedResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => ['required', 'string', 'max:4096'],
            'input' => ['sometimes', 'string', 'max:4096'],
            'history' => ['sometimes', 'array'],
            'stream' => ['sometimes', 'boolean'],
            'model' => ['sometimes', 'string'],
            'provider' => ['sometimes', 'string', 'in:openai,gemini,anthropic,mistral'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }
        $data = $validator->validated();

        return $this->aiRepository->generate($data, $data['provider'] ?? null);
    }

    /**
     * Get available AI profiles.
     *
     * @return JsonResponse
     */
    public function profiles(): JsonResponse
    {
        return response()->json(['profiles' => $this->aiRepository->getAvailableProfiles()]);
    }

    /**
     * Generate prompt suggestions based on the chat context.
     */
    public function suggestPrompts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'history' => ['sometimes', 'array'],
            'file_paths' => ['sometimes', 'array'],
            'profile' => ['sometimes', 'string'],
        ]);

        $data['prompt'] = 'suggest';

        $suggestions = $this->aiRepository->suggestPrompts($data);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Get available AI models from different providers.
     */
    public function getModels(): JsonResponse
    {
        $models = $this->aiRepository->getAvailableModels();
        if (isset($models['gemini'])) {
            $models['gemini'] = array_map(function ($model) {
                if (is_array($model) && isset($model['id']) && str_starts_with($model['id'], 'models/')) {
                    $model['id'] = str_replace('models/', '', $model['id']);
                }
                return $model;
            }, $models['gemini']);
        }

        return response()->json(['models' => $models]);
    }
}
