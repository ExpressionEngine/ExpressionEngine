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

class FileFtVarReplaceTagSpy extends File_ft
{
    /** @var array<int, mixed> */
    public $preProcessCalls = [];

    /** @var mixed */
    public $preProcessReturn = [];

    /** @var array<int, array<string, mixed>> */
    public $replaceTagCalls = [];

    /** @var string */
    public $replaceTagReturn = 'replace-tag-output';

    /** @var array<int, array<string, mixed>> */
    public $replaceResizeCalls = [];

    /** @var string */
    public $replaceResizeReturn = 'replace-resize-output';

    /**
     * Capture pre-processing without depending on file_field parsing internals.
     *
     * @param mixed $data
     * @return mixed
     */
    public function pre_process($data)
    {
        $this->preProcessCalls[] = $data;

        return $this->preProcessReturn;
    }

    /**
     * Capture fallback tag replacement dispatch.
     *
     * @param mixed $data
     * @param array<string, mixed> $params
     * @param mixed $tagdata
     * @return string
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        $this->replaceTagCalls[] = [
            'data' => $data,
            'params' => $params,
            'tagdata' => $tagdata,
        ];

        return $this->replaceTagReturn;
    }

    /**
     * Capture modifier-specific dispatch for resize tags.
     *
     * @param mixed $data
     * @param array<string, mixed> $params
     * @param mixed $tagdata
     * @return string
     */
    public function replace_resize($data, $params = array(), $tagdata = false)
    {
        $this->replaceResizeCalls[] = [
            'data' => $data,
            'params' => $params,
            'tagdata' => $tagdata,
        ];

        return $this->replaceResizeReturn;
    }
}

class FileFtVarReplaceTagTest extends FileFtTestBase
{
    /**
     * Assert an explicit modifier dispatches to the matching replacement method.
     *
     * @return void
     */
    public function testVarReplaceTagDispatchesToModifierSpecificMethod()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtVarReplaceTagSpy::class);
        $fieldtype->preProcessReturn = ['file_id' => 9, 'url' => '{filedir_2}hero.jpg'];
        $fieldtype->replaceResizeReturn = 'resized-output';
        $this->templateMock->fetchParamMap['modifier'] = 'resize';

        $result = $fieldtype->var_replace_tag('{filedir_2}hero.jpg', ['width' => '1200'], '{file:url}');

        $this->assertSame('resized-output', $result);
        $this->assertSame(['{filedir_2}hero.jpg'], $fieldtype->preProcessCalls);
        $this->assertSame([
            [
                'data' => ['file_id' => 9, 'url' => '{filedir_2}hero.jpg'],
                'params' => ['width' => '1200'],
                'tagdata' => '{file:url}',
            ],
        ], $fieldtype->replaceResizeCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
        $this->assertSame([
            [
                'key' => 'modifier',
                'default' => 'tag',
            ],
        ], $this->templateMock->fetchParamCalls);
    }

    /**
     * Assert an empty tag pair is normalized to false before modifier dispatch.
     *
     * @return void
     */
    public function testVarReplaceTagNormalizesEmptyTagdataBeforeDispatch()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtVarReplaceTagSpy::class);
        $fieldtype->preProcessReturn = ['file_id' => 12];
        $fieldtype->replaceResizeReturn = 'normalized-output';
        $this->templateMock->fetchParamMap['modifier'] = 'resize';

        $result = $fieldtype->var_replace_tag('{filedir_7}manual.pdf', ['wrap' => 'plain'], '');

        $this->assertSame('normalized-output', $result);
        $this->assertSame([
            [
                'data' => ['file_id' => 12],
                'params' => ['wrap' => 'plain'],
                'tagdata' => false,
            ],
        ], $fieldtype->replaceResizeCalls);
    }

    /**
     * Assert unsupported modifiers fall back to replace_tag() after pre-processing.
     *
     * @return void
     */
    public function testVarReplaceTagFallsBackWhenModifierMethodDoesNotExist()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtVarReplaceTagSpy::class);
        $fieldtype->preProcessReturn = false;
        $fieldtype->replaceTagReturn = 'fallback-output';
        $this->templateMock->fetchParamMap['modifier'] = 'unknown';

        $result = $fieldtype->var_replace_tag('{filedir_5}fallback.pdf', ['raw_output' => 'yes'], '{tagdata}');

        $this->assertSame('fallback-output', $result);
        $this->assertSame([
            [
                'data' => false,
                'params' => ['raw_output' => 'yes'],
                'tagdata' => '{tagdata}',
            ],
        ], $fieldtype->replaceTagCalls);
        $this->assertSame([], $fieldtype->replaceResizeCalls);
    }

    /**
     * Assert the default modifier value routes to replace_tag() when TMPL has no override.
     *
     * @return void
     */
    public function testVarReplaceTagUsesTagAsDefaultModifier()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtVarReplaceTagSpy::class);
        $fieldtype->preProcessReturn = ['file_id' => 44];
        $fieldtype->replaceTagReturn = 'default-tag-output';

        $result = $fieldtype->var_replace_tag('{filedir_1}default.pdf', ['wrap' => 'plain']);

        $this->assertSame('default-tag-output', $result);
        $this->assertSame([
            [
                'data' => ['file_id' => 44],
                'params' => ['wrap' => 'plain'],
                'tagdata' => false,
            ],
        ], $fieldtype->replaceTagCalls);
        $this->assertSame([
            [
                'key' => 'modifier',
                'default' => 'tag',
            ],
        ], $this->templateMock->fetchParamCalls);
    }
}
