<?php

namespace App\Providers;

use App\Contracts\AIRepositoryInterface;
use App\Contracts\ContactRepositoryInterface;
use App\Contracts\FileManagerInterface;
use App\Contracts\PageRepositoryInterface;
use App\Contracts\ProjectRepositoryInterface;
use App\Repositories\AIRepository;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FilesController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ContactController;
use App\Repositories\JsonContactRepository;
use App\Repositories\JsonPageRepository;
use App\Repositories\JsonProjectRepository;

use Illuminate\Support\Facades\Storage;


class AppServiceProvider extends ServiceProvider
{


    /**
     * Register any application services.
     */
    public function register(): void
    {




        if (config('app.data_source') === 'database') {
            //     $this->app->singleton(RepositoryInterface::class, DatabasePageRepository::class);
        } else {
            $this->app->singleton(PageRepositoryInterface::class, JsonPageRepository::class);
            $this->app->singleton(ProjectRepositoryInterface::class, JsonProjectRepository::class);
            $this->app->singleton(ContactRepositoryInterface::class, JsonContactRepository::class);
            $this->app->singleton(AIRepositoryInterface::class, AIRepository::class);

            //     $this->app->singleton(RepositoryInterface::class, JsonPageRepository::class);
        }
        $sources = [
            'pages',
            'content',
            'contact',
            'projects'
        ];
        foreach ($sources as $source) {
            $this->singleton($source);
        }
    }
    /**
     * Cached file data as singleton
     */
    private function singleton(string $source): void
    {
        $this->app->singleton($source . '.data', function () use ($source) {
            return Cache::rememberForever($source . '.json.data', function () use ($source) {
                $path = storage_path('app/public/json/' . $source . '.json');
                if (!File::exists($path)) {
                    return [];
                }
                $json = File::get($path);
                return json_decode($json, true);
            });
        });
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $pagesData = $this->app->make('pages.data');
        $contentData = $this->app->make('content.data');
        $navigationItems = collect($pagesData)->map(function ($page) {
            $page['routeName'] = isset($page['method']) && str_contains($page['method'], '.')
                ? $page['name'] . '.' . $page['method']
                : $page['name'];
            return $page;
        })->all();
        $contactData = $this->app->make('contact.data');
        View::share('navigationItems', $navigationItems);
        View::share('contactData', $contactData);
        View::share('contentData', $contentData);
        $this->app->when(ContactController::class)
            ->needs('$contactData')
            ->give($contactData);

        $this->app->when([
            PortfolioController::class,
            DashboardController::class,
            AdminPageController::class,
            AdminProjectController::class
        ])
            ->needs('$projects')
            ->give($this->app->make('projects.data'));
        $this->app->when([
            DashboardController::class,
            AdminPageController::class
        ])
            ->needs('$pages')
            ->give($pagesData);
        $this->app->when(FilesController::class)
            ->needs('$files')
            ->give(Storage::disk('public'));
    }
}
