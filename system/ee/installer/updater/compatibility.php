<?php

/**
 * Supply new helpers to CLI updates that retain the old application bootstrap.
 * Keep this fallback consistent with Boot/boot.common.php. The downloaded
 * updater must be self-contained until the next application request.
 */
if (! function_exists('file_exists_safe')) {
    function file_exists_safe($path)
    {
        return strpos((string) $path, "\0") === false && file_exists($path);
    }
}
