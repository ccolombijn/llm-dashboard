<?php

namespace App\Http\Controllers;

use App\Contracts\ProfileRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function __construct(private ProfileRepositoryInterface $profileRepository) {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('profiles.create');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'system_prompt' => 'nullable|string',
        ]);

        $name = Str::slug($request->input('name'));
        $data = [
            'system_prompt' => $request->input('system_prompt', ''),
            'files' => [], // Default empty files array
        ];

        if ($this->profileRepository->create($name, $data)) {
            return redirect()->route('dashboard')->with('success', 'Profile created successfully.');
        }

        return back()->with('error', 'Failed to create profile.')->withInput();
    }
}
