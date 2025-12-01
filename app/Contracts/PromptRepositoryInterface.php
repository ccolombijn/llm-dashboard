<?php

declare(strict_types=1);

namespace App\Contracts;

interface PromptRepositoryInterface
{
    /**
     * @return array<string, string>
     */
    public function all(): array;

    /**
     * @return array<string>
     */
    public function getCustomPromptKeys(): array;

    public function find(string $key): ?string;

    public function customPromptExists(string $key): bool;

    public function store(string $key, string $prompt): bool;

    public function update(string $key, string $prompt): bool;

    /**
     * @param string $key
     * @return bool
     */
    public function destroy(string $key): bool;
}
