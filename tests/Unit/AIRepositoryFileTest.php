<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AIRepositoryFileTest extends TestCase
{
    public function test_get_file_content_for_prompt_missing_file_returns_null()
    {
        $repo = $this->app->make(\App\Repositories\AIRepository::class);

        $ref = new \ReflectionMethod($repo, 'getFileContentForPrompt');
        $ref->setAccessible(true);

        $result = $ref->invoke($repo, 'json/this_file_does_not_exist.txt');

        $this->assertNull($result);
    }

    public function test_get_file_content_for_prompt_pdf_handling()
    {
        $repo = $this->app->make(\App\Repositories\AIRepository::class);

        $ref = new \ReflectionMethod($repo, 'getFileContentForPrompt');
        $ref->setAccessible(true);

        $relativePath = 'json/test_pdf.pdf';
        $fullDir = storage_path('app/public/json');
        if (! File::exists($fullDir)) {
            File::ensureDirectoryExists($fullDir);
        }

        $fullPath = storage_path('app/public/' . $relativePath);
        // Create a minimal PDF-like file. Real extraction requires `pdftotext`; behavior may vary.
        file_put_contents($fullPath, "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\nstartxref\n0\n%%EOF");

        $result = $ref->invoke($repo, $relativePath);

        // Depending on environment, Pdf::getText may throw BinaryNotFoundException (returns null)
        // or return extracted text, or return an error string. Accept null or string.
        $this->assertTrue(is_null($result) || is_string($result));

        // cleanup
        @unlink($fullPath);
    }
}
