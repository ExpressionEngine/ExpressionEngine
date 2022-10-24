<?php

/**
 * PHP-Scoper configuration file.
 *
 * @package   ExpressionEngine\ExpressionEngine
 * @copyright 2022 PacketTide LLC
 * @license
 * @link      https://expressionengine.com
 */

use Isolated\Symfony\Component\Finder\Finder;

return [
    'prefix' => 'ExpressionEngine\\Dependency',                       // string|null
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'bin',
                'bamarni',
                'doc',
                'docs',
                'test',
                'Test',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('vendor'),
    ],                        // Finder[]
    'patchers' => [],                       // callable[]
    // 'files-whitelist' => [],                // string[]
    'whitelist' => [],                      // string[]
    #'expose-global-constants' => true,   // bool
    #'expose-global-classes' => true,     // bool
    #'expose-global-functions' => true,   // bool
    #'exclude-constants' => [],             // string[]
    #'exclude-classes' => [],               // string[]
    #'exclude-functions' => [],             // string[]
];
