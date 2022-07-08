<?php

/**
 * Low Variables Add-On Setup file
 *
 * @package        low_variables
 * @author         Tom Jaeger - EEHarbor
 * @link           https://eeharbor.com/low-variables
 * @copyright      Copyright (c) 2009-2020, Low
 */
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

if (! defined('LOW_VAR_VERSION')) {
    define('LOW_VAR_VERSION', $addonJson->version);
}

return array(
    'name'           => $addonJson->name,
    'description'    => $addonJson->description,
    'version'        => $addonJson->version,
    'namespace'      => $addonJson->namespace,
    'author'         => 'EEHarbor',
    'author_url'     => 'https://eeharbor.com/',
    'docs_url'       => 'https://eeharbor.com/low-variables',
    'settings_exist' => true
);
