<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\CP\FileManager\Columns;

use ExpressionEngine\Library\CP\FileManager\Columns\Categories;
use PHPUnit\Framework\TestCase;

class CategoriesTest extends TestCase
{
    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testRenderTableCellUsesFileCategoryAssignments()
    {
        $db = new FileManagerCategoriesDbMock(
            [
                ['file_id' => 123, 'cat_id' => 7],
                ['file_id' => 123, 'cat_id' => 8],
                ['file_id' => 456, 'cat_id' => 9],
            ],
            [
                ['cat_id' => 7, 'cat_name' => 'News'],
                ['cat_id' => 8, 'cat_name' => 'Products'],
                ['cat_id' => 9, 'cat_name' => 'Archive'],
            ]
        );
        ee()->setMock('db', $db);

        $column = new Categories('categories');

        $this->assertSame('News, Products', $column->renderTableCell(null, null, (object) ['file_id' => 123]));
        $this->assertSame(['file_categories', 'categories'], $db->queriedTables);
    }

    public function testRenderTableCellReturnsEmptyWhenFileHasNoCategories()
    {
        ee()->setMock('db', new FileManagerCategoriesDbMock([], []));

        $column = new Categories('categories');

        $this->assertSame('', $column->renderTableCell(null, null, (object) ['file_id' => 123]));
    }
}

class FileManagerCategoriesDbMock
{
    public $queriedTables = [];

    private $fileCategories;
    private $categories;
    private $select = '';
    private $from = '';
    private $where = [];
    private $whereIn = [];

    public function __construct(array $fileCategories, array $categories)
    {
        $this->fileCategories = $fileCategories;
        $this->categories = $categories;
    }

    public function select($columns)
    {
        $this->select = $columns;

        return $this;
    }

    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    public function where($field, $value)
    {
        $this->where[$field] = $value;

        return $this;
    }

    public function where_in($field, array $values)
    {
        $this->whereIn[$field] = $values;

        return $this;
    }

    public function get($table = null)
    {
        $table = $table ?: $this->from;
        $this->queriedTables[] = $table;

        $rows = $table === 'file_categories'
            ? $this->filterFileCategories()
            : $this->filterCategories();

        $this->resetQueryState();

        return new FileManagerCategoriesDbResultMock($rows);
    }

    private function filterFileCategories()
    {
        if (!isset($this->where['file_id'])) {
            return $this->fileCategories;
        }

        return array_values(array_filter($this->fileCategories, function ($row) {
            return $row['file_id'] == $this->where['file_id'];
        }));
    }

    private function filterCategories()
    {
        if (!isset($this->whereIn['cat_id'])) {
            return $this->categories;
        }

        return array_values(array_filter($this->categories, function ($row) {
            return in_array($row['cat_id'], $this->whereIn['cat_id']);
        }));
    }

    private function resetQueryState()
    {
        $this->select = '';
        $this->from = '';
        $this->where = [];
        $this->whereIn = [];
    }
}

class FileManagerCategoriesDbResultMock
{
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function result_array()
    {
        return $this->rows;
    }
}
