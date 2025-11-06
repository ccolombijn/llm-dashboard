<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Pug\Pug;
use Pug\Filter\Markdown as MarkdownFilter;

class PageContentService
{
    /**
     * The default parts to look for when saving page content.
     * @var string[]
     */
    protected array $parts;

    public function __construct()
    {
        $this->parts = config('page.default_parts');
    }

    /**
     * Main method to get fully rendered HTML for a given page part.
     * Pug/Markdown rendering, Blade component insertion, and image path processing.
     * @throws \Exception If rendering or file operations fail.
     * @return string The final rendered HTML content for the specified part.
     * @see renderPug()
     * @see getMarkdownContent()
     * @see insertComponents()
     * @see convertMarkdownToHtml()
     * @see processImagePathsInHtml()
     */
    public function getRenderedPartContent(string $part, array $page): string
    {
        $output = '';

        if (in_array($part, $this->parts)) {
            $output = $this->renderPug($part, $page); // First try to get pug if available
            if (is_null($output)) {
                $output = $this->getMarkdownContent($part, $page); // Fallback to markdown
            }
        } else {
            $componentView = 'components.sections.' . $part;

            if (view()->exists($componentView)) {
                $viewData = $page[$part] ?? (app('content.data')['sections.' . $part] ?? []);
                $output = view($componentView, $viewData)->render();
            }
        }
        $contentWithComponents = $this->insertComponents($output);
        $htmlOutput = $this->convertMarkdownToHtml($contentWithComponents);
        return $this->processImagePathsInHtml($htmlOutput);
    }
    
    /**
     * Saves the markdown content for a page, only creating a specific file
     * if its content differs from the default.
     * @param Request $request The incoming request containing form data.
     * @param array $page The page data array, must include 'name' key.
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Exception
     * @see getMarkdownContent()
     * @see getMarkdownPath()
     */
    public function savePartsForPage(Request $request, array $page): void
    {
        foreach ($this->parts as $part) {
            if ($request->has($part)) {
                $submittedContent = $request->input($part) ?? '';
                $specificPath = storage_path("app/public/md/{$part}/{$page['name']}.md");

                // Get the content of the default file to compare against, using our fallback logic.
                $defaultContent = $this->getMarkdownContent($part, ['name' => 'default']);

                // If submitted content is different from the default, save the specific file.
                if ($submittedContent !== $defaultContent) {
                    File::ensureDirectoryExists(dirname($specificPath));
                    File::put($specificPath, $submittedContent);
                } else {
                    // If the content is the same as the default, delete the specific file
                    // to revert to using the default.
                    if (File::exists($specificPath)) {
                        File::delete($specificPath);
                    }
                }
            }
        }
    }

    /**
     * Finds and renders a Pug file for a given part and page, with fallbacks.
     * Returns null if no Pug file is found.
     * @throws \Exception If Pug rendering fails.
     * @return string|null The rendered HTML or null if no Pug file is found.
     * @see getRenderedPartContent()
     */
    private function renderPug(string $part, array $page): ?string
    {
        $possiblePugPaths = [
            storage_path("app/public/pug/{$part}/{$page['name']}.pug"),
            storage_path("app/public/pug/{$part}/default.pug"),
            resource_path("pug/{$part}/{$page['name']}.pug"),
            resource_path("pug/{$part}/default.pug"),
        ];

        foreach ($possiblePugPaths as $path) {
            if (File::exists($path)) {
                try {
                    $pug = new Pug([
                        'basedir' => resource_path(),
                        'pretty' => true,
                        'cache' => false, // Disable cache in development, enable in production
                        'filters' => ['markdown' => new MarkdownFilter()],
                    ]);
                    // Pass page data and global content data to the Pug template
                    return $pug->render($path, ['page' => $page, 'content' => app('content.data')]);
                } catch (\Exception $e) {
                    Log::error("Pug rendering failed for '{$path}': " . $e->getMessage());
                    return "[Pug Error]";
                }
            }
        }
        return null; // Return null if no Pug file is found
    }

    /**
     * Gets raw markdown content using the fallback path logic.
     */
    public function getMarkdownContent(string $part, array $page = [ 'name' => 'default']): string
    {
        $filePath = $this->getMarkdownPath($part, $page);
        return empty($filePath) ? '' : File::get($filePath);
    }

