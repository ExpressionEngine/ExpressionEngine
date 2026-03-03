<?php
// Global helper functions for EE_Template tests

if (!function_exists('show_404')) {
    function show_404($uri = '') {
        throw new \Exception('404 redirect requested');
    }
}

if (!function_exists('trim_slashes')) {
    function trim_slashes($str) {
        return trim($str, '/');
    }
}

