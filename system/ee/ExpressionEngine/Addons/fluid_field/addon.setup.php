<?php

use ExpressionEngine\Addons\FluidField\Service\Tag;

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/fluid.html',
    'name' => 'Fluid',
    'description' => 'Fluid Fields',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\FluidField',
    'settings_exist' => false,
    'built_in' => true,

    'fieldtypes' => array(
        'fluid_field' => array(
            'name' => 'Fluid',
            'templateGenerator' => 'Fluid'
        )
    ),

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
