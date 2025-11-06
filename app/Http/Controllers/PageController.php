<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;
use \Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __construct(
        private PageContentService $pageContentService
    ) {}
    /**
     * Returns default page view
     */
    public function show(array $page): View
    {

        if (!$page) {
            abort(404);
        }

        $content = [];
        $parts = $page['parts'] ?? config('page.default_parts');
        
        foreach ($parts as $part) { 
            $content[$part] = $this->pageContentService->getRenderedPartContent($part, $page);

        }

        $view = $page['view'] ?? 'pages.default';
        
        return view($view, [
            'page' => $page,
            'content' => $content,
            'parts' => $parts, 
        ]);
    }
}
