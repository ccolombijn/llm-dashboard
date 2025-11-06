<?php

namespace App\Contracts;

interface RepositoryInterface
{
    /**
     * Get all items.
     * @return array
     */
    public function all(): array;
    
    /**
     * Find item by name.
     * @param string $name
     * @return array|null
     */
    public function find(string $name): ?array;

    /**
     * Find item by key and value
     * @param string $key
     * @param string $value
     * @return array|null
     */
    public function findBy(string $key, string $value): ?array;

    /**
     * Update item.
     * @param $key
     * @param $value
     * @param array $data
     * @return bool
     */
    public function update(string $key, string $value, array $data): bool;

    /**
     * Create a new item.
     * @param array $data
     * @return void
     */
    public function create(array $data): void;

    /**
     * Delete an item.
     * @param string $key
     * @param string $name
     * @return bool
     */
    public function delete(string $key, string $name): bool;
}