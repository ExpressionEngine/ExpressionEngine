<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetListingDataTest extends StructureTestBase
{
    public function testGetListingDataQueriesListingsTableUsingEntryIdFilter()
    {
        $expectedRow = (object) ['entry_id' => 88, 'channel_id' => 5];
        $db = new class($expectedRow) extends FakeDb {
            public $table;
            public $where;
            private $expectedRow;

            public function __construct($expectedRow)
            {
                $this->expectedRow = $expectedRow;
            }

            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                $this->table = $table;
                $this->where = $where;

                return new class($this->expectedRow) {
                    public $num_rows = 1;
                    private $row;

                    public function __construct($row)
                    {
                        $this->row = $row;
                    }

                    public function row($column = null)
                    {
                        if ($column !== null) {
                            return $this->row->$column ?? null;
                        }

                        return $this->row;
                    }
                };
            }
        };
        ee()->setMock('db', $db);

        $row = $this->structure->get_listing_data(88);

        $this->assertSame('structure_listings', $db->table);
        $this->assertSame(['entry_id' => 88], $db->where);
        $this->assertSame($expectedRow, $row);
    }

    public function testGetListingDataReturnsRowObjectWhenFound()
    {
        ee()->setMock('db', new class extends FakeDb {
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                $rows = [ ['entry_id' => 77, 'channel_id' => 9, 'template_id' => 2, 'uri' => 'item'] ];
                return new class($rows) {
                    private $rows;
                    public $num_rows;
                    public function __construct($rows) { $this->rows = $rows; $this->num_rows = count($rows); }
                    public function row($column = null) {
                        $rowObj = (object) $this->rows[0];
                        if ($column !== null) {
                            return $rowObj->$column ?? ($this->rows[0][$column] ?? null);
                        }
                        return $rowObj;
                    }
                };
            }
        });

        $row = $this->structure->get_listing_data(77);
        $this->assertIsObject($row);
        $this->assertSame(9, $row->channel_id);
        $this->assertSame(2, $row->template_id);
    }

    public function testGetListingDataReturnsFalseWhenNotFound()
    {
        ee()->setMock('db', new class extends FakeDb {
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new class([]) {
                    public $num_rows = 0;
                    public function __construct($rows) {}
                };
            }
        });

        $this->assertFalse($this->structure->get_listing_data(123));
    }

    public function testGetListingDataDoesNotReadRowWhenQueryHasNoRows()
    {
        $query = new class {
            public $num_rows = 0;

            public function row($column = null)
            {
                throw new RuntimeException('row() should not be called when there are no listing rows.');
            }
        };

        ee()->setMock('db', new class($query) extends FakeDb {
            private $query;

            public function __construct($query)
            {
                $this->query = $query;
            }

            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return $this->query;
            }
        });

        $this->assertFalse($this->structure->get_listing_data(123));
    }
}


