<?php

use ExpressionEngine\Addons\FluidField\Service\Tag;

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'Fluid',
    'description' => 'Fluid Fields',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\FluidField',
    'settings_exist' => false,
    'built_in' => true,

    'services' => array(
        'Tag' => function ($ee, $tagdata) {
            return new Tag($tagdata, ee()->functions, ee()->api_channel_fields, $ee->make('ee:Variables/Parser'));
        }
    ),

    'models' => array(
        'FluidField' => 'Model\FluidField',
    ),

    'models.dependencies' => array(
        'FluidField' => array(
            'ee:ChannelEntry',
            'ee:ChannelField'
        ),
    )

);
