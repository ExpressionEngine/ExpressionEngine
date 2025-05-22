<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/file.html',
    'name' => 'File',
    'description' => '',
    'version' => '1.1.0',
    'namespace' => 'ExpressionEngine\Addons\File',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'file' => array(
            'compatibility' => 'file',
            'templateGenerator' => 'File',
            'use' => array(
                'MemberField'
            )
        )
    )
);
