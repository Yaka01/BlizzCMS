<?php

use App\Controllers\Home;
use App\Controllers\News;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', [Home::class, 'index']);
$routes->get('lang/(:any)', [Home::class, 'lang']);

/**
 * News Routes
 */
$routes->get('news', [News::class, 'index']);
$routes->get('news/(:num)/(:any)', [News::class, 'view']);
