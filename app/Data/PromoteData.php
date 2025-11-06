<?php

namespace App\Data;

class PromoteData
{
    /**
     * @param array $items The array of all items
     */
    public function __construct(public readonly array $items)
    {
        // This uses PHP 8's constructor property promotion.
        // It automatically creates and assigns the public 'items' property.
    }
}