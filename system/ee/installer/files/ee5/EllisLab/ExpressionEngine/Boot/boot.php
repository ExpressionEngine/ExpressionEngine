<?php

if (!defined('EESELF') && defined('SELF')) {
    define('EESELF', SELF);
}

// Check to see if we have write access to the file.
if (!empty($_SERVER['SCRIPT_FILENAME']) && is_file(realpath($_SERVER['SCRIPT_FILENAME'])) && is_writable(realpath($_SERVER['SCRIPT_FILENAME']))) {
    $contents = file_get_contents(realpath($_SERVER['SCRIPT_FILENAME']));
    if ($contents !== false) {
        $contents = str_replace('ee/EllisLab/ExpressionEngine/Boot/boot.php', 'ee/ExpressionEngine/Boot/boot.php', $contents);
        $fileWritten = file_put_contents(realpath($_SERVER['SCRIPT_FILENAME']), $contents);
    }
}

if (!isset($fileWritten) || $fileWritten === false) {
    define('ELLISLAB_STILL_HERE', true);
}

require_once SYSPATH . '/ee/ExpressionEngine/Boot/boot.php';
