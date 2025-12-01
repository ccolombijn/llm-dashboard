<?php

namespace App\Providers;

use App\Contracts\PromptRepositoryInterface;
use App\Repositories\JsonPromptRepository;
use App\Repositories\AIRepository;
use App\Contracts\AIRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use App\Repositories\StorageFileManagerRepository;
use App\Contracts\FileManagerInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AIRepositoryInterface::class, AIRepository::class);
        $this->app->singleton(FileManagerInterface::class, StorageFileManagerRepository::class);
        $this->app->bind(
            PromptRepositoryInterface::class,
            JsonPromptRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
