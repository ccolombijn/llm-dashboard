<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait FilePathResolver
{
    /**
     * Resolves file paths to absolute paths using the storage disk.
     *
     * @param array $filePaths Array of file paths.
     * @return array Array of absolute file paths.
     */
    protected function resolveAbsoluteFilePaths(array $filePaths): array
    {
        $absoluteFilePaths = [];
        foreach ($filePaths as $filePath) {
            // Use the repository's own validation logic to get a safe, absolute path
            if (Storage::disk('public')->exists(str_replace('..', '', $filePath))) {
                $absoluteFilePaths[] = Storage::disk('public')->path(str_replace('..', '', $filePath));
            }
        }

        return $absoluteFilePaths;
    }
}

