<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

trait HandlesResponses
{
    /**
     * Generic method to handle streaming responses from any provider.
     */
    protected function streamResponse(iterable $stream, callable $textExtractor): StreamedResponse
    {
        return new StreamedResponse(function () use ($stream, $textExtractor) {
            try {
                foreach ($stream as $chunk) {
                    $text = $textExtractor($chunk);
                    if (! empty($text)) {
                        echo $text;
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                }
            } catch (Throwable $e) {
                Log::error('An unexpected error occurred during the stream.', ['exception' => $e]);
                echo "[ERROR: An unexpected error occurred during the stream.]";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/plain',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache',
        ]);
    }

    protected function handleErrorResponse(Throwable $e, string $provider): JsonResponse
    {
        Log::error("Failed to get a response from {$provider}.", ['exception' => $e]);
        $message = config('app.debug') ? $e->getMessage() : "Failed to get a response from {$provider}. Please try again later.";
        return response()->json(['error' => $message], 500);
    }
}
