<?php

    $addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

    if (!defined('STRUCTURE_VERSION')) {
        define('STRUCTURE_VERSION', $addonJson->version);
    }

    return array(
        'name'              => $addonJson->name,
        'version'           => $addonJson->version,
        'description'       => $addonJson->description,
        'namespace'         => $addonJson->namespace,
        'author'            => 'ExpressionEngine',
        'author_url'        => 'https://expressionengine.com/',
        'docs_url'          => 'https://eeharbor.com/structure/documentation',
        'settings_exist'    => true,
        'fieldtypes' => array(
            'structure' => array(
                'name' => 'Structure'
            )
        )
    );
