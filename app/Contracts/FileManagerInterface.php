<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface FileManagerInterface
{
    /**
     * Get a list of files and folders in a directory.
     * @param string $path
     * @return array
     */
    public function listContents(string $path = ''): array;

    /**
     * Get details for a single file.
     * @param string $path
     * @return array|null
     */
    public function getFileDetails(string $path): ?array;

    /**
     * Store one or more uploaded files.
     * @param string $path
     * @param UploadedFile[] $files
     * @return void
     */
    public function store(string $path, array $files): void;

    /**
     * Delete a file or folder.
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool;

    /**
     * Create a new directory.
     * @param string $path
     * @return bool
     */
    public function createDirectory(string $path): bool;
}