<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/multiselect.html',
    'name' => 'Multi Select',
    'description' => '',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\MultiSelect',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'multi_select' => array(
            'compatibility' => 'list',
            'use' => array(
                'MemberField'
            )
        )
    )
);
