<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'Value & Range Sliders',
    'description' => '',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\SliderInput',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'slider' => array(
            'name' => 'Value Slider',
            'compatibility' => 'text',
            'use' => array(
                'MemberField'
            )
        ),
        'range_slider' => array(
            'name' => 'Range Slider',
            'compatibility' => 'text',
            'use' => array(
                'MemberField'
            )
        )
    )
);
