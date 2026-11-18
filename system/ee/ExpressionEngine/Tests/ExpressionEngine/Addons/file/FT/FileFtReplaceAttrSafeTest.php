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

class FileFtReplaceAttrSafeTest extends FileFtTestBase
{
    /**
     * Assert replace_attr_safe() forwards url data through the attribute-safe formatter.
     *
     * @return void
     */
    public function testReplaceAttrSafeDelegatesUrlToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceAttrSafeFormatRecorder('escaped::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_attr_safe([
            'url' => '{filedir_8}spec "sheet".pdf',
            'raw_output' => '{filedir_8}ignored.pdf',
        ], [
            'encode_vars' => false,
            'double_encode' => false,
        ], '{ignored-tagdata}');

        $this->assertSame('escaped::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_8}spec "sheet".pdf',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'encode_vars' => false,
                'double_encode' => false,
            ],
        ], $formatRecorder->textFormatter->attributeSafeCalls);
    }

    /**
     * Assert replace_attr_safe() preserves empty urls and string-casts falsey formatter results.
     *
     * @return void
     */
    public function testReplaceAttrSafeCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceAttrSafeFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_attr_safe([
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
        $this->assertSame([[]], $formatRecorder->textFormatter->attributeSafeCalls);
    }
}

class FileFtReplaceAttrSafeFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceAttrSafeTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceAttrSafeTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceAttrSafeTextFormatterRecorder
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

class FileFtReplaceAttrSafeTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $attributeSafeCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the attributeSafe() return value for assertions.
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
    public function attributeSafe($params = [])
    {
        $this->attributeSafeCalls[] = $params;

        return $this->returnValue;
    }
}
