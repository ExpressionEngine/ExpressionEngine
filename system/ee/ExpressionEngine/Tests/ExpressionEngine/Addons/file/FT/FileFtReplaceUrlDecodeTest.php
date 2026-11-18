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

class FileFtReplaceUrlDecodeTest extends FileFtTestBase
{
    /**
     * Assert replace_url_decode() forwards url data through the url decoder formatter.
     *
     * @return void
     */
    public function testReplaceUrlDecodeDelegatesUrlToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceUrlDecodeFormatRecorder('decoded::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_url_decode([
            'url' => '{filedir_8}Spec+Sheet%20%26%20Notes.pdf%3Fdl%3DY',
            'raw_output' => '{filedir_8}ignored.pdf',
            'filename' => 'ignored.pdf',
        ], [
            'plus_encoded_spaces' => 'yes',
        ], '{ignored-tagdata}');

        $this->assertSame('decoded::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_8}Spec+Sheet%20%26%20Notes.pdf%3Fdl%3DY',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'plus_encoded_spaces' => 'yes',
            ],
        ], $formatRecorder->textFormatter->urlDecodeCalls);
    }

    /**
     * Assert replace_url_decode() preserves empty urls and string-casts falsey formatter results.
     *
     * @return void
     */
    public function testReplaceUrlDecodeCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceUrlDecodeFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_url_decode([
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
        $this->assertSame([[]], $formatRecorder->textFormatter->urlDecodeCalls);
    }
}

class FileFtReplaceUrlDecodeFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceUrlDecodeTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceUrlDecodeTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceUrlDecodeTextFormatterRecorder
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

class FileFtReplaceUrlDecodeTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $urlDecodeCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the urlDecode() return value for assertions.
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
    public function urlDecode($params = [])
    {
        $this->urlDecodeCalls[] = $params;

        return $this->returnValue;
    }
}
