<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class DatabasePageRepository implements RepositoryInterface
{
    /**
     * A unique key for caching the results from this repository.
     * @var string
     */
    private string $cacheKey = 'pages.db.data';

    /**
     * Get all pages, using a cache to avoid repeated database queries.
     * This method retrieves all pages from the database and caches them indefinitely.
     * If the cache is empty, it fetches all pages from the database and returns them as an array.
     * @return array
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function all(): array
    {
        return Cache::rememberForever($this->cacheKey, function () {
            // If the cache is empty, fetch all pages from the database and return them as an array.
            return Page::all()->toArray();
        });
    }

    /**
     * Find a specific page by its name from the cached collection.
     * This method uses the cached result from 'all()' to find a page by its name.
     * It returns the first page that matches the given name, or null if no match is found.
     * @param string $name
     * @return array|null
     */
    public function find(string $name): ?array
    {
        // This is more efficient than a separate DB query as it uses the cached 'all()' result.
        return collect($this->all())->firstWhere('name', $name);
    }

    /**
     * Find a specific page by its name from the cached collection.
     * This method uses the cached result from 'all()' to find a page by a specific key and value.
     * It returns the first page that matches the given key and value, or null if no match is found.
     * @param string $key The key to search by (e.g., 'slug', 'id').
     * @param string $value The value to match against the key.
     * @return array|null
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function findBy(string $key, string $value): ?array
    {
        return collect($this->all())->firstWhere($key, $value);
    }

    /**
     * Create a new page in the database and clear the cache.
     * This method accepts an array of data to create a new page.
     * After creating the page, it clears the cache to ensure the next call to 'all()' retrieves the latest data.
     * @param array $data An associative array containing the page data.
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function create(array $data): void
    {
        Page::create($data);
        Cache::forget($this->cacheKey);
    }

    /**
     * Update a page in the database and clear the cache.
     * This method finds a page by a specific key and value, updates it with the provided data,	
     * and clears the cache if the update is successful.
     * @param string $key The key to search by (e.g., 'slug', 'id').
     * @param string $value The value to match against the key.
     * @param array $data An associative array containing the updated page data.
     * @return bool
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function update(string $key, string $value, array $data): bool
    {
        $page = Page::where($key, $value)->first();

        if (!$page) {
            return false;
        }

        $updated = $page->update($data);

        if ($updated) {
            Cache::forget($this->cacheKey);
        }

        return $updated;
    }

    /**
     * Delete a page from the database and clear the cache.
     * This method finds a page by a specific key and value, deletes it,
     * and clears the cache if the deletion is successful.
     * @param string $key The key to search by (e.g., 'slug', 'id').
     * @param string $value The value to match against the key.
     * @return bool Returns true if the page was deleted, false otherwise.
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function delete(string $key, string $value): bool
    {
        $page = Page::where($key, 'like', $value)->first();

        if (!$page) {
            return false;
        }

        $deleted = $page->delete();

        if ($deleted) {
            Cache::forget($this->cacheKey);
        }

        return $deleted;
    }
}