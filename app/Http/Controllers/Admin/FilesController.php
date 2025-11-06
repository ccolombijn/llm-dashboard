<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileManagerInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class FilesController extends AdminController
{
    public function __construct(private FileManagerInterface $fileManager) {}

    /**
     * Display a listing of files and folders.
     * @param string $path
     * @return View
     */
    public function index(string $path = ''): View
    {
        $contents = $this->fileManager->listContents($path);

        // Logic to determine if we are viewing a file or a folder
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (!empty($path) && !empty($extension)) {
            $fileDetails = $this->fileManager->getFileDetails($path);
            if (!$fileDetails) abort(404);
            return view('admin.files.view', ['file' => $fileDetails]);
        }

        return view('admin.files.index', [
            'files' => $contents,
            'path' => $path
        ]);
    }

    /**
     * Show the form for uploading files.
     * @param string $path
     * @return View
     */
    public function upload(string $path = ''): View
    {
        return view('admin.files.upload', ['path' => $path ?: '.']);
    }


    /**
     * Store uploaded files.
     * @param Request $request
     * @param string $path
     * @return RedirectResponse
     */
    public function store(Request $request, string $path = ''): RedirectResponse
    {
        $request->validate([
            'files_to_upload'   => 'sometimes|required|array',
            'files_to_upload.*' => 'file|mimes:jpg,jpeg,png,gif,svg,webp|max:5120',
        ]);

        if ($request->hasFile('files_to_upload')) {
            $this->fileManager->store($path, $request->file('files_to_upload'));
        }

        return redirect()->route('admin.files.view', ['path' => $path])
            ->with('success', 'Files uploaded and optimized successfully!');
    }

    /**
     * Remove the specified file or folder.
     * @param string $path
     * @return RedirectResponse
     */
    public function destroy(string $path): RedirectResponse
    {
        $parentPath = dirname($path);
        $redirectPath = ($parentPath === '.' || $parentPath === '/') ? '' : $parentPath;
        if ($this->fileManager->delete($path)) {
            return redirect()->route('admin.files.view', ['path' => $redirectPath])
                ->with('success', 'File or folder removed successfully!');
        } else {
            return redirect()->route('admin.files.view', ['path' => $redirectPath])
                ->with('error', 'File or folder <b>' . $path . '</b> could not be removed');
        }
    }
}
