<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'Comment',
    'description' => '',
    'version' => '2.3.3',
    'namespace' => 'ExpressionEngine\Addons\Comment',
    'settings_exist' => false,
    'built_in' => true,
    'cookies.functionality' => [
        'my_email',
        'my_location',
        'my_name',
        'my_url',
        'notify_me',
        'save_info'
    ],
    'cookie_settings' => [
        'my_email' => [
            'description' => 'lang:cookie_my_email_desc'
        ],
        'my_location' => [
            'description' => 'lang:cookie_my_location_desc'
        ],
        'my_name' => [
            'description' => 'lang:cookie_my_name_desc'
        ],
        'my_url' => [
            'description' => 'lang:cookie_my_url_desc'
        ],
        'notify_me' => [
            'description' => 'lang:cookie_notify_me_desc'
        ],
        'save_info' => [
            'description' => 'lang:cookie_save_info_desc'
        ],
    ],
);
