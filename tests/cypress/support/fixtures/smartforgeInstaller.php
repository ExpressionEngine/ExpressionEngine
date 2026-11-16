<?php

define('INSTALL_MODE', true);
require __DIR__ . '/bootstrap.php';

if (! defined('INSTALLER') || ! INSTALLER) {
    throw new RuntimeException('Expected the installer bootstrap');
}

$logger = ee()->logger;
ee()->load->database();
ee()->load->library('smartforge');

echo json_encode(['logger_preserved' => $logger === ee()->logger]);
