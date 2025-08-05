<?php

return array(
    'name' => "Rich Text Editor",
    'description' => "",
    'version' => "2.3.0",
    'namespace' => 'ExpressionEngine\Addons\Rte',
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/rte.html',
    'settings_exist' => true,
    'services' => array(
        'CkeditorService' => 'Service\CkeditorService',
        'RedactorService' => 'Service\RedactorService',
        'RedactorXService' => 'Service\RedactorXService',
        'RedactorYService' => 'Service\RedactorYService',
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
