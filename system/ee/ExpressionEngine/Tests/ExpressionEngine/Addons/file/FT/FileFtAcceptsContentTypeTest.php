<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/FileFtTestBase.php';

class FileFtAcceptsContentTypeTest extends FileFtTestBase
{
    /**
     * Assert file fields accept every supported content placement context.
     *
     * @dataProvider acceptsContentTypeProvider
     * @param string $contentType
     * @return void
     */
    public function testAcceptsContentTypeAlwaysReturnsTrue($contentType)
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->accepts_content_type($contentType);

        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    /**
     * Assert accepts_content_type() does not mutate the field context.
     *
     * @return void
     */
    public function testAcceptsContentTypeDoesNotMutateFieldContext()
    {
        $fieldtype = $this->makeFieldtype([
            'allowed_directories' => [3, 7],
            'field_content_type' => 'image',
        ], 24, 'hero_asset');
        $expectedSettings = $fieldtype->settings;
        $expectedContentId = $fieldtype->content_id;
        $expectedFieldName = $fieldtype->field_name;

        $this->assertTrue($fieldtype->accepts_content_type('blocks/7'));
        $this->assertSame($expectedSettings, $fieldtype->settings);
        $this->assertSame($expectedContentId, $fieldtype->content_id);
        $this->assertSame($expectedFieldName, $fieldtype->field_name);
    }

    /**
     * Provide representative content contexts and boundary inputs.
     *
     * @return array<string, array{0: string}>
     */
    public function acceptsContentTypeProvider()
    {
        return [
            'empty string' => [''],
            'channel entry' => ['channel'],
            'grid row' => ['grid'],
            'fluid field' => ['fluid_field'],
            'blocks row' => ['blocks/7'],
            'member field' => ['member'],
            'custom context' => ['custom_context'],
        ];
    }
}
