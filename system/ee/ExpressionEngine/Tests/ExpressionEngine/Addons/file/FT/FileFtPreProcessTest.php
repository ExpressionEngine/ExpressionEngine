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

class FileFtPreProcessTest extends FileFtTestBase
{
    /**
     * Assert pre_process() returns the parsed payload provided by file_field.
     *
     * @return void
     */
    public function testPreProcessReturnsParsedPayloadFromFileField()
    {
        $fieldtype = $this->makeFieldtype();
        $parsedPayload = [
            'file_id' => 12,
            'url' => 'https://example.com/images/banner.png',
            'filename' => 'banner',
            'extension' => 'png',
        ];
        $this->fileFieldMock->parseFieldReturn = $parsedPayload;

        $this->assertSame($parsedPayload, $fieldtype->pre_process('{filedir_1}banner.png'));
        $this->assertSame(['{filedir_1}banner.png'], $this->fileFieldMock->parseFieldCalls);
    }

    /**
     * Provide boundary values that still must be delegated to file_field.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function preProcessBoundaryProvider()
    {
        return [
            'empty string' => ['', null],
            'false' => [false, []],
        ];
    }

    /**
     * Assert pre_process() does not short-circuit boundary values before parsing.
     *
     * @dataProvider preProcessBoundaryProvider
     * @param mixed $data
     * @param mixed $parsedPayload
     * @return void
     */
    public function testPreProcessDelegatesBoundaryValuesToFileField($data, $parsedPayload)
    {
        $fieldtype = $this->makeFieldtype();
        $this->fileFieldMock->parseFieldReturn = $parsedPayload;

        $this->assertSame($parsedPayload, $fieldtype->pre_process($data));
        $this->assertSame([$data], $this->fileFieldMock->parseFieldCalls);
    }
}
