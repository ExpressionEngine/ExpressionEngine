<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/select.html',
    'name' => 'Select Dropdown',
    'description' => '',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\SelectDropdown',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'select' => array(
            'compatibility' => 'list',
            'use' => array(
                'MemberField'
            )
        )
    )
);

// EOF
