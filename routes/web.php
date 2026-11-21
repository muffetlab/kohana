<?php

use Kohana\Routing\Route;

/**
 * Routes are matched in the order they are defined. Each route needs a name,
 * a URI pattern and defaults; the controller resolves to
 * App\Http\Controllers\{Controller}Controller.
 */
Route::set('default', '(<controller>(/<action>(/<id>)))')
    ->defaults([
        'controller' => 'welcome',
        'action' => 'index',
    ]);
