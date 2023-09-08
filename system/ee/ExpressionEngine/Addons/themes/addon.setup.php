<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'Themes',
    'description' => '',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\Themes',
    'settings_exist' => false,
    'built_in' => true,
    'templateThemes' => array(
        'tailwind' => array( //key should match the folder name
            'name' => 'Tailwind', // name for selection in the UI
            'engine' => 'native', // template engine
        ),
        'tailwind-twig' => array(
            'name' => 'Tailwind Twig',
            'engine' => 'twig',
        )
    )
);
