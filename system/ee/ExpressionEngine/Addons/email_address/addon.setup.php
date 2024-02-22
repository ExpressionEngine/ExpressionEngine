<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'Email Address',
    'description' => 'Email Address Field',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\EmailAddress',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'email_address' => array(
            'compatibility' => 'text',
            'use' => array(
                'MemberField'
            )
        )
    )
);
