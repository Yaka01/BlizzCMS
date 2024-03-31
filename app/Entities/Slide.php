<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Slide extends Entity
{
    protected $attributes = [
        'id'         => null,
        'title'      => null,
        'description' => null,
        'type'       => 'image',
        'path'       => null,
        'sort'       => 0,
    ];

    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [];
}
