<?php

return [
    /**
     * Host names the application answers to, as regex patterns (comma
     * separated in APP_TRUSTED_HOSTS). URL::site() with a protocol refuses
     * other hosts. Escape dots: example\.org, .*\.example\.org
     */
    'trusted_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('APP_TRUSTED_HOSTS', ''))))),
];
