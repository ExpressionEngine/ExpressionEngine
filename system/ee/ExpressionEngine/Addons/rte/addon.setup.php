<?php

return array(
    'name' => "Rich Text Editor",
    'description' => "",
    'version' => "2.2.0",
    'namespace' => 'ExpressionEngine\Addons\Rte',
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'settings_exist' => true,
    'services' => array(
        'CkeditorService' => 'Service\CkeditorService',
        'RedactorService' => 'Service\RedactorService',
        'RedactorXService' => 'Service\RedactorXService',
    ),
    'models' => array(
        'Toolset' => 'Model\Toolset'
    ),
    'fieldtypes' => array(
        'rte' => array(
            'compatibility' => 'text',
            'use' => array(
                'MemberField'
            )
        )
    )
);
