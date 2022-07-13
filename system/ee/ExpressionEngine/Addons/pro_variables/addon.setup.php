<?php

/**
 * Pro Variables Add-On Setup file
 *
 * @package        pro_variables
 * @author         EEHarbor
 * @link           https://eeharbor.com/pro-variables
 * @copyright      Copyright (c) 2009-2022, EEHarbor
 */
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

if (! defined('PRO_VAR_VERSION')) {
    define('PRO_VAR_VERSION', $addonJson->version);
}

return array(
    'name'           => $addonJson->name,
    'description'    => $addonJson->description,
    'version'        => $addonJson->version,
    'namespace'      => $addonJson->namespace,
    'author'         => 'EEHarbor',
    'author_url'     => 'https://eeharbor.com/',
    'docs_url'       => 'https://eeharbor.com/pro-variables',
    'settings_exist' => true
);
