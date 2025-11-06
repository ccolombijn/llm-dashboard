<?php

namespace App\Http\Controllers;

use App\Contracts\PageRepositoryInterface;
use App\Contracts\ContactRepositoryInterface;
use App\Mail\ContactFormMail;
use App\Services\PageContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContactRepositoryInterface $contactRepository,
        private PageContentService $pageContentService
    ) {}

    /**
     * Display the contact page.
     */
    public function show(): View
    {
        $page = $this->pageRepository->findBy('name', 'contact');
        if (!$page) abort(404);

        $contentData = [];
        $parts = $page['parts'] ?? config('page.default_parts');
        foreach ($parts as $part) {
            $contentData[$part] = $this->pageContentService->getRenderedPartContent($part, $page);
        }

        return view('pages.contact', [
            'page' => $page,
            'content' => $contentData,
            'parts' => $parts,
        ]);
    }

    /**
     * Handle the contact form submission.
     */
    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|min:2|string',
            'email' => 'required|email',
            'mobile' => 'required|digits:10',
            'message' => 'required|min:10'
        ]);

        $contactDetails = $this->contactRepository->getDetails();
        $recipientEmail = $contactDetails['email'] ?? config('mail.contact_form_recipient');

        Mail::to($recipientEmail)->send(new ContactFormMail($validated));

        return redirect()->route('contact.show')->with('success', 'Your email has been sent!');
    }

    /**
     * Handle file downloads from the public disk.
     */
    public function download(string $file): StreamedResponse
    {
        return Storage::download($file);
    }
}