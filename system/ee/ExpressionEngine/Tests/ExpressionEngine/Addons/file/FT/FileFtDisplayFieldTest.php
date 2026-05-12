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

class FileFtDisplayFieldSpy extends File_ft
{
    /** @var int */
    public $frontendJsCalls = 0;

    /**
     * Record front-end JavaScript loading without executing the helper body.
     *
     * @return void
     */
    protected function _frontend_js()
    {
        $this->frontendJsCalls++;
    }
}

class FileFtVarDisplayFieldSpy extends File_ft
{
    /** @var array<int, mixed> */
    public $displayFieldCalls = [];

    /** @var string */
    public $displayFieldReturn = '<variables field>';

    /**
     * Capture wrapper delegation without exercising display_field() internals.
     *
     * @param mixed $data
     * @return string
     */
    public function display_field($data)
    {
        $this->displayFieldCalls[] = $data;

        return $this->displayFieldReturn;
    }
}

class FileFtDisplayFieldTest extends FileFtTestBase
{
    /**
     * Assert control-panel requests use the drag-and-drop picker contract.
     *
     * @return void
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDisplayFieldUsesDragAndDropPickerInControlPanel()
    {
        define('REQ', 'CP');

        $fieldtype = $this->makeFieldtype([
            'allowed_directories' => [3, 8],
            'field_content_type' => 'images',
            'num_existing' => 4,
            'show_existing' => 'y',
        ], 0, 'hero_image');
        $this->fileFieldMock->dragAndDropReturn = '<cp picker>';

        $result = $fieldtype->display_field('{filedir_3}banner.png');

        $this->assertSame('<cp picker>', $result);
        $this->assertSame([
            [
                'file.publishCreateUrl' => 'https://example.com/admin.php?/cp/files/file/view/###&modal_form=y',
            ],
        ], $this->javascriptMock->globals);
        $this->assertSame([
            [
                'path' => 'files/file/view/###',
                'params' => ['modal_form' => 'y'],
            ],
        ], $this->cpUrlFactory->makeCalls);
        $this->assertSame(1, $this->cpUrlFactory->lastCompiledUrl->compileCalls);
        $this->assertSame([
            [
                'file' => [
                    'cp/publish/entry-list',
                ],
            ],
        ], $this->cpMock->scripts);
        $this->assertSame([
            [
                'field_name' => 'hero_image',
                'data' => '{filedir_3}banner.png',
                'allowed_file_dirs' => [3, 8],
                'content_type' => 'images',
            ],
        ], $this->fileFieldMock->dragAndDropCalls);
        $this->assertSame([], $this->fileFieldMock->fieldCalls);
    }

    /**
     * Assert front-end requests fall back to default picker settings.
     *
     * @return void
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDisplayFieldUsesFrontendPickerDefaultsOutsideControlPanel()
    {
        define('REQ', 'PAGE');

        $fieldtype = $this->makeFieldtype([], 0, 'feature_file', FileFtDisplayFieldSpy::class);
        $this->fileFieldMock->fieldReturn = '<frontend picker>';

        $result = $fieldtype->display_field('{filedir_1}brochure.pdf');

        $this->assertSame('<frontend picker>', $result);
        $this->assertSame(1, $fieldtype->frontendJsCalls);
        $this->assertSame([
            [
                'file.publishCreateUrl' => 'https://example.com/admin.php?/cp/files/file/view/###&modal_form=y',
            ],
        ], $this->javascriptMock->globals);
        $this->assertSame([], $this->cpMock->scripts);
        $this->assertSame([], $this->fileFieldMock->dragAndDropCalls);
        $this->assertSame([
            [
                'field_name' => 'feature_file',
                'data' => '{filedir_1}brochure.pdf',
                'allowed_file_dirs' => 'all',
                'content_type' => 'all',
                'filebrowser' => false,
                'existing_limit' => null,
            ],
        ], $this->fileFieldMock->fieldCalls);
    }

    /**
     * Assert front-end requests pass the configured existing-file limit only when enabled.
     *
     * @return void
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDisplayFieldPassesConfiguredExistingLimitOnFrontend()
    {
        define('REQ', 'PAGE');

        $fieldtype = $this->makeFieldtype([
            'allowed_directories' => [5],
            'field_content_type' => 'all',
            'num_existing' => 9,
            'show_existing' => 'y',
        ], 0, 'asset_file', FileFtDisplayFieldSpy::class);
        $this->fileFieldMock->fieldReturn = '<frontend with existing>';

        $result = $fieldtype->display_field('{filedir_5}logo.svg');

        $this->assertSame('<frontend with existing>', $result);
        $this->assertSame(1, $fieldtype->frontendJsCalls);
        $this->assertSame([
            [
                'field_name' => 'asset_file',
                'data' => '{filedir_5}logo.svg',
                'allowed_file_dirs' => [5],
                'content_type' => 'all',
                'filebrowser' => false,
                'existing_limit' => 9,
            ],
        ], $this->fileFieldMock->fieldCalls);
    }

    /**
     * Assert Pro Variables display delegates to display_field() unchanged.
     *
     * @return void
     */
    public function testVarDisplayFieldDelegatesToDisplayField()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'variables_file', FileFtVarDisplayFieldSpy::class);
        $fieldtype->displayFieldReturn = '<variables field output>';

        $result = $fieldtype->var_display_field('{filedir_7}manual.pdf');

        $this->assertSame('<variables field output>', $result);
        $this->assertSame(['{filedir_7}manual.pdf'], $fieldtype->displayFieldCalls);
    }
}
