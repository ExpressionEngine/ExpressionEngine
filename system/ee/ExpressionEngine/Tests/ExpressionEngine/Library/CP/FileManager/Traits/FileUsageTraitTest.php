<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\CP\FileManager\Traits;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';

use ExpressionEngine\Library\CP\FileManager\Traits\FileUsageTrait;
use PHPUnit\Framework\TestCase;

class FileUsageTraitTest extends TestCase
{
    public function testBareFiledirTokenReturnsNoReplacements(): void
    {
        $this->assertSame([], FileUsageTraitHarness::parse('{filedir_7}'));
    }

    public function testMixedContentWithBareFiledirTokenReturnsNoReplacements(): void
    {
        $this->assertSame([], FileUsageTraitHarness::parse('before {filedir_7} after'));
    }

    public function testGridLikeNestedPayloadWithBareFiledirTokenDoesNotThrow(): void
    {
        $payload = [
            'field_id_10' => [
                [
                    'row_id' => 'new_row_0',
                    'col_id_1' => '{filedir_7}',
                    'col_id_2' => 'Grid cell content',
                    'enabled' => true,
                ],
            ],
        ];

        $results = [];
        array_walk_recursive($payload, function ($item) use (&$results) {
            if (!is_string($item)) {
                return;
            }

            $results[] = FileUsageTraitHarness::parse($item);
        });

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertSame([], $result);
        }
    }

    public function testFluidLikeNestedPayloadWithBareFiledirTokenDoesNotThrow(): void
    {
        $payload = [
            'field_id_20' => [
                [
                    'field_20' => [
                        'field_id_33' => '{filedir_7}',
                        'field_id_34' => 'Fluid field content',
                    ],
                ],
            ],
        ];

        $results = [];
        array_walk_recursive($payload, function ($item) use (&$results) {
            if (!is_string($item)) {
                return;
            }

            $results[] = FileUsageTraitHarness::parse($item);
        });

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertSame([], $result);
        }
    }
}

class FileUsageTraitHarness
{
    use FileUsageTrait;

    public static function parse($data = '')
    {
        return static::getFileUsageReplacements($data);
    }
}
