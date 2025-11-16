<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Spatie\PdfToText\Exceptions\BinaryNotFoundException;
use Spatie\PdfToText\Pdf;
use Throwable;


class PomlService
{
    /**
     * Renders a POML template with the given variables.
     *
     * @param string $templateName The name of the poml file (e.g., 'ask').
     * @param array $variables The variables to inject into the template.
     * @return string The rendered prompt.
     */
    public function render(string $templateName, array $variables): string
    {
        // Path to your poml file in storage
        $path = "poml/{$templateName}.poml";

        if (!Storage::disk('public')->exists($path)) {
            throw new InvalidArgumentException("POML template not found at: {$path}");
        }

        $template = Storage::disk('public')->get($path);

        // Process loops (like <div for="file in files">)
        $template = $this->parseLoops($template, $variables);

        // Process simple variable placeholders (like {{prompt}})
        $template = $this->parseVariables($template, $variables);

        return $template;
    }

    /**
     * A simple parser for {{variable}} placeholders.
     */
    private function parseVariables(string $template, array $variables): string
    {
        return preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) use ($variables) {
            $key = $matches[1];
            return $variables[$key] ?? '';
        }, $template);
    }

    /**
     * A simple parser for <div for="item in items"> and <document> tags.
     * This is tailored for your `ask.poml` file.
     */
    private function parseLoops(string $template, array $variables): string
    {
        // Regex to find a <div for="file in files">...</div> block
        $pattern = '/<div for="(\w+) in (\w+)">\s*(.*?)\s*<\/div>/s';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $itemName = $matches[1]; // e.g., 'file'
            $arrayName = $matches[2]; // e.g., 'files'
            $loopContent = $matches[3]; // The content inside the loop

            if (!isset($variables[$arrayName]) || !is_array($variables[$arrayName])) {
                return ''; // If the array isn't provided, remove the loop block
            }

            $renderedBlocks = [];
            foreach ($variables[$arrayName] as $item) {
                // Replace the item placeholder (e.g., {{file}})
                $block = str_replace("{{{$itemName}}}", $item, $loopContent);

                // Process the <document src="..."> tag
                $block = $this->parseDocumentTag($block);
                $renderedBlocks[] = $block;
            }

            return implode("\n", $renderedBlocks);
        }, $template);
    }

    /**
     * Parses the <document src="{{file}}" /> tag to inline file content.
     */
    private function parseDocumentTag(string $content): string
    {
        // Regex to find <document src="..." />
        $pattern = '/<document src="([^"]+)"[^>]*\/>/s';

        return preg_replace_callback($pattern, function ($matches) {
            $filePath = $matches[1];
            if (! File::exists($filePath)) {
                Log::warning('POML Service: File path could not be found.', ['path' => $filePath]);

                return "Error: File not found at '{$filePath}'";
            }
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if ($extension === 'pdf') {
                try {
                    return Pdf::getText($filePath);
                } catch (BinaryNotFoundException $e) {
                    Log::critical('pdftotext binary not found. Please install poppler-utils on your system.', ['exception' => $e]);

                    return "Error: Cannot process PDF files. The 'pdftotext' binary is not installed.";
                } catch (Throwable $e) {
                    Log::error("POML Service: Failed to extract text from PDF: {$filePath}", ['exception' => $e]);

                    return "Error: Could not extract text from PDF file '{$filePath}'.";
                }
            }

            return File::get($filePath);
        }, $content);
    }
}
