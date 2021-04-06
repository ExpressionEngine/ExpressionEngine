<?php

use Generator\AddonGenerator;

return [
    'author' => 'Packet Tide',
    'author_url' => 'https://packettide.com',
    'name' => 'Generator',
    'description' => 'Generate EE Addons right in EE',
    'version' => '1.0.0',
    'namespace' => 'Generator\\',
    'settings_exist' => false,
    'built_in' => true,
    // Advanced settings
    'services' => [
        'AddonGenerator' => function ($addon, array $data) {
            return new AddonGenerator($data);
        },
    ],
];
