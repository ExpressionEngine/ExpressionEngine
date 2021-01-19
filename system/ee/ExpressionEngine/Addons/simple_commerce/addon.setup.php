<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'Simple Commerce',
    'description' => 'Easily integrate EE with PayPal',
    'version' => '2.2.1',
    'namespace' => 'ExpressionEngine\Addons\SimpleCommerce',
    'settings_exist' => true,

    'models' => array(
        'EmailTemplate' => 'Model\EmailTemplate',
        'Item' => 'Model\Item',
        'Purchase' => 'Model\Purchase'
    ),

    'models.dependencies' => array(
        'Item' => array(
            'ee:ChannelEntry'
        ),
        'Purchase' => array(
            'ee:Member'
        )
    )
);

// EOF
