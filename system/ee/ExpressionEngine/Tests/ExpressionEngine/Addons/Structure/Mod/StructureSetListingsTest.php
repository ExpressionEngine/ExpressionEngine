<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSetListingsTest extends StructureTestBase
{
    public function testSetListingsPerformsUpdateOrInsertPerEntry()
    {
        $captured = (object) ['updates' => 0, 'inserts' => 0, 'get_where_calls' => []];

        ee()->config->items['site_id'] = 1;

        // Ensure extensions mock exists for hook checks inside set_listings
        ee()->setMock('extensions', new class {
            public function active_hook($name) { return false; }
            public function call($name) { return null; }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            private $callIndex = 0;
            public function __construct($cap) { $this->cap = $cap; }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                $this->cap->get_where_calls[] = $where['entry_id'] ?? null;
                // Return existing row for first entry, none for second
                if ($this->callIndex++ === 0) {
                    return new eeDbResultMock([[ 'entry_id' => $where['entry_id'] ]]);
                }
                return new eeDbResultMock([]);
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
            public function query($sql) { return new eeDbResultMock([]); }
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

    public function testSetListingsUsesHookOverrideAndUnsetsParentUriFromHookData()
    {
        $captured = (object) ['update_payloads' => []];

        ee()->config->items['site_id'] = 1;

        ee()->setMock('extensions', new class {
            public function active_hook($name) { return $name === 'structure_before_save_listing'; }
            public function call($name, $listing)
            {
                return [
                    'entry_id' => $listing['entry_id'],
                    'channel_id' => $listing['channel_id'],
                    'template_id' => $listing['template_id'],
                    'parent_uri' => '/from-hook',
                    'from_hook' => true,
                ];
            }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new eeDbResultMock([['entry_id' => $where['entry_id']]]);
            }
            public function update_string($table, $data, $where)
            {
                $this->cap->update_payloads[] = $data;
                return 'UPDATE';
            }
            public function insert_string($table, $data)
            {
                return 'INSERT';
            }
            public function query($sql)
            {
                return new eeDbResultMock([]);
            }
        });

        $data = [[
            'entry_id' => 10,
            'channel_id' => 3,
            'parent_id' => 5,
            'template_id' => 2,
            'parent_uri' => '/parent',
            'uri' => 'one',
            'site_id' => 1,
        ]];

        $this->structure->set_listings($data);

        $this->assertCount(1, $captured->update_payloads);
        $this->assertSame(true, $captured->update_payloads[0]['from_hook']);
        $this->assertArrayNotHasKey('parent_uri', $captured->update_payloads[0]);
    }

    /**
     * Ensure invalid hook overrides do not replace sanitized listing data.
     *
     * @return void
     */
    public function testSetListingsIgnoresInvalidHookOverrideAndUsesSanitizedOriginalListing()
    {
        $captured = (object) ['hook_payloads' => [], 'insert_payloads' => []];

        ee()->config->items['site_id'] = 1;

        ee()->setMock('extensions', new class($captured) {
            private $cap;

            public function __construct($cap)
            {
                $this->cap = $cap;
            }

            public function active_hook($name)
            {
                return $name === 'structure_before_save_listing';
            }

            public function call($name, $listing)
            {
                $this->cap->hook_payloads[] = $listing;

                return 'invalid-hook-payload';
            }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;

            public function __construct($cap)
            {
                $this->cap = $cap;
            }

            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new eeDbResultMock([]);
            }

            public function update_string($table, $data, $where)
            {
                return 'UPDATE';
            }

            public function insert_string($table, $data)
            {
                $this->cap->insert_payloads[] = $data;

                return 'INSERT';
            }

            public function query($sql)
            {
                return new eeDbResultMock([]);
            }
        });

        $data = [[
            'entry_id' => 12,
            'channel_id' => 3,
            'parent_id' => 5,
            'template_id' => 2,
            'parent_uri' => '/parent',
            'uri' => 'three',
            'site_id' => 1,
        ]];

        $this->structure->set_listings($data);

        $this->assertCount(1, $captured->hook_payloads);
        $this->assertArrayNotHasKey('parent_uri', $captured->hook_payloads[0]);
        $this->assertCount(1, $captured->insert_payloads);
        $this->assertSame('three', $captured->insert_payloads[0]['uri']);
        $this->assertArrayNotHasKey('parent_uri', $captured->insert_payloads[0]);
    }

    /**
     * Preserve parent_uri when parent_id is absent from the listing payload.
     *
     * @return void
     */
    public function testSetListingsPreservesParentUriWhenParentIdIsMissing()
    {
        $captured = (object) ['update_payloads' => []];

        ee()->config->items['site_id'] = 1;

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }

            public function call($name)
            {
                return null;
            }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;

            public function __construct($cap)
            {
                $this->cap = $cap;
            }

            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new eeDbResultMock([['entry_id' => $where['entry_id']]]);
            }

            public function update_string($table, $data, $where)
            {
                $this->cap->update_payloads[] = $data;

                return 'UPDATE';
            }

            public function insert_string($table, $data)
            {
                return 'INSERT';
            }

            public function query($sql)
            {
                return new eeDbResultMock([]);
            }
        });

        $data = [[
            'entry_id' => 13,
            'channel_id' => 4,
            'template_id' => 7,
            'parent_uri' => '/orphan',
            'uri' => 'four',
            'site_id' => 1,
        ]];

        $this->structure->set_listings($data);

        $this->assertCount(1, $captured->update_payloads);
        $this->assertSame('/orphan', $captured->update_payloads[0]['parent_uri']);
        $this->assertSame('four', $captured->update_payloads[0]['uri']);
    }
}

