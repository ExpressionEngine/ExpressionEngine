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

class FileFtReplaceLimitTest extends FileFtTestBase
{
    /**
     * Assert replace_limit() forwards url data and applies the preserve_words default.
     *
     * @return void
     */
    public function testReplaceLimitDelegatesUrlToTextFormatterAndDefaultsPreserveWords()
    {
        $formatRecorder = new FileFtReplaceLimitFormatRecorder('limited::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_limit([
            'url' => '{filedir_8}spec sheet download link.pdf',
            'raw_output' => '{filedir_8}ignored-source.pdf',
        ], [
            'limit' => 14,
            'append' => '...',
        ], '{ignored-tagdata}');

        $this->assertSame('limited::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_8}spec sheet download link.pdf',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'limit' => 14,
                'append' => '...',
                'preserve_words' => true,
            ],
        ], $formatRecorder->textFormatter->limitCharsCalls);
    }

    /**
     * Assert replace_limit() preserves an explicit preserve_words override.
     *
     * @return void
     */
    public function testReplaceLimitPreservesExplicitPreserveWordsOverride()
    {
        $formatRecorder = new FileFtReplaceLimitFormatRecorder('trimmed::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_limit([
            'url' => '{filedir_5}multi word asset name.pdf',
            'raw_output' => '{filedir_5}ignored.pdf',
        ], [
            'limit' => 8,
            'preserve_words' => false,
        ]);

        $this->assertSame('trimmed::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => '{filedir_5}multi word asset name.pdf',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'limit' => 8,
                'preserve_words' => false,
            ],
        ], $formatRecorder->textFormatter->limitCharsCalls);
    }

    /**
     * Assert replace_limit() preserves empty urls and string-casts falsey formatter results.
     *
     * @return void
     */
    public function testReplaceLimitCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceLimitFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_limit([
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
            [
                'preserve_words' => true,
            ],
        ], $formatRecorder->textFormatter->limitCharsCalls);
    }
}

class FileFtReplaceLimitFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceLimitTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceLimitTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceLimitTextFormatterRecorder
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

class FileFtReplaceLimitTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $limitCharsCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the limitChars() return value for assertions.
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
    public function limitChars($params = [])
    {
        $this->limitCharsCalls[] = $params;

        return $this->returnValue;
    }
}
