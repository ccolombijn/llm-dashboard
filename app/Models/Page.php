<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model 
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'route',
        'controller',
        'method',
        'view',
        'parts',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'parts' => 'array', // Automatically cast the 'parts' JSON column to an array
    ];
}