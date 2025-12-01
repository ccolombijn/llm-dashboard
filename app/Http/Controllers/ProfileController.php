<?php

namespace App\Http\Controllers;

use App\Contracts\ProfileRepositoryInterface;
use App\Contracts\FileManagerInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileRepositoryInterface $profileRepository,
        private FileManagerInterface $fileManager
    ) {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $contextFilesDir = config('ai.default_files_directory');

        $allContents = $this->fileManager->listContents($contextFilesDir);

        $files = collect($allContents)
            ->where('type', 'file')
            ->pluck('path');

        return view('profiles.create', compact('files'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'system_prompt' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'string',
        ]);

        if ($this->profileRepository->create($validated)) {
            return redirect()->route('dashboard')->with('success', 'Profile created successfully.');
        }

        return back()->with('error', 'Failed to create profile.')->withInput();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $filename)
    {
        $profile = $this->profileRepository->find($filename);
        $profile['filename'] = $filename;
        $profile['name'] = $profile['name'] ?? pathinfo($filename, PATHINFO_FILENAME);
        if (!$profile) {
            return redirect()->route('dashboard')->with('error', 'Profile not found.');
        }

        //dd($profile); // Add this line to inspect the $profile variable
        // Get the directory for context files from the config.
        $contextFilesDir = config('ai.default_files_directory');

        // Get all contents from the directory using the existing method.
        $allContents = $this->fileManager->listContents($contextFilesDir);

        // Filter the contents to get only files and extract their paths.
        $files = collect($allContents)
            ->where('type', 'file')
            ->pluck('path');

        return view('profiles.edit', compact('profile', 'files'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $filename)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'system_prompt' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'string',
        ]);

        if ($this->profileRepository->update($filename, $validated)) {
            return redirect()->route('dashboard')->with('success', 'Profile updated successfully.');
        }

        return back()->with('error', 'Failed to update profile.')->withInput();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $filename)
    {

        $this->profileRepository->delete($filename);
        return redirect()->route('dashboard')->with('success', 'Profile deleted successfully.');
    }
}
