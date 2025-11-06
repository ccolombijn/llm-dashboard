<?php

namespace App\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class PageFormOptionsService
{

    /**
     * Get a list of all controllers and their methods that return views
     * @return array
     */
    public function getControllers(): array
    {
        $controllers = [];
        $path = app_path('Http/Controllers');
        foreach (File::files($path) as $file) {
            $controllerName = $file->getFilenameWithoutExtension();
            if ($controllerName === 'Controller') continue;

            $className = 'App\\Http\\Controllers\\' . $controllerName;
            $reflection = new \ReflectionClass($className);
            $methods = [];

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $className) continue;

                $returnType = $method->getReturnType();
                if ($returnType) {
                    $types = ($returnType instanceof \ReflectionUnionType || $returnType instanceof \ReflectionIntersectionType)
                        ? $returnType->getTypes()
                        : [$returnType];

                    foreach ($types as $type) {
                        if ($type instanceof \ReflectionNamedType && $type->getName() === View::class) {
                            $methods[] = $method->getName();
                            // Once we find a View, we can stop checking types for this method
                            break;
                        }
                    }
                }
            }

            if (!empty($methods)) {
                $controllers[$controllerName] = $methods;
            }
        }
        return $controllers;
    }

    /**
     * Get a list of all views in a given path
     * @param string $path The path to the views directory relative to resources/views
     * @param bool $fullPath Whether to return the full view name (with dots) or just the filename
     * @return array
     */
    private function getViews(string $path, bool $fullPath = true): array
    {
        $views = [];
        $resourcePath = resource_path('views/' . $path);
        if (!File::isDirectory($resourcePath)) {
            return [];
        }

        foreach (File::files($resourcePath) as $file) {
            $viewName = basename($file->getFilename(), '.blade.php');
            $views[] = $fullPath ? str_replace('/', '.', $path) . '.' . $viewName : $viewName;
        }

        return $views;
    }

    /**
     * Get a list of all page views
     * @return array
     */
    public function getPageViews(): array
    {
        return $this->getViews('pages');
    }

    /**
     * Get a list of alle section components
     * @return array	
     */
    public function getSections(): array
    {
        return $this->getViews('components/sections', false);
    }

    public function getSortedParts(array $pageParts = []): array
    {
        $allSections = $this->getSections();
        $selected = array_intersect($pageParts, $allSections);
        $remainder = array_diff($allSections, $pageParts);
        return array_merge($selected, $remainder);
    }
}
