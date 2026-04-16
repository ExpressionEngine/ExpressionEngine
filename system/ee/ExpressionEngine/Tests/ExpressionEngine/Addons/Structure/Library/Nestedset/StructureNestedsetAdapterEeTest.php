<?php

require_once __DIR__ . '/../../../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../../../Addons/structure/libraries/nestedset/structure_nestedset_adapter_ee.php';

use PHPUnit\Framework\TestCase;

class StructureNestedsetAdapterEeTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 1;
            }
        });
    }

    public function testGetNodeByIdAndGetNodeBySetFormatAndMissPaths()
    {
        $db = new class {
            public $results = [];
            public $queries = [];
            public function query($sql)
            {
                $this->queries[] = $sql;
                $result = array_shift($this->results);
                return $result;
            }
        };
        ee()->setMock('db', $db);

        $db->results[] = $this->result([], 0);
        $adapter = new Structure_Nestedset_Adapter_Ee('exp_structure', 'lft', 'rgt', 'entry_id');
        $this->assertFalse($adapter->getNodeById(10));

        $db->results[] = $this->result([[
            'entry_id' => 5,
            'lft' => 2,
            'rgt' => 3,
            'depth' => 1,
            'isLeaf' => 1,
            'numChildren' => 0,
            'title' => 'Node',
        ]], 1);
        $this->assertSame(
            ['id' => 5, 'left' => 2, 'right' => 3, 'depth' => 1, 'isLeaf' => 1, 'numChildren' => 0, 'title' => 'Node'],
            $adapter->getNodeById(5)
        );

        $db->results[] = $this->result([], 0);
        $db->results[] = $this->result([], 0);
        $this->assertFalse($adapter->getNodeBySet(2, 3));

        $db->results[] = $this->result([], 0);
        $db->results[] = $this->result([[
            'entry_id' => 6,
            'lft' => 4,
            'rgt' => 7,
            'depth' => 1,
            'isLeaf' => 0,
            'numChildren' => 1,
            'title' => 'Parent',
        ]], 1);
        $node = $adapter->getNodeBySet(4, 7);
        $this->assertSame(6, $node['id']);
        $this->assertSame(4, $node['left']);
        $this->assertSame(7, $node['right']);
        $this->assertStringContainsString("SET SESSION sql_mode = ''", implode("\n", $db->queries));
    }

    public function testGetTreeGetDepthAndWriteOperations()
    {
        $db = new class {
            public $results = [];
            public $queries = [];
            public function query($sql)
            {
                $this->queries[] = $sql;
                return array_shift($this->results);
            }
            public function escape_str($value)
            {
                return addslashes($value);
            }
        };
        ee()->setMock('db', $db);

        $adapter = new class('exp_structure', 'lft', 'rgt', 'entry_id') extends Structure_Nestedset_Adapter_Ee {
            public function getNodeById($id)
            {
                return ['id' => $id, 'left' => 2, 'right' => 9];
            }
        };

        $db->results[] = $this->result([['entry_id' => 0], ['entry_id' => 1]], 2);
        $this->assertCount(2, $adapter->getTree(0));

        $db->results[] = $this->result([['entry_id' => 1]], 1);
        $this->assertCount(1, $adapter->getTree(['left' => 2, 'right' => 9], true));

        $db->results[] = $this->result([['entry_id' => 2]], 1);
        $this->assertCount(1, $adapter->getTree(1, false));

        $db->results[] = $this->result([], 0, ['depth' => 4]);
        set_error_handler(function () {
            return true;
        });
        $depth = $adapter->getDepth(1);
        restore_error_handler();
        $this->assertSame(4, $depth);

        $adapter->insertNode(8, 9, ['entry_id' => 100, 'title' => "O'Reilly"]);
        $adapter->shift(10, 2);
        $newSet = $adapter->shiftRange(4, 5, 8);
        $adapter->deleteNodeTree(20, 25);
        $adapter->begin();
        $adapter->end();

        $this->assertSame(['left' => 12, 'right' => 13], $newSet);
        $sql = implode("\n", $db->queries);
        $this->assertStringContainsString("INSERT INTO exp_structure", $sql);
        $this->assertStringContainsString("DELETE FROM exp_structure", $sql);
        $this->assertStringContainsString("LOCK TABLE exp_structure WRITE", $sql);
        $this->assertStringContainsString("UNLOCK TABLES", $sql);
    }

    private function result(array $rows, $numRows, ?array $rowProperty = null)
    {
        return new class($rows, $numRows, $rowProperty) {
            private $rows;
            public $num_rows;
            public $row;

            public function __construct($rows, $numRows, $rowProperty)
            {
                $this->rows = $rows;
                $this->num_rows = $numRows;
                $this->row = $rowProperty ?? [];
            }

            public function result_array()
            {
                return $this->rows;
            }
        };
    }
}
