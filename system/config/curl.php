<?php

use Kohana\Foundation\Application;

return [
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Kohana v' . Application::VERSION . ' +https://kohana.top/)',
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HEADER => false,
];
