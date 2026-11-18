<?php

return [
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'Cypress CLI Update',
    'description' => 'CLI update regression fixture',
    'version' => '2.0.0',
    'namespace' => 'Cypress\\CliUpdate',
    'settings_exist' => false,
    'plugin.typography' => true,
    'commands' => [
        'cypress:delete-field' => 'Cypress\\CliUpdate\\CommandDeleteField',
    ],
];
