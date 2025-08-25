<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSetListingsTest extends StructureTestBase
{
    public function testSetListingsPerformsUpdateOrInsertPerEntry()
    {
        $captured = (object) ['updates' => 0, 'inserts' => 0, 'get_where_calls' => []];

        ee()->config->items['site_id'] = 1;

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            private $callIndex = 0;
            public function __construct($cap) { $this->cap = $cap; }
            public function get_where($table, $where)
            {
                $this->cap->get_where_calls[] = $where['entry_id'] ?? null;
                // Return existing row for first entry, none for second
                if ($this->callIndex++ === 0) {
                    return new FakeDbResult([[ 'entry_id' => $where['entry_id'] ]]);
                }
                return new FakeDbResult([]);
            }
            public function update_string($table, $data, $where)
            {
                $this->cap->updates++;
                return 'UPDATE';
            }
            public function insert_string($table, $data)
            {
                $this->cap->inserts++;
                return 'INSERT';
            }
            public function query($sql) { return new FakeDbResult([]); }
        });

        $data = [
            [
                'entry_id' => 10,
                'channel_id' => 3,
                'parent_id' => 5,
                'template_id' => 2,
                'parent_uri' => '/parent',
                'uri' => 'one',
                'site_id' => 1,
            ],
            [
                'entry_id' => 11,
                'channel_id' => 3,
                'parent_id' => 5,
                'template_id' => 2,
                'parent_uri' => '/parent',
                'uri' => 'two',
                'site_id' => 1,
            ],
        ];

        $this->structure->set_listings($data);

        $this->assertSame([10, 11], $captured->get_where_calls);
        $this->assertSame(1, $captured->updates);
        $this->assertSame(1, $captured->inserts);
    }
}



