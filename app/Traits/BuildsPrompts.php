<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\PomlService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToText\Exceptions\BinaryNotFoundException;
use Spatie\PdfToText\Pdf;
use Sbsaga\Toon\Facades\Toon;
use Symfony\Component\Yaml\Yaml;
use Throwable;

trait BuildsPrompts
{
    use FilePathResolver; // Assuming FilePathResolver is a separate trait or part of this.

    /**
     * Build the base prompt including file contexts.
     */
    protected function buildBasePrompt(array $data): string
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
    protected function buildBasePromptParts(array $data): array
    {
        // Assuming $this->profileRepository is available, perhaps injected into the class using this trait
        // For now, we'll assume it's passed or resolved.
        $profileRepository = app(\App\Contracts\ProfileRepositoryInterface::class);

        $profileName = $data['profile'] ?? config('ai.default_profile');
        $profileData = null;
        if ($profileName) {
            $profileData = $profileRepository->find($profileName);
        }

        $prompt = (string) ($data['prompt'] ?? '');
        $isPredefinedPrompt = array_key_exists($prompt, config('ai.prompts', []));

        if ($isPredefinedPrompt) {
            $promptTemplate = config('ai.prompts.' . $prompt);
            if (is_string($promptTemplate) && str_starts_with($promptTemplate, 'poml:')) {
                $templateName = substr($promptTemplate, 5);

                $defaultFiles = $profileData['files'] ?? config('ai.default_files', []);
                $requestFiles = $data['file_paths'] ?? [];
                $allFiles = array_unique(array_merge($defaultFiles, $requestFiles));

                $pomlVariables = [
                    'prompt' => $data['input'] ?? '',
                    'files' => $this->resolveAbsoluteFilePaths($allFiles),
                ];

                return ['poml_rendered' => app(PomlService::class)->render($templateName, $pomlVariables)];
            }
            $prompt = str_replace(':input', $data['input'] ?? '', $promptTemplate);
        }

        if (str_contains($prompt, ':history')) {
            $historyString = collect($data['history'] ?? [])->map(fn($item) => ($item['role'] ?? 'user') . ': ' . ($item['text'] ?? ''))->implode("\n");
            $prompt = str_replace(':history', $historyString, $prompt);
        }

        $fileContext = '';
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

    protected function getFileContentForPrompt(string $filePath): ?string
    {
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
            if ($extension === 'json') {
                try {
                    $jsonContent = $storage->get($filePath);
                    return (string) Toon::from($jsonContent);
                } catch (Throwable $e) {
                    Log::error("Failed to convert JSON to TOON for file: {$fullPath}", ['exception' => $e]);
                    return "Error: Could not convert JSON file '{$filePath}' to TOON format.";
                }
            }
        }
        return $storage->get($filePath);
    }
}
