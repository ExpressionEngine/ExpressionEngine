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

class FileFtReplaceRawContentTest extends FileFtTestBase
{
    /**
     * Assert replace_raw_content() forwards raw_output through the text formatter.
     *
     * @return void
     */
    public function testReplaceRawContentDelegatesRawOutputToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceRawContentFormatRecorder('encoded::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_raw_content([
            'raw_output' => '{filedir_8}spec-sheet.pdf',
            'url' => '{filedir_8}ignored.pdf',
        ], [
            'encode_vars' => false,
            'entities' => 'quotes',
        ], '{ignored-tagdata}');

        $this->assertSame('encoded::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_8}spec-sheet.pdf',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'encode_vars' => false,
                'entities' => 'quotes',
            ],
        ], $formatRecorder->textFormatter->encodeCalls);
    }

    /**
     * Assert replace_raw_content() keeps empty raw payloads and falsey formatter results intact.
     *
     * @return void
     */
    public function testReplaceRawContentCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceRawContentFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_raw_content([
            'raw_output' => '',
            'url' => '{filedir_3}fallback.pdf',
        ]);

        $this->assertSame('0', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([[]], $formatRecorder->textFormatter->encodeCalls);
    }
}

class FileFtReplaceRawContentFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceRawContentTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceRawContentTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceRawContentTextFormatterRecorder
     */
    public function make($type, $value)
    {
        $this->makeCalls[] = [
            'type' => $type,
            'value' => $value,
        ];

        return $this->textFormatter;
    }
}

class FileFtReplaceRawContentTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $encodeCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the encodeEETags() return value for assertions.
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->returnValue = $returnValue;
    }

    /**
     * Capture forwarded formatter params and return the configured value.
     *
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function encodeEETags($params = [])
    {
        $this->encodeCalls[] = $params;

        return $this->returnValue;
    }
}
