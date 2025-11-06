<?php

namespace App\Repositories;

use App\Contracts\ApplicationRepositoryInterface;

class JsonApplicationRepository extends JsonRepository implements ApplicationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct('applications');
    }
}
