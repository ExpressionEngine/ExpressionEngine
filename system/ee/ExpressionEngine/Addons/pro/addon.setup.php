<?php

return array(
    'author'         => 'Packet Tide, LLC',
    'author_url'     => 'https://packettide.com/',
    'name'           => 'ExpressionEngine Pro',
    'description'    => 'Super charge EE with more power, features, and flexibility!',
    'version'        => '2.1.0',
    'namespace'      => 'ExpressionEngine\Addons\Pro',
    'settings_exist' => true,
    'built_in' => true,
    'requires'       => [
        'php'   => '7.2.5',
        'ee'    => '7.0.0-rc.1'
    ],
    'cookies.functionality' => [
        'frontedit',
    ],
    'cookie_settings' => [
        'frontedit' => [
            'description' => 'lang:cookie_frontedit_desc'
        ],
    ],
    'models'       => [
        'DashboardWidget' => 'Model\Dashboard\DashboardWidget',
        'DashboardLayout' => 'Model\Dashboard\DashboardLayout',
        'Dock' => 'Model\Dock\Dock',
        'Prolet' => 'Model\Prolet\Prolet'
    ],
    'services'       => [
        'Cookie' => 'Service\Cookie\Cookie',
        'Access' => 'Service\Access\Access',
        'Prolet' => 'Service\Prolet\Prolet',
        'FrontEdit' => 'Service\FrontEdit\FrontEdit',
        'License' => 'Service\License\License'
    ],
    'services.singletons' => [
        'Addon' => function ($ee) {
            return new ExpressionEngine\Addons\Pro\Service\Addon\Factory($ee->make('App'));
        },
        'Dock' => function ($ee) {
            return new ExpressionEngine\Addons\Pro\Service\Dock\DockFactory();
        },
        'Mfa' => function ($ee) {
            return new ExpressionEngine\Addons\Pro\Service\Mfa\Mfa();
        }
    ]
);
