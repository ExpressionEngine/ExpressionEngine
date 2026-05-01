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

class FileFtGetTableColumnConfigTest extends FileFtTestBase
{
    /**
     * Assert getTableColumnConfig() disables table encoding for file markup.
     *
     * @return void
     */
    public function testGetTableColumnConfigReturnsEncodeFalse()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame(['encode' => false], $fieldtype->getTableColumnConfig());
    }

    /**
     * Assert getTableColumnConfig() does not mutate field context or touch collaborators.
     *
     * @return void
     */
    public function testGetTableColumnConfigDoesNotMutateFieldContextOrTouchCollaborators()
    {
        $fieldtype = $this->makeFieldtype([
            'allowed_directories' => [4, 8],
            'field_content_type' => 'image',
        ], 13, 'hero_asset');
        $expectedSettings = $fieldtype->settings;
        $expectedContentId = $fieldtype->content_id;
        $expectedFieldName = $fieldtype->field_name;
        $libraries = $this->loadRecorder->libraries;
        $models = $this->loadRecorder->models;
        $fileFieldCalls = $this->fileFieldMock->calls;

        $this->assertSame(['encode' => false], $fieldtype->getTableColumnConfig());
        $this->assertSame($expectedSettings, $fieldtype->settings);
        $this->assertSame($expectedContentId, $fieldtype->content_id);
        $this->assertSame($expectedFieldName, $fieldtype->field_name);
        $this->assertSame($libraries, $this->loadRecorder->libraries);
        $this->assertSame($models, $this->loadRecorder->models);
        $this->assertSame($fileFieldCalls, $this->fileFieldMock->calls);
    }
}
