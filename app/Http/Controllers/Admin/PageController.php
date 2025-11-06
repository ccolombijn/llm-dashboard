<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PageRepositoryInterface;
use App\Services\PageContentService;
use App\Services\PageFormOptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends AdminController
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private PageContentService $contentService,
        private PageFormOptionsService $formOptionsService
    ) {}

    /**
     * Display a listing of the pages.
     */
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => $this->pageRepository->all(),
        ]);
    }

    /**
     * Show the form for creating a new page.
     */
    public function create(): View
    {
        // Default parts for a new page
        $defaultParts = config('page.default_parts');

        return view('admin.pages.create', [
            'controllers' => $this->formOptionsService->getControllers(),
            'views' => $this->formOptionsService->getPageViews(),
            'sorted_sections' => $this->formOptionsService->getSortedParts($defaultParts),
            'selected_parts' => $defaultParts,
            'header' => $this->contentService->getMarkdownContent('header'),
            'content' => $this->contentService->getMarkdownContent('content'),
            'footer' => $this->contentService->getMarkdownContent('footer'),
        ]);
    }

    /**
     * Store a newly created page in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'controller' => 'nullable|string|max:255',
            'method' => 'nullable|string|max:255',
            'view' => 'nullable|string|max:255',
            'parts' => 'nullable|array',
            'parts_order' => 'required|string',
        ]);

        if ($this->pageRepository->find($validated['name'])) {
            return redirect()->back()
                ->withErrors(['name' => 'This name is already in use. Please choose a different one.'])
                ->withInput();
        }

        $newPage = [
            'name' => $validated['name'],
            'title' => $validated['title'],
        ];

        $optionalFields = ['route', 'controller', 'method', 'view'];
        foreach ($optionalFields as $field) {
            if (!empty($validated[$field])) {
                $newPage[$field] = $validated[$field];
            }
        }

        $selectedParts = $validated['parts'] ?? [];
        $orderedPartNames = explode(',', $validated['parts_order']);
        $newPage['parts'] = collect($orderedPartNames)
            ->filter(fn($partName) => in_array($partName, $selectedParts))
            ->values()
            ->all();

        $this->pageRepository->create($newPage);

        $this->contentService->savePartsForPage($request, $newPage);

        return redirect()->route('admin.pages.index')->with('success', 'Page added successfully!');
    }

    /**
     * Show the form for editing the specified page.
     */
    public function edit(string $pageName): View
    {
        $page = $this->pageRepository->find($pageName);
        if (!$page) {
            abort(404);
        }

        $pageParts = $page['parts'] ?? config('page.default_parts');
        $header = $this->contentService->getMarkdownContent('header', $page);
        $content = $this->contentService->getMarkdownContent('content', $page);
        $footer = $this->contentService->getMarkdownContent('footer', $page);

        $sortedSections = $this->formOptionsService->getSortedParts($pageParts);
        //dd($sortedSections);
        return view('admin.pages.edit', [
            'page' => $page,
            'controllers' => $this->formOptionsService->getControllers(),
            'views' => $this->formOptionsService->getPageViews(),
            'sorted_sections' => $sortedSections,
            'selected_parts' => $pageParts,
            'header' => $header,
            'content' => $content,
            'footer' => $footer,
        ]);
    }

    /**
     * Update the specified page in storage.
     */
    public function update(Request $request, string $pageName): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'controller' => 'nullable|string|max:255',
            'method' => 'nullable|string|max:255',
            'view' => 'nullable|string|max:255',
            'parts' => 'nullable|array',
            'parts_order' => 'required|string',
        ]);

        $updateData = $validated;
        unset($updateData['parts'], $updateData['parts_order']); // Remove temporary fields

        $selectedParts = $validated['parts'] ?? [];
        $orderedPartNames = explode(',', $validated['parts_order']);
        $updateData['parts'] = collect($orderedPartNames)
            ->filter(fn($partName) => in_array($partName, $selectedParts))
            ->values()
            ->all();

        $this->pageRepository->update('name', $pageName, $updateData);
        $page = $this->pageRepository->find($pageName); // Get the updated page data
        $this->contentService->savePartsForPage($request, $page);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully!');
    }

    /**
     * Remove the specified page from storage.
     */
    public function destroy(string $pageName): RedirectResponse
    {
        $this->pageRepository->delete('name', $pageName);
        // @todo : delete the associated markdown files here
        // using the PageContentService.

        return redirect()->route('admin.pages.index')->with('success', 'Page removed successfully!');
    }
}