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

class FileFtRenderTableCellDouble extends File_ft
{
    /** @var mixed */
    public $preProcessReturn = [];

    /** @var string */
    public $replaceTagReturn = '';

    /** @var array<int, mixed> */
    public $preProcessCalls = [];

    /** @var array<int, array<string, mixed>> */
    public $replaceTagCalls = [];

    /**
     * Return the configured pre_process() payload for renderTableCell() tests.
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
     * Return the configured URL and record replace_tag() arguments.
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
}

class FileFtRenderTableCellTest extends FileFtTestBase
{
    /**
     * Provide empty inputs that must short-circuit before preprocessing.
     *
     * @return array<string, array{0: mixed}>
     */
    public function emptyRenderTableCellDataProvider()
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }

    /**
     * Build a File_ft double that records renderTableCell() collaborators.
     *
     * @return FileFtRenderTableCellDouble
     */
    protected function makeRenderTableCellFieldtype()
    {
        /** @var FileFtRenderTableCellDouble $fieldtype */
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtRenderTableCellDouble::class);

        return $fieldtype;
    }

    /**
     * Assert renderTableCell() short-circuits empty values before preprocessing.
     *
     * @dataProvider emptyRenderTableCellDataProvider
     * @param mixed $data
     * @return void
     */
    public function testRenderTableCellReturnsEmptyStringForEmptyDataWithoutPreProcessing($data)
    {
        $fieldtype = $this->makeRenderTableCellFieldtype();

        $this->assertSame('', $fieldtype->renderTableCell($data, 7, (object) ['entry_id' => 14]));
        $this->assertSame([], $fieldtype->preProcessCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert renderTableCell() returns an empty string when pre_process() has no title.
     *
     * @return void
     */
    public function testRenderTableCellReturnsEmptyStringWhenPreProcessedDataHasNoTitle()
    {
        $fieldtype = $this->makeRenderTableCellFieldtype();
        $fieldtype->preProcessReturn = [
            'url' => 'https://example.com/files/manual.pdf',
        ];

        $this->assertSame('', $fieldtype->renderTableCell('{filedir_1}manual.pdf', 9, (object) ['entry_id' => 14]));
        $this->assertSame(['{filedir_1}manual.pdf'], $fieldtype->preProcessCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert renderTableCell() builds the table anchor from the parsed URL and title.
     *
     * @return void
     */
    public function testRenderTableCellBuildsAnchorFromPreProcessedTitleAndResolvedUrl()
    {
        $fieldtype = $this->makeRenderTableCellFieldtype();
        $fieldData = [
            'title' => 'Manual PDF',
            'url' => 'https://example.com/files/manual.pdf',
        ];
        $fieldtype->preProcessReturn = $fieldData;
        $fieldtype->replaceTagReturn = 'https://cdn.example.com/files/manual.pdf';

        $result = $fieldtype->renderTableCell('{filedir_1}manual.pdf', 11, (object) ['entry_id' => 14]);

        $this->assertSame(
            '<a href="https://cdn.example.com/files/manual.pdf" target="_blank">Manual PDF</a>',
            $result
        );
        $this->assertSame(['{filedir_1}manual.pdf'], $fieldtype->preProcessCalls);
        $this->assertSame([
            [
                'data' => $fieldData,
                'params' => [],
                'tagdata' => false,
            ],
        ], $fieldtype->replaceTagCalls);
    }
}
