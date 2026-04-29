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

if (!function_exists('anchor')) {
    /**
     * Provide the url helper anchor() API needed by File_ft::_wrap_it().
     *
     * @param string $url
     * @param string $title
     * @param string $attributes
     * @return string
     */
    function anchor($url = '', $title = '', $attributes = '')
    {
        $attributeMarkup = $attributes !== '' ? ' ' . $attributes : '';

        return '<a href="' . $url . '"' . $attributeMarkup . '>' . $title . '</a>';
    }
}

class FileFtReplaceReplaceTest extends FileFtTestBase
{
    /**
     * Assert replace_replace() delegates url data to the text formatter replace modifier.
     *
     * @return void
     */
    public function testReplaceReplaceDelegatesUrlToTextFormatter()
    {
        $formatRecorder = new FileFtReplaceReplaceFormatRecorder('https://cdn.example.com/files/final-guide.pdf');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_replace([
            'url' => 'https://cdn.example.com/files/draft-guide.pdf',
            'filename' => 'draft-guide.pdf',
        ], [
            'find' => 'draft',
            'replace' => 'final',
            'case_sensitive' => 'yes',
        ], '{ignored-tagdata}');

        $this->assertSame('https://cdn.example.com/files/final-guide.pdf', $result);
        $this->assertSame([
            [
                'type' => 'Text',
                'value' => 'https://cdn.example.com/files/draft-guide.pdf',
            ],
        ], $formatRecorder->makeCalls);
        $this->assertSame([
            [
                'find' => 'draft',
                'replace' => 'final',
                'case_sensitive' => 'yes',
            ],
        ], $formatRecorder->textFormatter->replaceCalls);
    }

    /**
     * Assert unsupported wrap values still return the transformed url through the wrap branch.
     *
     * @return void
     */
    public function testReplaceReplaceReturnsTransformedUrlForUnsupportedWrapValues()
    {
        $formatRecorder = new FileFtReplaceReplaceFormatRecorder('https://cdn.example.com/files/final-guide.pdf');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_replace([
            'url' => 'https://cdn.example.com/files/draft-guide.pdf',
            'filename' => 'draft-guide.pdf',
        ], [
            'find' => 'draft',
            'replace' => 'final',
            'wrap' => 'plain',
        ]);

        $this->assertSame('https://cdn.example.com/files/final-guide.pdf', $result);
        $this->assertSame([
            [
                'find' => 'draft',
                'replace' => 'final',
                'wrap' => 'plain',
            ],
        ], $formatRecorder->textFormatter->replaceCalls);
    }

    /**
     * Assert link wrapping uses the transformed url and preserves the file link metadata.
     *
     * @return void
     */
    public function testReplaceReplaceWrapsTransformedUrlAsLink()
    {
        $formatRecorder = new FileFtReplaceReplaceFormatRecorder('https://cdn.example.com/files/final-guide.pdf');
        $loadRecorder = new FileFtReplaceReplaceLoadRecorder();
        ee()->setMock('Format', $formatRecorder);
        ee()->setMock('load', $loadRecorder);
        $this->loadRecorder = $loadRecorder;
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_replace([
            'url' => 'https://cdn.example.com/files/draft-guide.pdf',
            'filename' => 'Guide Download',
            'file_pre_format' => '<p>',
            'file_post_format' => '</p>',
            'file_properties' => 'class="download" rel="noopener"',
        ], [
            'find' => 'draft',
            'replace' => 'final',
            'wrap' => 'link',
        ]);

        $this->assertSame(
            '<p><a href="https://cdn.example.com/files/final-guide.pdf" class="download" rel="noopener">Guide Download</a></p>',
            $result
        );
        $this->assertSame(['url_helper'], $loadRecorder->helpers);
    }

    /**
     * Assert image wrapping uses the transformed url and preserves image metadata.
     *
     * @return void
     */
    public function testReplaceReplaceWrapsTransformedUrlAsImage()
    {
        $formatRecorder = new FileFtReplaceReplaceFormatRecorder('https://cdn.example.com/images/hero-final.png');
        ee()->setMock('Format', $formatRecorder);
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_replace([
            'url' => 'https://cdn.example.com/images/hero-draft.png',
            'filename' => 'Hero Banner',
            'image_pre_format' => '<figure>',
            'image_post_format' => '</figure>',
            'image_properties' => 'width="1280" height="720" loading="lazy"',
        ], [
            'find' => 'draft',
            'replace' => 'final',
            'wrap' => 'image',
        ]);

        $this->assertSame(
            '<figure><img src="https://cdn.example.com/images/hero-final.png" width="1280" height="720" loading="lazy" alt="Hero Banner" /></figure>',
            $result
        );
    }
}

class FileFtReplaceReplaceLoadRecorder extends FileFtLoadRecorder
{
    /** @var array<int, string> */
    public $helpers = [];

    /**
     * Record requested helpers.
     *
     * @param string $name
     * @return void
     */
    public function helper($name)
    {
        $this->helpers[] = $name;
    }
}

class FileFtReplaceReplaceFormatRecorder
{
    /** @var array<int, array{type: string, value: mixed}> */
    public $makeCalls = [];

    /** @var FileFtReplaceReplaceTextFormatterRecorder */
    public $textFormatter;

    /**
     * Seed the text formatter return value captured by make().
     *
     * @param mixed $returnValue
     * @return void
     */
    public function __construct($returnValue)
    {
        $this->textFormatter = new FileFtReplaceReplaceTextFormatterRecorder($returnValue);
    }

    /**
     * Capture the requested formatter type and source payload.
     *
     * @param string $type
     * @param mixed $value
     * @return FileFtReplaceReplaceTextFormatterRecorder
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

class FileFtReplaceReplaceTextFormatterRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $replaceCalls = [];

    /** @var mixed */
    private $returnValue;

    /**
     * Store the replace() return value for assertions.
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
    public function replace($params = [])
    {
        $this->replaceCalls[] = $params;

        return $this->returnValue;
    }
}
