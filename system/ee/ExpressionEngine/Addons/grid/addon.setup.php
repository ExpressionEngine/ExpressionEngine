<?php

return [
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/grid.html',
    'name' => 'Grid',
    'description' => '',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\Grid',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => [
        'grid' => [
            'name' => 'Grid',
            'templateGenerator' => 'Grid',
            'compatibility' => 'grid'
        ],
        'file_grid' => [
            'name' => 'File Grid',
            'templateGenerator' => 'Grid',
            'compatibility' => 'file_grid'
        ]
    ],
    'models' => [
        'GridColumn' => 'Model\GridColumn'
    ],
    'models.dependencies' => [
        'GridColumn' => [
            'ee:ChannelField'
        ]
    ]
];
