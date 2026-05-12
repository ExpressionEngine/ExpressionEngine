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

class FileFtReplaceCensorTest extends FileFtTestBase
{
    /**
     * Assert replace_censor() delegates title data to the text formatter censor modifier.
     *
     * @return void
     */
    public function testReplaceCensorDelegatesTitleToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceCensorFormatRecorder('censored::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_censor([
            'title' => 'Spec damn sheet Final.pdf',
            'url' => '{filedir_8}clean-download.pdf',
            'raw_output' => '{filedir_8}ignored.pdf',
            'filename' => 'clean-download.pdf',
        ], [
            'custom' => 'ignored-by-parent-censor',
        ], '{ignored-tagdata}');

        $this->assertSame('censored::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => 'Spec damn sheet Final.pdf',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame(1, $formatRecorder->textFormatter->censorCallCount);
    }

    /**
     * Assert replace_censor() preserves the empty-title boundary and string-casts falsey formatter results.
     *
     * @return void
     */
    public function testReplaceCensorCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceCensorFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_censor([
            'title' => '',
            'url' => '{filedir_3}fallback.pdf',
            'raw_output' => '{filedir_3}ignored.pdf',
        ]);

        $this->assertSame('0', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame(1, $formatRecorder->textFormatter->censorCallCount);
    }
}

class FileFtReplaceCensorFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceCensorTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceCensorTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceCensorTextFormatterRecorder
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

class FileFtReplaceCensorTextFormatterRecorder
{
    /** @var int */
    public $censorCallCount = 0;

    /** @var mixed */
    private $returnValue;

    /**
     * Store the censor() return value for assertions.
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->returnValue = $returnValue;
    }

    /**
     * Record the censor() call and return the configured formatter result.
     *
     * @return mixed
     */
    public function censor()
    {
        $this->censorCallCount++;

        return $this->returnValue;
    }
}
