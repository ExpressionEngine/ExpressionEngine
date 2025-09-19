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
    'prefix' => 'ExpressionEngine\\Dependency', // string|null
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
    ],                                      // Finder[]
    'patchers' => [],                       // callable[]
    // 'exclude-files' => [],      // list<string>
    // 'exclude-namespaces' => [], // list<string|regex>
    // 'exclude-constants' => [],  // list<string|regex>
    // 'exclude-classes' => [],    // list<string|regex>
    // 'exclude-functions' => [],  // list<string|regex>

    // 'expose-global-constants' => true,   // bool
    // 'expose-global-classes' => true,     // bool
    // 'expose-global-functions' => true,   // bool

    // 'expose-namespaces' => [], // list<string|regex>
    // 'expose-constants' => [],  // list<string|regex>
    // 'expose-classes' => [],    // list<string|regex>
    // 'expose-functions' => [],  // list<string|regex>
];
