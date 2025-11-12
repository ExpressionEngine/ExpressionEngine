<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetListingDataTest extends StructureTestBase
{
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
}



