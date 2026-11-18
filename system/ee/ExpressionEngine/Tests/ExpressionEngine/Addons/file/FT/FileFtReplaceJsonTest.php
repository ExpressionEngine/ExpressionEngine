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

class FileFtReplaceJsonTest extends FileFtTestBase
{
    /**
     * Assert replace_json() delegates url data to the text formatter json modifier.
     *
     * @return void
     */
    public function testReplaceJsonDelegatesUrlToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceJsonFormatRecorder('json::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_json([
            'url' => '{filedir_8}spec sheet.pdf?dl=Y',
            'raw_output' => '{filedir_8}ignored.pdf',
            'filename' => 'ignored.pdf',
        ], [
            'depth' => 4,
            'pretty' => 'yes',
        ], '{ignored-tagdata}');

        $this->assertSame('json::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_8}spec sheet.pdf?dl=Y',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'depth' => 4,
                'pretty' => 'yes',
            ],
        ], $formatRecorder->textFormatter->jsonCalls);
    }

    /**
     * Assert replace_json() preserves empty urls and string-casts falsey formatter results.
     *
     * @return void
     */
    public function testReplaceJsonCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceJsonFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_json([
            'url' => '',
            'raw_output' => '{filedir_3}fallback.pdf',
        ]);

        $this->assertSame('0', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([[]], $formatRecorder->textFormatter->jsonCalls);
    }
}

class FileFtReplaceJsonFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceJsonTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceJsonTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceJsonTextFormatterRecorder
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

class FileFtReplaceJsonTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $jsonCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the json() return value for assertions.
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
    public function json($params = [])
    {
        $this->jsonCalls[] = $params;

        return $this->returnValue;
    }
}
