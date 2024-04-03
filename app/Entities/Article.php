<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Article extends Entity
{
    protected $attributes = [
        'id' => null,
        'title' => null,
        'summary' => null,
        'content' => null,
        'slug' => null,
        'image' => null,
        'comments' => null,
        'views' => null,
        'meta_description' => null,
        'meta_robots' => null,
        'discuss' => null
    ];

    protected $datamap = [
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
        'deletedAt' => 'deleted_at',
        'metaDescription' => 'meta_description',
        'metaRobots' => 'meta_robots',
    ];
    protected $dates   = ['created_at', 'updated_at', 'published_at'];
    protected $casts   = [];
}
