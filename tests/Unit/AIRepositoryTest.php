<?php

namespace Tests\Unit;

use Tests\TestCase;

class AIRepositoryTest extends TestCase
{
    public function test_get_available_profiles_returns_profiles()
    {
        $profilesDir = storage_path('app/public/json/profiles');

        if (! is_dir($profilesDir)) {
            mkdir($profilesDir, 0777, true);
        }

        $testFile = $profilesDir . '/test_profile.json';
        file_put_contents($testFile, json_encode(['name' => 'test']));

        $repo = $this->app->make(\App\Repositories\AIRepository::class);

        $profiles = $repo->getAvailableProfiles();

        $this->assertIsArray($profiles);
        $this->assertContains('test_profile', $profiles);

        // cleanup
        @unlink($testFile);
    }
}
