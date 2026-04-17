<?php
// Global helper functions for EE_Template tests

if (!function_exists('show_404')) {
    function show_404($uri = '') {
        throw new \Exception('404 redirect requested');
    }
}

if (!function_exists('show_error')) {
    function show_error($message = '', $status_code = 500, $heading = 'An Error Was Encountered') {
        throw new \RuntimeException((string) $message);
    }
}

if (!function_exists('lang')) {
    function lang($line, $for = '', $attributes = []) {
        return $line;
    }
}

if (!function_exists('trim_slashes')) {
    function trim_slashes($str) {
        return trim($str, '/');
    }
}
