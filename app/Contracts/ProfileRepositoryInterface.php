<?php

namespace App\Contracts;

interface ProfileRepositoryInterface
{
    /**
     * Get all available profile names.
     *
     * @return array
     */
    public function getProfileNames(): array;

    /**
     * Find a profile by its name.
     *
     * @param string $name
     * @return array|null
     */
    public function find(string $name): ?array;

    /**
     * Create a new profile.
     *
     * @param string $name
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool;

    /**
     * Delete a profile by its name.
     *
     * @param string $name
     * @return bool
     */
    public function delete(string $name): bool;
}
