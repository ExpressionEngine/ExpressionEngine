<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureOrderEntriesTest extends StructureTestBase
{
    public function testOrderEntriesConcatenatesEntryIdsWithDelimiter()
    {
        $this->structure->sql = new class {
            public function get_data()
            {
                return [
                    ['entry_id' => 5],
                    ['entry_id' => 7],
                    ['entry_id' => 9],
                ];
            }
        };

        $this->setTemplateParams(['delimiter' => ',']);
        $out = $this->structure->order_entries();
        $this->assertSame('5,7,9', $out);
    }

    public function testOrderEntriesDefaultsToPipeDelimiter()
    {
        $this->structure->sql = new class {
            public function get_data() { return [['entry_id' => 1], ['entry_id' => 2]]; }
        };
        $out = $this->structure->order_entries();
        $this->assertSame('1|2', $out);
    }

    public function testOrderEntriesWithNoPagesReturnsEmptyString()
    {
        $this->structure->sql = new class { public function get_data() { return []; } };
        $out = $this->structure->order_entries();
        $this->assertSame('', $out);
    }

    public function testOrderEntriesSingleEntryNoTrailingDelimiter()
    {
        $this->structure->sql = new class { public function get_data() { return [['entry_id' => 99]]; } };
        $out = $this->structure->order_entries();
        $this->assertSame('99', $out);
    }

    public function testOrderEntriesReturnsEmptyStringWhenSqlReturnsNull()
    {
        $this->structure->sql = new class { public function get_data() { return null; } };
        $out = $this->structure->order_entries();
        $this->assertSame('', $out);
    }

    public function testOrderEntriesReturnsEmptyStringForEmptyTraversablePages()
    {
        $this->structure->sql = new class {
            public function get_data()
            {
                return new ArrayIterator([]);
            }
        };

        $out = $this->structure->order_entries();
        $this->assertSame('', $out);
    }
}

