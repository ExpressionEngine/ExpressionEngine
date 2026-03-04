<?php

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../Addons/structure/helper.php';

use PHPUnit\Framework\TestCase;

if (!defined('STRUCTURE_VERSION')) {
    define('STRUCTURE_VERSION', '7.5.14');
}

class StructureHelperDbResult
{
    public $num_rows;
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function result()
    {
        return array_map(function ($row) {
            return (object) $row;
        }, $this->rows);
    }
}

class StructureHelperDbMock
{
    public $deletedIds = [];
    public $updates = [];
    public $inserts = [];
    public $whereCalls = [];

    private $fromTable;
    private $whereMap = [];
    private $staleRows = [];
    private $siteRows = [];
    private $structureRows = [];

    public function __construct(array $staleRows, array $siteRows, array $structureRows)
    {
        $this->staleRows = $staleRows;
        $this->siteRows = $siteRows;
        $this->structureRows = $structureRows;
    }

    public function order_by($field, $direction = '')
    {
        return $this;
    }

    public function select($fields = '*')
    {
        return $this;
    }

    public function from($table)
    {
        $this->fromTable = $table;
        return $this;
    }

    public function where($field, $value = null)
    {
        $this->whereMap[$field] = $value;
        $this->whereCalls[] = [$field, $value];
        return $this;
    }

    public function where_in($field, $values)
    {
        if ($field === 'id') {
            $this->deletedIds = array_values((array) $values);
        }
        return $this;
    }

    public function delete($table)
    {
        return true;
    }

    public function limit($limit)
    {
        return $this;
    }

    public function update($table, $data = null)
    {
        $this->updates[] = [$table, $data];
        return true;
    }

    public function insert($table, $data = null)
    {
        $this->inserts[] = [$table, $data];
        return true;
    }

    public function get($table = null, $limit = null, $offset = null)
    {
        if ($table === 'structure_nav_history') {
            return new StructureHelperDbResult($this->staleRows);
        }

        $useTable = $table ?: $this->fromTable;

        if ($useTable === 'sites') {
            $rows = $this->siteRows;
            if (isset($this->whereMap['site_id'])) {
                $rows = array_values(array_filter($rows, function ($row) {
                    return (int) $row['site_id'] === (int) $this->whereMap['site_id'];
                }));
            }
            $this->resetBuilderState();
            return new StructureHelperDbResult($rows);
        }

        if ($useTable === 'structure') {
            $rows = $this->structureRows;
            if (isset($this->whereMap['site_id'])) {
                $rows = array_values(array_filter($rows, function ($row) {
                    return (int) $row['site_id'] === (int) $this->whereMap['site_id'];
                }));
            }
            $this->resetBuilderState();
            return new StructureHelperDbResult($rows);
        }

        $this->resetBuilderState();
        return new StructureHelperDbResult([]);
    }

    private function resetBuilderState(): void
    {
        $this->fromTable = null;
        $this->whereMap = [];
    }
}

class HelperTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->setMock('config', new class {
            public $items = [
                'structure_nav_history' => 'y',
                'structure_nav_history_states' => 1,
            ];
            public function item($name)
            {
                return $this->items[$name] ?? null;
            }
        });
    }

    public function testAddStructureNavRevisionReturnsEarlyWhenHistoryDisabled()
    {
        ee()->config->items['structure_nav_history'] = 'n';

        $db = new StructureHelperDbMock([], [], []);
        ee()->setMock('db', $db);

        add_structure_nav_revision('all', 'skip');

        $this->assertSame([], $db->inserts);
        $this->assertSame([], $db->updates);
    }

    public function testAddStructureNavRevisionPrunesAndInsertsSnapshots()
    {
        $stale = [['id' => 10], ['id' => 11]];
        $sites = [
            ['site_id' => 1, 'site_pages' => 'pages-1'],
            ['site_id' => 2, 'site_pages' => 'pages-2'],
        ];
        $structureRows = [
            ['site_id' => 1, 'entry_id' => 100],
            ['site_id' => 2, 'entry_id' => 200],
        ];

        $db = new StructureHelperDbMock($stale, $sites, $structureRows);
        ee()->setMock('db', $db);

        add_structure_nav_revision('all', 'saved');

        $this->assertSame([10, 11], $db->deletedIds);
        $this->assertCount(2, $db->inserts);
        $this->assertSame('structure_nav_history', $db->inserts[0][0]);
        $this->assertSame('saved', $db->inserts[0][1]['note']);
        $this->assertSame(STRUCTURE_VERSION, $db->inserts[0][1]['structure_version']);
        $this->assertCount(2, $db->updates);
    }

    public function testAddStructureNavRevisionFiltersToSpecificSiteId()
    {
        $sites = [
            ['site_id' => 1, 'site_pages' => 'pages-1'],
            ['site_id' => 2, 'site_pages' => 'pages-2'],
        ];
        $structureRows = [
            ['site_id' => 1, 'entry_id' => 100],
            ['site_id' => 2, 'entry_id' => 200],
        ];

        $db = new StructureHelperDbMock([], $sites, $structureRows);
        ee()->setMock('db', $db);

        add_structure_nav_revision(2, 'single-site');

        $this->assertContains(['site_id', 2], $db->whereCalls);
        $this->assertCount(1, $db->inserts);
        $this->assertSame(2, $db->inserts[0][1]['site_id']);
    }

    public function testStructureArrayGetAndPickAndHelperMethods()
    {
        $this->assertSame('/foo/bar', Structure_Helper::remove_double_slashes('//foo///bar'));
        $this->assertSame('/foo/bar', Structure_Helper::tidy_url('foo//bar'));
        $this->assertSame('c', Structure_Helper::get_slug('/a/b/c/'));
        $this->assertSame('resolved', Structure_Helper::resolveValue(function () {
            return 'resolved';
        }));
        $this->assertSame('default', structure_array_get(['a' => []], 'a:b', function () {
            return 'default';
        }));
        $this->assertSame('value', structure_array_get(['a' => ['b' => 'value']], 'a:b'));
        $this->assertSame(['k' => 'v'], structure_array_get(['k' => 'v'], null));
        $this->assertSame('first', pick(null, '', 'first', 'second'));
        $this->assertNull(pick('', null));
        $this->assertNull(pick());
    }

}
