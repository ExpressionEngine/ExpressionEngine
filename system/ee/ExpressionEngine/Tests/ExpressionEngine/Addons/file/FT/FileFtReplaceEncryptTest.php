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

class FileFtReplaceEncryptTest extends FileFtTestBase
{
    /**
     * Assert replace_encrypt() forwards url data through the encrypt formatter.
     *
     * @return void
     */
    public function testReplaceEncryptDelegatesUrlToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceEncryptFormatRecorder('encrypted::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_encrypt([
            'url' => '{filedir_8}spec sheet.pdf?dl=Y',
            'raw_output' => '{filedir_8}ignored.pdf',
            'filename' => 'ignored.pdf',
        ], [
            'decode' => false,
            'key' => 'field-secret',
        ], '{ignored-tagdata}');

        $this->assertSame('encrypted::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_8}spec sheet.pdf?dl=Y',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'decode' => false,
                'key' => 'field-secret',
            ],
        ], $formatRecorder->textFormatter->encryptCalls);
    }

    /**
     * Assert replace_encrypt() preserves empty urls and string-casts falsey formatter results.
     *
     * @return void
     */
    public function testReplaceEncryptCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceEncryptFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_encrypt([
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
        $this->assertSame([[]], $formatRecorder->textFormatter->encryptCalls);
    }
}

class FileFtReplaceEncryptFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceEncryptTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceEncryptTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceEncryptTextFormatterRecorder
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

class FileFtReplaceEncryptTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $encryptCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the encrypt() return value for assertions.
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
    public function encrypt($params = [])
    {
        $this->encryptCalls[] = $params;

        return $this->returnValue;
    }
}
