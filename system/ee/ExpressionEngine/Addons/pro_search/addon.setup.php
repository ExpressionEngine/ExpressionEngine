<?php

/**
 * Pro Search config file
 *
 * @package        pro_search
 * @author         Tom Jaeger - EEHarbor
 * @link           https://eeharbor.com/pro-search
 * @copyright      Copyright (c) 2020, EEHarbor
 */
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

return array(
    'name'           => $addonJson->name,
    'description'    => $addonJson->description,
    'version'        => $addonJson->version,
    'namespace'      => $addonJson->namespace,
    'author'         => 'EEHarbor',
    'author_url'     => 'https://eeharbor.com/',
    'docs_url'       => 'https://eeharbor.com/pro-search',
    'settings_exist' => true
);
