<?php

namespace App\Repositories;

use App\Contracts\PageRepositoryInterface;

class JsonPageRepository extends JsonRepository implements PageRepositoryInterface
{
    public function __construct()
    {
        parent::__construct('pages');
    }
}