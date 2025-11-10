<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class DotEnvService
{
    /**
     * The path to the .env file.
     * @var string
     */
    protected string $envPath;

    public function __construct()
    {
        $this->envPath = app()->environmentFilePath();
    }

    /**
     * Set or update a key-value pair in the .env file.
     *
     * @param string $key The environment variable key.
     * @param string $value The value to set for the key.
     * @return void
     */
    public function setKey(string $key, string $value): void
    {
        if (!File::exists($this->envPath)) {
            return;
        }

        $content = File::get($this->envPath);

        $escapedKey = preg_quote($key, '/');
        $pattern = "/^{$escapedKey}=.*/m";

        $newEntry = "{$key}={$value}";

        if (preg_match($pattern, $content)) {
            // Key exists, so we replace it
            $content = preg_replace($pattern, $newEntry, $content);
        } else {
            // Key does not exist, so we append it
            $content .= "\n{$newEntry}\n";
        }

        File::put($this->envPath, $content);
    }
}
