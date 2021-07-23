<?php

    $command = 'update:prepare';

    $commandOptions = [
        'upgrade-ee' => 'Start the upgrade after moving files',
        'force-add-on-upgrade' => 'After upgrading EE, runs addon upgrades',
        'old-base-path:' => 'Absolute path of old site',
        'new-base-path:' => 'Absolute path of new site',
        'old-public-path:' => 'Absolute path of old site public path',
        'new-public-path:' => 'Absolute path of new site public path',
        'no-config-file' => 'Ignores the config file and doesn\'t check for it',
        'ee-version' => 'The current site ',
        'should-move-system-path' => 'Whether the upgrade process should move the old system folder to the new site',
        'old-system-path:' => 'Absolute path of old site system folder',
        'new-system-path:' => 'Absolute path of new site system folder',
        'should-move-template-path' => 'Whether the upgrade process should move the old template folder to the new site',
        'old-template-path:' => 'Absolute path of old site template folder',
        'new-template-path:' => 'Absolute path of new site template folder',
        'should-move-theme-path' => 'Whether the upgrade process should move the old theme folder to the new site',
        'old-theme-path:' => 'Absolute path of old site user theme folder',
        'new-theme-path:' => 'Absolute path of new site user theme folder',
        'run-preflight-hooks' => 'Whether the upgrade process should run defined preflight hooks',
        'run-postflight-hooks' => 'Whether the upgrade process should run defined postflight hooks',
        'temp-directory' => 'The directory we work magic in',
    ];

    $langStart = 'command_' . str_replace(':', '_', $command) . '_option_';

    $langArray = [];
    $newOptions = [];
    foreach ($commandOptions as $name => $val) {
        $newName = explode(',', $name);
        $newName = $newName[0];
        $newName = str_replace(':', '', $newName);
        $newName = str_replace('-', '_', $newName);

        $langEntry = $langStart . $newName;
        $newOptions[$name] = $langEntry;
        $langArray[$langEntry] = $val;
    }

    // Print command options:
    echo "    public \$commandOptions = [\n";
    foreach ($newOptions as $k => $v) {
        echo "        '$k' => '$v',\n";
    }
    echo "    ];\n\n\n\n";

    echo "    // $command options\n";
    foreach ($langArray as $k => $v) {
        echo "    '$k'        => '$v',\n";
    }

    // echo "<pre>";
    // var_dump($newOptions);
    // var_dump($langArray);
    // exit;
// var_dump($commandOptions);
// 'command_migrate_addon_option_' => '',

//         'command_migrate_addon_option_steps' => 'Specify the number of migrations to run',
//         'command_migrate_addon_option_everything' => 'Run all addn-on migrations',
//         'command_migrate_addon_option_all' => 'Run all addn-on migrations. Alias for --everything',
//         'command_migrate_addon_option_addon' => 'Run migration only for specified addon.',
