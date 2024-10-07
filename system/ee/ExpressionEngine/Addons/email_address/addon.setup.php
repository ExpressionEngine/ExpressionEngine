<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/email-address.html',
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
