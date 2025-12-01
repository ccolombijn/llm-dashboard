<?php

namespace App\Repositories;

use App\Contracts\ProfileRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
    public function create(array $data): bool
    {
        $slug = Str::slug($data['name']);
        $filePath = "{$this->path}/{$slug}.json";

        // Add the slug as a persistent ID to the data
        $data['id'] = $slug;

        try {
            // Ensure files key exists to avoid errors on new profiles
            $data['files'] = $data['files'] ?? [];
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            return Storage::disk($this->disk)->put($filePath, $jsonContent);
        } catch (\JsonException $e) {
            Log::error("Failed to encode profile data for: {$data['name']}", ['exception' => $e]);
            return false;
        }
    }

    /**
     * Update a profile by its name.
     *
     * @param string $id The original ID (slug) of the profile.
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        $originalProfile = $this->find($id);
        if (!$originalProfile) {
            return false;
        }

        $newSlug = Str::slug($data['name']);

        // If the name has changed, the slug will be different.
        // We need to delete the old file.
        if ($id !== $newSlug) {
            $this->delete($id);
        }

        // The create method will handle saving the file with the correct new or old slug.
        // It also correctly sets the 'id' field in the JSON.
        return $this->create($data);
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
        $filePath = "{$this->path}/" . strtolower($name) . ".json";
        $storage = Storage::disk($this->disk);
        return $storage->delete($filePath);
    }
}
