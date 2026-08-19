<?php

// Static file serving (CSS, JS, images)
Route::set('docs/media', 'guide-media(/<file>)', ['file' => '.+'])
    ->defaults([
        'controller' => 'Userguide',
        'action' => 'media',
        'file' => null,
    ]);

// API Browser, if enabled
if (Kohana::$config->load('userguide.api_browser') === true) {
    Route::set('docs/api', 'guide-api(/<class>)', ['class' => '[a-zA-Z0-9_]+'])
        ->defaults([
            'controller' => 'Userguide',
            'action' => 'api',
            'class' => null,
    ]);
}

// User guide pages, in modules
Route::set('docs/guide', 'guide(/<module>(/<page>))', ['page' => '.+'])
    ->defaults([
        'controller' => 'Userguide',
        'action' => 'docs',
        'module' => '',
    ]);
