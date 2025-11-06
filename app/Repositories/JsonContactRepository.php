<?php

namespace App\Repositories;

use App\Contracts\ContactRepositoryInterface;

class JsonContactRepository extends JsonRepository implements ContactRepositoryInterface
{
    public function __construct()
    {
        parent::__construct('contact');
    }
    /**
     * 
     */
    public function getDetails(): array
    {
        return $this->all();
    }
}