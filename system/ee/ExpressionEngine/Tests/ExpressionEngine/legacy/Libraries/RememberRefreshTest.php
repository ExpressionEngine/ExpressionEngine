<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RememberRefreshTest extends TestCase
{
    public function testLosingRefreshDoesNotEmitAnUnusableCookie()
    {
        ee()->resetMocks();
        require_once BASEPATH . 'libraries/Remember.php';
        $db = new RememberRefreshDatabase();
        $db->tables['remember_me'] = [[
            'remember_me_id' => 'old-token', 'member_id' => 42, 'site_id' => 1,
            'user_agent' => 'browser', 'expiration' => 4000000, 'last_refresh' => 1000000
        ]];
        $input = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['cookie', 'user_agent', 'ip_address', 'set_cookie', 'delete_cookie'])->getMock();
        $input->method('cookie')->willReturn('old-token');
        $input->method('user_agent')->willReturn('browser');
        $input->expects($this->never())->method('set_cookie');
        $input->expects($this->never())->method('delete_cookie');
        $functions = $this->getMockBuilder(\stdClass::class)->addMethods(['random'])->getMock();
        $functions->method('random')->willReturn('losing-token');
        ee()->setMock('db', $db);
        ee()->setMock('input', $input);
        ee()->setMock('functions', $functions);
        ee()->setMock('localize', (object) ['now' => 2000000]);
        $remember = new \Remember();
        $this->assertTrue((bool) $remember->exists());
        $db->tables['remember_me'][0]['remember_me_id'] = 'winning-token';
        $remember->refresh();
        $this->assertSame('winning-token', $db->tables['remember_me'][0]['remember_me_id']);
        $this->assertFalse((bool) $remember->exists());
        ee()->resetMocks();
    }
}

/**
 * Minimal in-memory Active Record adapter for remembered-login persistence.
 */
class RememberRefreshDatabase
{
    public $tables = [];
    public $updates = [];
    private $where = [];
    private $changes = [];
    protected $affectedRows = 0;

    /**
     * Restrict the next write to matching fixture rows.
     *
     * @param string $key Column to compare.
     * @param mixed $value Required column value.
     * @return self
     */
    public function where($key, $value)
    {
        $this->where[$key] = $value;
        return $this;
    }

    /**
     * Accept ordering for fixtures already arranged oldest first.
     *
     * @param string $key Column to sort.
     * @param string $direction Sort direction.
     * @return self
     */
    public function order_by($key, $direction)
    {
        return $this;
    }

    /**
     * Stage values for the next fixture update.
     *
     * @param array $changes Column values to write.
     * @return self
     */
    public function set($changes)
    {
        $this->changes = $changes;
        return $this;
    }

    /**
     * Return fixture rows matching an equality query.
     *
     * @param string $table Fixture table name.
     * @param array $where Column values to match.
     * @return object
     */
    public function get_where($table, $where)
    {
        $rows = array_values(array_filter($this->tables[$table], function ($row) use ($where) {
            return !array_diff_assoc($where, $row);
        }));
        return new class($rows) {
            private $rows;

            /**
             * Capture the matching fixture rows.
             *
             * @param array $rows Query result rows.
             * @return void
             */
            public function __construct($rows)
            {
                $this->rows = $rows;
            }

            /**
             * Count matching rows.
             *
             * @return int
             */
            public function num_rows()
            {
                return count($this->rows);
            }

            /**
             * Return the first matching row.
             *
             * @return array
             */
            public function row_array()
            {
                return $this->rows[0];
            }

            /**
             * Return rows in the legacy object format.
             *
             * @return object[]
             */
            public function result()
            {
                return array_map(function ($row) { return (object) $row; }, $this->rows);
            }

            /**
             * Match the database result's release interface.
             *
             * @return void
             */
            public function free_result()
            {
            }
        };
    }

    /**
     * Apply a write to matching rows and record its query constraints.
     *
     * @param string $table Fixture table name.
     * @param array|null $changes Column values, or previously staged values.
     * @return void
     */
    public function update($table, $changes = null)
    {
        $this->affectedRows = 0;
        $changes = $changes === null ? $this->changes : $changes;
        $this->updates[] = ['table' => $table, 'where' => $this->where, 'changes' => $changes];
        foreach ($this->tables[$table] as &$row) {
            if (!array_diff_assoc($this->where, $row)) {
                $row = array_merge($row, $changes);
                $this->affectedRows++;
            }
        }
        $this->where = [];
        $this->changes = [];
    }

    public function affected_rows()
    {
        return $this->affectedRows;
    }

    /**
     * Remove only fixture rows matching the current query.
     *
     * @param string $table Fixture table name.
     * @return void
     */
    public function delete($table)
    {
        $where = $this->where;
        $except = $where['remember_me_id !='] ?? null;
        unset($where['remember_me_id !=']);
        $this->tables[$table] = array_values(array_filter($this->tables[$table], function ($row) use ($where, $except) {
            return (bool) array_diff_assoc($where, $row) || $row['remember_me_id'] === $except;
        }));
        $this->where = [];
    }
}