    /**
     * Finds the correct path for a markdown file, checking storage then resources.
     * Returns empty string if no file is found.
     * @return string The path to the markdown file or empty string if not found.
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Exception
     * @see getMarkdownContent()
     * 
     */
    private function getMarkdownPath(string $part, array $page): string 
    {
        $possibleMdPaths = [
            storage_path("app/public/md/{$part}/{$page['name']}.md"),
            storage_path("app/public/md/{$part}/default.md"),
            resource_path("md/{$part}/{$page['name']}.md"),
            resource_path("md/{$part}/default.md"),
        ];
        foreach ($possibleMdPaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }
        return '';
    }

    /**
     * Inserts Blade components into a string.
     * Components are denoted by {componentName attr1="value1" attr2="value2" ...}
     * Example: {hero title="Welcome" subtitle="Enjoy your stay"}
     * @throws \Exception If the component view does not exist.
     * @return string The content with components rendered.
     */
    private function insertComponents(string $content): string
    {
        $pattern = '/{([a-zA-Z0-9_.-]+)\s*([^}]*)?}/';

        return preg_replace_callback($pattern, function ($matches) {
            $componentName = $matches[1];
            $attributesString = $matches[2] ?? '';
            $bladeComponentView = 'components.' . $componentName;

            if (!view()->exists($bladeComponentView)) {
                Log::warning("Component view not found: '{$bladeComponentView}'.");
                return '';
            }
            
            $defaultData = app('content.data')[$componentName] ?? [];
            $overrides = $this->parseAttributes($attributesString);
            $componentData = array_merge($defaultData, $overrides);

            return view($bladeComponentView, $componentData)->render();
        }, $content);
    }

    /**
     * Parses a string of HTML-like attributes into an associative array.
     * Example input: attr1="value1" attr2='value2' attr3=value3
     * Example output: ['attr1' => 'value1', 'attr2' => 'value2', 'attr3' => 'value3']
     * @return array The parsed attributes as key-value pairs.
     * @see insertComponents()
     * @throws \Exception If attribute parsing fails.
     */
    private function parseAttributes(string $str): array
    {
        $attributes = [];
        $pattern = '/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s}]+))/';
        preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[2] ?: ($match[3] ?: $match[4]);
            $attributes[$key] = $value;
        }
        return $attributes;
    }

    /**
     * Converts Markdown string to HTML
     * Uses League\CommonMark for conversion.
     * @param string $markdown The markdown content to convert.
     * @return string The converted HTML content.
     * @throws \Exception If the conversion fails.
     * @see getRenderedPartContent()
     * @see insertComponents()
     */
    private function convertMarkdownToHtml(string $markdown): string
    {
        $environment = new Environment(['html_input' => 'allow', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $converter = new MarkdownConverter($environment);
        $output = $converter->convert($markdown)->getContent();
        return $output;
    }

    /**
     * Replaces static image paths in final HTML with Vite asset paths and adds dimensions.
     * This is specifically for images stored in the resources/images directory.
     * It assumes images are referenced in the format <img src="/images/filename.ext" ...>
     * @param string $htmlContent The HTML content to process.
     * @return string The processed HTML content with updated image paths.
     * @throws \Exception If the image file does not exist.
     * @see getRenderedPartContent()
     */
    private function processImagePathsInHtml(string $htmlContent): string 
    {
        $pattern = '/<img src="\/images\/(.*?)"(.*?)>/';
        return preg_replace_callback($pattern, function ($matches) {
            $filename = $matches[1];
            $otherAttributes = $matches[2];
            $viteUrl = Vite::asset('resources/images/' . $filename);
            $physicalPath = resource_path('images/' . $filename);
            $dimensions = '';
            if (file_exists($physicalPath)) {
                if ($imageSize = getimagesize($physicalPath)) {
                    $dimensions = " width=\"{$imageSize[0]}\" height=\"{$imageSize[1]}\"";
                }
            }
            return '<img src="' . $viteUrl . '"' . $dimensions . $otherAttributes . '>';
        }, $htmlContent);
    }
}