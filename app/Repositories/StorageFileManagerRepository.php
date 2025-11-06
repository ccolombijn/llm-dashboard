<?php

namespace App\Repositories;

use App\Contracts\FileManagerInterface;
use App\Services\FileProcessingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class StorageFileManagerRepository implements FileManagerInterface
{
    /**
     * The disk to use for all storage operations.
     * @var string
     */
    private string $disk = 'public';

    public function __construct(private FileProcessingService $fileService) {}

    /**
     * Get a list of files and folders in a directory.
     * @param string $path
     * @return array
     */
    public function listContents(string $path = ''): array
    {
        $storage = Storage::disk($this->disk);

        $directories = $storage->directories($path);
        $files = $storage->files($path);

        $contents = [];

        foreach ($directories as $directory) {
            $contents[] = [
                'type' => 'folder',
                'name' => basename($directory),
                'path' => $directory,
                'size' => $this->fileService->formatFileSize(
                    $this->fileService->getDirectorySize($storage->path($directory))
                ),
            ];
        }

        foreach ($files as $file) {
            if (basename($file) === '.gitignore') {
                continue;
            }

            $contents[] = [
                'type' => 'file',
                'name' => basename($file),
                'path' => $file,
                'mime_type' => $storage->mimeType($file),
                'size' => $this->fileService->formatFileSize($storage->size($file)),
                'last_modified' => Carbon::createFromTimestamp($storage->lastModified($file))->format('Y-m-d H:i:s'),
            ];
        }

        return $contents;
    }

    /**
     * Get details for a single file.
     * @param string $path
     * @return array|null
     */
    public function getFileDetails(string $path): ?array
    {
        $storage = Storage::disk($this->disk);

        if (!$storage->exists($path)) {
            return null;
        }

        $physicalPath = $storage->path($path);

        return [
            'name' => basename($path),
            'path' => $physicalPath,
            'type' => $storage->mimeType($path),
            'content' => $storage->get($path),
            'size' => $this->fileService->formatFileSize($storage->size($path)),
            'date' => Carbon::createFromTimestamp($storage->lastModified($path))->format('d-m-Y H:i'),
            'hash' => hash_file('sha256', $physicalPath),
        ];
    }

    /**
     * Store one or more uploaded files.
     * @param string $path
     * @param array $files
     * @return void
     */
    public function store(string $path, array $files): void
    {
        foreach ($files as $file) {
            // The fileService handles the actual storing, optimizing, and WebP conversion
            $this->fileService->optimizeAndConvert($file, $path . '/' . $file->getClientOriginalName());
        }
    }

    /**
     * Delete a file or folder.
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        $storage = Storage::disk($this->disk);

        if ($storage->exists($path)) {
            // Check if it's a directory or file by checking for an extension
            if (empty(pathinfo($path, PATHINFO_EXTENSION))) {
                return $storage->deleteDirectory($path);
            } else {
                // Also delete the WebP version if it exists
                $webpPath = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.webp';
                if ($storage->exists($webpPath)) {
                    $storage->delete($webpPath);
                }
                return $storage->delete($path);
            }
        }
        return false;
    }

    /**
     * Create a new directory.
     * @param string $path
     * @return bool
     */
    public function createDirectory(string $path): bool
    {
        return Storage::disk($this->disk)->makeDirectory($path);
    }
}
