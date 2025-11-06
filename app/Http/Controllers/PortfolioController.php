<?php

namespace App\Http\Controllers;

use App\Contracts\ProjectRepositoryInterface;
use App\Services\PageContentService;
use Illuminate\Contracts\View\View;

class PortfolioController extends Controller
{
    protected array $parts;

    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private PageContentService $pageContentService
    ) {
        $this->parts = config('page.default_parts');
    }

    /**
     * Display the portfolio overview page.
     */
    public function index(array $page): View
    {

        $projects = $this->projectRepository->all();

        $data = [
            'page' => $page,
            'items' => $projects,
            'name' => 'portfolio',
            'route' => 'portfolio.project',
            'key' => 'project',
        ];

        foreach ($this->parts as $part) { 
            $data[$part] = $this->pageContentService->getRenderedPartContent($part, $page);
        }

        return view('pages.overview', $data);
    }

    /**
     * Display a single project detail page.
     * The $page array and $projectName string are passed by dynamic router
     */
    public function project(string $projectName): View
    {

        $project = $this->projectRepository->findBy('name', $projectName);

        if (!$project) {
            abort(404);
        }

        $project['header'] = $this->pageContentService
            ->getRenderedPartContent('header/projects', $project);
        $project['description'] = $this->pageContentService
            ->getRenderedPartContent('content/projects', $project);
        $project['part'] = 'portfolio';
        $data = [
            'item' => (object) $project,
            'name' => 'portfolio'
        ];

        foreach ($this->parts as $part) { 
            $data[$part] = $this->pageContentService->getRenderedPartContent($part, $project);
        }

        return view('pages.detail', $data);
    }

}