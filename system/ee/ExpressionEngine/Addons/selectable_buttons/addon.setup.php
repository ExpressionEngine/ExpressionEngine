<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/selectable-buttons.html',
    'name' => 'Selectable Buttons',
    'description' => '',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\SelectableButtons',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'selectable_buttons' => array(
            'compatibility' => 'list',
            'use' => array(
                'MemberField'
            )
        )
    )
);
