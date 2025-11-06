<?php

namespace App\Contracts;

interface ContactRepositoryInterface extends RepositoryInterface
{
    public function getDetails(): array;
}