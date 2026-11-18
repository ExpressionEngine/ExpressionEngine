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

class FileFtSaveSettingsTest extends FileFtTestBase
{
    /**
     * Assert save_settings() merges provided values with defaults and strips unknown keys.
     *
     * @return void
     */
    public function testSaveSettingsMergesDefaultsAndStripsUnknownKeys()
    {
        $fieldtype = $this->makeFieldtype();
        $payload = [
            'field_content_type' => 'image',
            'allowed_directories' => [3, 7],
            'show_existing' => 'y',
            'num_existing' => '12',
            'field_fmt' => 'xhtml',
            'unexpected' => 'discard-me',
        ];

        $result = $fieldtype->save_settings($payload);

        $this->assertSame([
            'field_content_type' => 'image',
            'allowed_directories' => [3, 7],
            'show_existing' => 'y',
            'num_existing' => '12',
            'field_fmt' => 'xhtml',
        ], $result);
        $this->assertSame([], $this->requestMock->postCalls);
    }

    /**
     * Assert save_settings() preserves explicit falsy values from non-empty input arrays.
     *
     * @return void
     */
    public function testSaveSettingsPreservesExplicitFalsyValuesFromInput()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->save_settings([
            'field_content_type' => '',
            'allowed_directories' => [],
            'show_existing' => '',
            'num_existing' => 0,
            'field_fmt' => '0',
        ]);

        $this->assertSame([
            'field_content_type' => '',
            'allowed_directories' => [],
            'show_existing' => '',
            'num_existing' => 0,
            'field_fmt' => '0',
        ], $result);
        $this->assertSame([], $this->requestMock->postCalls);
    }

    /**
     * Assert save_settings() reads each expected POST setting when no payload is provided.
     *
     * @return void
     */
    public function testSaveSettingsUsesRequestPostValuesWhenInputIsEmpty()
    {
        $fieldtype = $this->makeFieldtype();
        $request = $this->setRequestPostValues([
            'field_content_type' => 'audio',
            'allowed_directories' => [4, 9],
            'show_existing' => 'y',
            'num_existing' => 8,
            'field_fmt' => 'br',
        ]);

        $result = $fieldtype->save_settings([]);

        $this->assertSame([
            'field_content_type' => 'audio',
            'allowed_directories' => [4, 9],
            'show_existing' => 'y',
            'num_existing' => 8,
            'field_fmt' => 'br',
        ], $result);
        $this->assertSame($this->expectedRequestPostCalls(), $request->postCalls);
    }

    /**
     * Assert save_settings() falls back to per-setting defaults for missing POST values.
     *
     * @return void
     */
    public function testSaveSettingsFallsBackToDefaultsForMissingPostValues()
    {
        $fieldtype = $this->makeFieldtype();
        $request = $this->setRequestPostValues([
            'allowed_directories' => [11],
            'num_existing' => 0,
        ]);
        $libraries = $this->loadRecorder->libraries;
        $models = $this->loadRecorder->models;
        $fileFieldCalls = $this->fileFieldMock->calls;

        $result = $fieldtype->save_settings([]);

        $this->assertSame([
            'field_content_type' => 'all',
            'allowed_directories' => [11],
            'show_existing' => '',
            'num_existing' => 0,
            'field_fmt' => 'none',
        ], $result);
        $this->assertSame($this->expectedRequestPostCalls(), $request->postCalls);
        $this->assertSame($libraries, $this->loadRecorder->libraries);
        $this->assertSame($models, $this->loadRecorder->models);
        $this->assertSame($fileFieldCalls, $this->fileFieldMock->calls);
    }

    /**
     * Return the POST keys and defaults save_settings() should request.
     *
     * @return array<int, array{key: string, default: mixed}>
     */
    private function expectedRequestPostCalls()
    {
        return [
            ['key' => 'field_content_type', 'default' => 'all'],
            ['key' => 'allowed_directories', 'default' => ''],
            ['key' => 'show_existing', 'default' => ''],
            ['key' => 'num_existing', 'default' => 0],
            ['key' => 'field_fmt', 'default' => 'none'],
        ];
    }
}
