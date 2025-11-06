<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ApplicationRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends AdminController
{
    private $rules;

    public function __construct(
        private ApplicationRepositoryInterface $applicationRepository
    ) {
        $this->rules = [
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'job_position' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
            'rating' => 'nullable|string|max:255',
            'applied' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'pw' => 'nullable|string|max:255',
        ];
    }

    /**
     * Display a listing of the applications.
     */
    public function index(): View
    {
        return view('admin.applications.index', [
            'applications' => $this->applicationRepository->all(),
        ]);
    }

    /**
     * Show the form for creating a new application.
     */
    public function create(): View
    {
        // Default parts for a new application
        $defaultParts = config('application.default_parts');

        return view('admin.applications.create');
    }

    /**
     * Store a newly created application in storage.
     */
    public function store(Request $request): RedirectResponse
    {

        $validated = $request->validate($this->rules);

        if ($this->applicationRepository->find($validated['name'])) {
            return redirect()->back()
                ->withErrors(['name' => 'This name is already in use. Please choose a different one.'])
                ->withInput();
        }

        $newApplication = [];
        foreach($this->rules as $field => $rule) {
            $nullable = str_contains($rule,'nullable');
            if($nullable && isset($validated[$field])) {
                $newApplication[] = $validated[$field]; // make sure nullable is set
            } else {
                $newApplication[] = $validated[$field];
            }
        }

        $this->applicationRepository->create($newApplication);

        return redirect()->route('admin.applications.index')->with('success', 'Application added successfully!');
    }

    /**
     * Show the form for editing the specified application.
     */
    public function edit(string $applicationName): View
    {
        $application = $this->applicationRepository->find($applicationName);
        if (!$application) {
            abort(404);
        }

        return view('admin.applications.edit', [
            'application' => $application,
        ]);
    }

    /**
     * Update the specified application in storage.
     */
    public function update(Request $request, string $applicationName): RedirectResponse
    {

        $updateData = $request->validate($this->rules);
        $this->applicationRepository->update('name', $applicationName, $updateData);
        return redirect()->route('admin.applications.index')->with('success', 'Application updated successfully!');
    }

    /**
     * Remove the specified application from storage.
     */
    public function destroy(string $applicationName): RedirectResponse
    {
        $this->applicationRepository->delete('name', $applicationName);
        return redirect()->route('admin.applications.index')->with('success', 'Application removed successfully!');
    }
}