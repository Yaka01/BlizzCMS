<?php

namespace App\Models;

use App\Entities\Article;
use CodeIgniter\Model;

class News extends Model
{
    protected $table            = 'news';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Article::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields = [
        'id',
        'title',
        'summary',
        'content',
        'slug',
        'image',
        'comments',
        'views',
        'meta_description',
        'meta_robots',
        'discuss'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
