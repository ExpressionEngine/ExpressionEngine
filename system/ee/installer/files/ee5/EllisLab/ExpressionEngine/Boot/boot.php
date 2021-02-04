<?php

if (!defined('EESELF') && defined('SELF')) {
    define('EESELF', SELF);
}

// Check to see if we have write access to the file.
if (!empty($_SERVER['SCRIPT_FILENAME']) && is_file(realpath($_SERVER['SCRIPT_FILENAME'])) && is_writable(realpath($_SERVER['SCRIPT_FILENAME']))) {
    $contents = file_get_contents(realpath($_SERVER['SCRIPT_FILENAME']));
    $contents = str_replace('ee/EllisLab/ExpressionEngine/Boot/boot.php', 'ee/ExpressionEngine/Boot/boot.php', $contents);
    file_put_contents(realpath($_SERVER['SCRIPT_FILENAME']), $contents);
    header("Refresh:1");
}

require_once SYSPATH . '/ee/ExpressionEngine/Boot/boot.php';
