<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

class SettingsController extends Controller
{
    protected $content;
    /**
     * 
     */
    public function __construct()
    {
        $this->content = app('content.data');
    }
    /**
     * 
     */
    public function index()
    {
        return view('admin.settings',[
            'content' => $this->content
        ]);
    }
    /**
     * @todo Implement validation (!)
     */
    public function update(Request $request)
    {
        $submittedData = $request->input('content', []);
        $newData = array_replace_recursive($this->content, $submittedData);
        $path = storage_path('app/public/json/content.json');
        File::put($path, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        Cache::forget('content.json.data');
        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully!');
    }
}
