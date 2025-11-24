<?php

namespace App\Repositories;

use App\Contracts\ProfileRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class JsonProfileRepository implements ProfileRepositoryInterface
{
    private string $disk = 'public';
    private string $path = 'json/profiles';

    /**
     * Get all available profile names.
     *
     * @return array
     */
    public function getProfileNames(): array
    {
        $storage = Storage::disk($this->disk);

        if (! $storage->exists($this->path)) {
            Log::warning("AI profiles directory not found: {$this->path}");

            return [];
        }

        $files = $storage->files($this->path);

        return collect($files)
            ->filter(fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'json')
            ->map(fn($file) => pathinfo($file, PATHINFO_FILENAME))
            ->values()
            ->all();
    }

    /**
     * Find a profile by its name.
     *
     * @param string $name
     * @return array|null
     */
    public function find(string $name): ?array
    {
        // Sanitize profile name to prevent directory traversal
        $name = basename($name);
        $filePath = "{$this->path}/{$name}.json";
        $storage = Storage::disk($this->disk);

        if (! $storage->exists($filePath)) {
            Log::warning("AI profile not found: {$filePath}");
            return null;
        }

        try {
            $content = $storage->get($filePath);
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error("Failed to parse AI profile: {$filePath}", ['exception' => $e]);
            return null;
        }
    }

    /**
     * Create or update a profile.
     *
     * @param string $name
     * @param array $data
     * @return bool
     */
    public function create(string $name, array $data): bool
    {
        $name = basename($name);
        $filePath = "{$this->path}/{$name}.json";

        try {
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            return Storage::disk($this->disk)->put($filePath, $jsonContent);
        } catch (\JsonException $e) {
            Log::error("Failed to encode profile data for: {$name}", ['exception' => $e]);
            return false;
        }
    }

    /**
     * Delete a profile by its name.
     *
     * @param string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        $name = basename($name);
        $filePath = "{$this->path}/{$name}.json";
        $storage = Storage::disk($this->disk);

        return $storage->delete($filePath);
    }
}
