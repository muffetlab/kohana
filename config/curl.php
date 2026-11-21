<?php

use Kohana\Foundation\Application;

/**
 * Default cURL options of the external request client (Request_Client_Curl).
 */
return [
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Kohana v' . Application::VERSION . ' +https://kohana.top/)',
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => (int) env('HTTP_CLIENT_TIMEOUT', 5),
    CURLOPT_HEADER => false,
];
