<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\PromptRepositoryInterface;
use Illuminate\Support\Facades\File;

final class JsonPromptRepository implements PromptRepositoryInterface
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/public/json/prompts.json');
    }

    public function all(): array
    {
        $defaultPrompts = config('ai.prompts', []);
        $jsonPrompts = $this->getJsonPrompts();

        return array_merge($defaultPrompts, $jsonPrompts);
    }

    public function getCustomPromptKeys(): array
    {
        return array_keys($this->getJsonPrompts());
    }

    public function find(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    public function customPromptExists(string $key): bool
    {
        return array_key_exists($key, $this->getJsonPrompts());
    }

    public function store(string $key, string $prompt): bool
    {
        $jsonPrompts = $this->getJsonPrompts();
        $jsonPrompts[$key] = $prompt;

        return $this->writePrompts($jsonPrompts);
    }

    public function update(string $key, string $prompt): bool
    {
        $jsonPrompts = $this->getJsonPrompts();
        $jsonPrompts[$key] = $prompt;

        return $this->writePrompts($jsonPrompts);
    }

    public function destroy(string $key): bool
    {
        $jsonPrompts = $this->getJsonPrompts();

        if (! array_key_exists($key, $jsonPrompts)) {
            return false;
        }

        unset($jsonPrompts[$key]);

        if (empty($jsonPrompts)) {
            if (File::exists($this->path)) {
                return File::delete($this->path);
            }
            return true;
        }

        return $this->writePrompts($jsonPrompts);
    }

    /**
     * @return array<string, string>
     */
    private function getJsonPrompts(): array
    {
        if (! File::exists($this->path)) {
            return [];
        }

        $jsonContents = File::get($this->path);

        return json_decode($jsonContents, true) ?? [];
    }

    /**
     * @param array<string, string> $prompts
     * @return bool
     */
    private function writePrompts(array $prompts): bool
    {
        File::ensureDirectoryExists(dirname($this->path));

        $result = file_put_contents(
            $this->path,
            json_encode($prompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $result !== false;
    }
}
