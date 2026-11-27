<?php

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('page_url')) {
    function page_url(string $page, array $params = []): string {
        return 'layout.php?' . http_build_query(
            ['page' => $page] + $params
        );
    }
}
