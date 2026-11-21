<?php

// Constants defined by public/index.php at runtime
define('DOCROOT', __DIR__ . '/public/');
define('APPPATH', __DIR__ . '/application/');
define('MODPATH', __DIR__ . '/modules/');
define('SYSPATH', __DIR__ . '/system/');
define('VENDOR_PATH', __DIR__ . '/vendor/');
define('KOHANA_START_TIME', microtime(true));
define('KOHANA_START_MEMORY', memory_get_usage());
