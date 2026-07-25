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

class FileFtUpdateTest extends FileFtTestBase
{
    /**
     * Provide representative upgrade-version payloads for the no-op updater.
     *
     * @return array<string, array{0: mixed}>
     */
    public function updateVersionProvider()
    {
        return [
            'semantic version' => ['1.1.0'],
            'empty string' => [''],
            'null' => [null],
            'numeric' => [7.5],
            'array payload' => [['from' => '7.5.0', 'to' => '7.5.1']],
            'release candidate' => ['8.0.0-rc.1'],
        ];
    }

    /**
     * Assert update() always reports success for representative version inputs.
     *
     * @dataProvider updateVersionProvider
     * @param mixed $version
     * @return void
     */
    public function testUpdateAlwaysReturnsBooleanTrue($version)
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->update($version);

        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    /**
     * Assert update() leaves field context and test doubles untouched.
     *
     * @return void
     */
    public function testUpdateDoesNotMutateFieldContextOrTouchCollaborators()
    {
        $fieldtype = $this->makeFieldtype([
            'allowed_directories' => [2, 9],
            'field_content_type' => 'image',
        ], 41, 'hero_asset');
        $expectedSettings = $fieldtype->settings;
        $expectedContentId = $this->fieldtypeContentId($fieldtype);
        $expectedFieldName = $this->fieldtypeName($fieldtype);
        $libraries = $this->loadRecorder->libraries;
        $models = $this->loadRecorder->models;
        $fileFieldCalls = $this->fileFieldMock->calls;

        $this->assertTrue($fieldtype->update('7.5.0'));
        $this->assertSame($expectedSettings, $fieldtype->settings);
        $this->assertSame($expectedContentId, $this->fieldtypeContentId($fieldtype));
        $this->assertSame($expectedFieldName, $this->fieldtypeName($fieldtype));
        $this->assertSame($libraries, $this->loadRecorder->libraries);
        $this->assertSame($models, $this->loadRecorder->models);
        $this->assertSame($fileFieldCalls, $this->fileFieldMock->calls);
    }
}
