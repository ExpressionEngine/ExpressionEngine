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

class FileFtReplaceFormPrepTest extends FileFtTestBase
{
    /**
     * Assert replace_form_prep() forwards url data through formPrep() before encoding EE tags.
     *
     * @return void
     */
    public function testReplaceFormPrepDelegatesUrlToTextFormatterAndRunsFormPrepBeforeEncoding()
    {
        $formatRecorder = new FileFtReplaceFormPrepFormatRecorder('prepared::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_form_prep([
            'url' => '{filedir_8}spec "sheet" {exp:tag}.pdf',
            'raw_output' => '{filedir_8}ignored.pdf',
            'filename' => 'ignored',
        ], [
            'encode_vars' => false,
            'entities' => 'quotes',
        ], '{ignored-tagdata}');

        $this->assertSame('prepared::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_8}spec "sheet" {exp:tag}.pdf',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            'formPrep',
            [
                'encodeEETags' => [
                    'encode_vars' => false,
                    'entities' => 'quotes',
                ],
            ],
        ], $formatRecorder->textFormatter->callOrder);
    }

    /**
     * Assert replace_form_prep() preserves empty urls and string-casts falsey formatter results.
     *
     * @return void
     */
    public function testReplaceFormPrepCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceFormPrepFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_form_prep([
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
        $this->assertSame([
            'formPrep',
            [
                'encodeEETags' => [],
            ],
        ], $formatRecorder->textFormatter->callOrder);
    }
}

class FileFtReplaceFormPrepFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceFormPrepTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceFormPrepTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceFormPrepTextFormatterRecorder
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

class FileFtReplaceFormPrepTextFormatterRecorder
{
    /** @var array<int, mixed> */
    public $callOrder = [];

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
     * Record that formPrep() ran before the final encoding step.
     *
     * @return self
     */
    public function formPrep()
    {
        $this->callOrder[] = 'formPrep';

        return $this;
    }

    /**
     * Capture forwarded formatter params and return the configured value.
     *
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function encodeEETags($params = [])
    {
        $this->callOrder[] = [
            'encodeEETags' => $params,
        ];

        return $this->returnValue;
    }
}
