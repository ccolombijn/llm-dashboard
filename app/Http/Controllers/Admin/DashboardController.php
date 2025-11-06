<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController as Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    protected $projects;

    public function __construct(array $projects)
    {
        //parent::__construct($content);
        $this->projects = $projects;
    }

    public function index()
    {
        return view('admin.dashboard');
    }
}