<?php

namespace App\Repositories;

use App\Contracts\ProjectRepositoryInterface; 

class JsonProjectRepository extends JsonRepository implements ProjectRepositoryInterface
{
    public function __construct()
    {
        parent::__construct('projects',[
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'intro' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:255',
        ]);
    }
}