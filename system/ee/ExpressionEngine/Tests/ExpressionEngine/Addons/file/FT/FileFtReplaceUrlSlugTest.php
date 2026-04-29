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

class FileFtReplaceUrlSlugTest extends FileFtTestBase
{
    /**
     * Assert replace_url_slug() delegates filename data to the text formatter.
     *
     * @return void
     */
    public function testReplaceUrlSlugDelegatesFilenameToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceUrlSlugFormatRecorder('slugged::payload');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_url_slug([
            'filename' => 'Spec Sheet Final 2026.pdf',
            'url' => '{filedir_8}spec-sheet-final-2026.pdf',
            'raw_output' => '{filedir_8}ignored.pdf',
        ], [
            'separator' => '_',
            'lowercase' => false,
        ], '{ignored-tagdata}');

        $this->assertSame('slugged::payload', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => 'Spec Sheet Final 2026.pdf',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'separator' => '_',
                'lowercase' => false,
            ],
        ], $formatRecorder->textFormatter->urlSlugCalls);
    }

    /**
     * Assert replace_url_slug() keeps the empty filename boundary and string-casts falsey results.
     *
     * @return void
     */
    public function testReplaceUrlSlugCastsFalseyFormatterResultToString()
    {
        $formatRecorder = new FileFtReplaceUrlSlugFormatRecorder(0);
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_url_slug([
            'filename' => '',
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
        $this->assertSame([[]], $formatRecorder->textFormatter->urlSlugCalls);
    }
}

class FileFtReplaceUrlSlugFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceUrlSlugTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceUrlSlugTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceUrlSlugTextFormatterRecorder
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

class FileFtReplaceUrlSlugTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $urlSlugCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the urlSlug() return value for assertions.
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
    public function urlSlug($params = [])
    {
        $this->urlSlugCalls[] = $params;

        return $this->returnValue;
    }
}
