<?php

$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

if (! defined('PRO_VAR_VERSION')) {
    define('PRO_VAR_VERSION', $addonJson->version);
}

return array(
    'name'           => $addonJson->name,
    'description'    => $addonJson->description,
    'version'        => $addonJson->version,
    'namespace'      => $addonJson->namespace,
    'author'         => 'ExpressionEngine',
    'author_url'     => 'https://expressionengine.com/',
    'docs_url'       => 'https://docs.expressionengine.com/latest/add-ons/overview.html',
    'settings_exist' => true
);
