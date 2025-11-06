<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ProjectRepositoryInterface;
use App\Services\PageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends AdminController
{

    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private PageContentService $contentService
    ) {}

    /**
     * Display a listing of the projects.
     * @return View
     */
    public function index(): View
    {
        return view('admin.projects.index', [
            'projects' => $this->projectRepository->all()
        ]);
    }

    /**
     * Show the form for creating a new project.
     * @return View
     */
    public function create(): View
    {
        return view('admin.projects.create');
    }

    /**
     * Store a newly created project.
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'intro' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($this->projectRepository->findBy('name', $validated['name'])) {
            return redirect()->back()
                ->withErrors(['name' => 'This name is already in use.'])
                ->withInput();
        }

        $this->projectRepository->create($validated);

        return redirect()->route('admin.projects.index')->with('success', 'Project added successfully!');
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(string $projectName): View
    {
        $project = $this->projectRepository->findBy('name', $projectName);
        if (!$project) {
            abort(404);
        }
        
        return view('admin.projects.edit', [
            'project' => $project,
            'header' => $this->contentService->getMarkdownContent('header/projects', $project),
            'content' => $this->contentService->getMarkdownContent('content/projects', $project),
            'footer' => $this->contentService->getMarkdownContent('footer/projects', $project),
        ]);
    }

    /**
     * Update the specified project.
     * @param Request $request
     * @param string $projectName
     * @return RedirectResponse
     */
    public function update(Request $request, string $projectName): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'intro' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:255',
        ]);
        $existingProject = $this->projectRepository->findBy('name', $validated['name']);
        if ($existingProject && $existingProject['name'] !== $projectName) {
            return redirect()->back()
                ->withErrors(['name' => 'This name is already in use.'])
                ->withInput();
        }
        $this->projectRepository->update('name', $projectName, $validated);

        return redirect()->route('admin.projects.index')->with('success', 'Project updated successfully!');
    }

    /**
     * Remove the specified project.
     * @param string $projectName
     * @return RedirectResponse
     */
    public function destroy(string $projectName): RedirectResponse
    {

        $this->projectRepository->delete('name', $projectName);

        return redirect()->route('admin.projects.index')->with('success', 'Project removed successfully!');
    }
}