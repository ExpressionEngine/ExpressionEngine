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

class FileFtReplaceUrlEncodeTest extends FileFtTestBase
{
    /**
     * Assert replace_url_encode() forwards url data through the url encoder formatter.
     *
     * @return void
     */
    public function testReplaceUrlEncodeDelegatesUrlToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceUrlEncodeFormatRecorder('encoded::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_url_encode([
            'url' => '{filedir_8}Spec Sheet & Notes.pdf?dl=Y',
            'raw_output' => '{filedir_8}ignored.pdf',
            'filename' => 'ignored.pdf',
        ], [
            'spaces' => '%20',
            'encode_slashes' => false,
        ], '{ignored-tagdata}');

        $this->assertSame('encoded::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_8}Spec Sheet & Notes.pdf?dl=Y',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'spaces' => '%20',
                'encode_slashes' => false,
            ],
        ], $formatRecorder->textFormatter->urlEncodeCalls);
    }

    /**
     * Assert replace_url_encode() preserves empty urls and string-casts falsey formatter results.
     *
     * @return void
     */
    public function testReplaceUrlEncodeCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceUrlEncodeFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_url_encode([
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
        $this->assertSame([[]], $formatRecorder->textFormatter->urlEncodeCalls);
    }
}

class FileFtReplaceUrlEncodeFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceUrlEncodeTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceUrlEncodeTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceUrlEncodeTextFormatterRecorder
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

class FileFtReplaceUrlEncodeTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $urlEncodeCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the urlEncode() return value for assertions.
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
    public function urlEncode($params = [])
    {
        $this->urlEncodeCalls[] = $params;

        return $this->returnValue;
    }
}
