<?php

use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'file/mod.file.php';

class FileEntriesPaginationTest extends TestCase
{
    private $db;
    private $template;
    private $pagination;
    private $file;

    protected function setUp(): void
    {
        parent::setUp();

        ee()->resetMocks();

        $this->db = new FileEntriesPaginationDbMock();
        $this->template = new FakeTemplate();
        $this->pagination = new FileEntriesPaginationObjectMock();

        $this->template->site_ids = [1];
        $this->template->setMap([
            'category_group' => '1',
            'directory_id' => '6',
            'dynamic' => 'no',
            'limit' => '12',
            'uncategorized_entries' => 'no',
        ]);

        ee()->setMock('db', $this->db);
        ee()->setMock('TMPL', $this->template);
        ee()->setMock('functions', new FileEntriesPaginationFunctionsMock());
        ee()->setMock('config', new FakeConfig());
        ee()->setMock('uri', new class {
            public $page_query_string = '';
            public $query_string = '';
            public $uri_string = '';
        });

        $this->file = new File();
        $this->file->enable = [
            'categories' => true,
            'pagination' => true,
        ];
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();

        parent::tearDown();
    }

    public function testCategoryGroupPaginationCountsAndLimitsDistinctFiles()
    {
        $result = $this->invokeGetFileData();

        $this->assertSame([[3, 12]], $this->pagination->builds);
        $this->assertStringContainsString('COUNT(DISTINCT exp_files.file_id)', $this->db->queries[0]);
        $this->assertSame([12, 0], $this->db->limitCalls[0]);
        $this->assertSame(3, $result->num_rows());
        $this->assertDistinctWasReappliedBeforeLimit();
    }

    private function invokeGetFileData()
    {
        $method = new ReflectionMethod($this->file, '_get_file_data');
        TestReflectionHelper::makeMethodAccessible($method);

        return $method->invoke($this->file, $this->pagination);
    }

    private function assertDistinctWasReappliedBeforeLimit()
    {
        $resetIndex = array_search('reset_select', $this->db->events, true);
        $limitIndex = array_search('limit', $this->db->events, true);

        $this->assertNotFalse($resetIndex);
        $this->assertNotFalse($limitIndex);

        $eventsBetweenResetAndLimit = array_slice(
            $this->db->events,
            $resetIndex + 1,
            $limitIndex - $resetIndex - 1
        );

        $this->assertContains('distinct', $eventsBetweenResetAndLimit);
    }
}

class FileEntriesPaginationDbMock
{
    public $events = [];
    public $queries = [];
    public $limitCalls = [];
    private $whereConditions = [];

    public function start_cache()
    {
        return $this;
    }

    public function stop_cache()
    {
        return $this;
    }

    public function flush_cache()
    {
        return $this;
    }

    public function distinct()
    {
        $this->events[] = 'distinct';

        return $this;
    }

    public function join()
    {
        return $this;
    }

    public function select()
    {
        return $this;
    }

    public function from()
    {
        return $this;
    }

    public function where($field, $value = null)
    {
        $this->whereConditions[$field] = $value;

        return $this;
    }

    public function where_in($field, $values)
    {
        $this->whereConditions[$field] = $values;

        return $this;
    }

    public function order_by()
    {
        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $this->events[] = 'limit';
        $this->limitCalls[] = [$limit, $offset];

        return $this;
    }

    public function _compile_select($selectOverride = false)
    {
        return $selectOverride . "\nFROM exp_files";
    }

    public function protect_identifiers($item)
    {
        return '`' . $item . '`';
    }

    public function query($sql)
    {
        $this->queries[] = $sql;

        return new eeDbResultMock([
            ['numrows' => 3],
        ]);
    }

    public function _reset_select()
    {
        $this->events[] = 'reset_select';

        return $this;
    }

    public function count_all_results()
    {
        return 99;
    }

    public function get($table = '')
    {
        if ($table === 'files') {
            return new eeDbResultMock([
                ['file_id' => 1, 'title' => 'File 1'],
                ['file_id' => 2, 'title' => 'File 2'],
                ['file_id' => 3, 'title' => 'File 3'],
            ]);
        }

        return new eeDbResultMock([
            ['file_id' => 1],
            ['file_id' => 2],
            ['file_id' => 3],
        ]);
    }
}

class FileEntriesPaginationFunctionsMock
{
    public function ar_andor_string($str, $field)
    {
        ee()->db->where($field, $str);
    }
}

class FileEntriesPaginationObjectMock
{
    public $paginate = true;
    public $per_page = 0;
    public $offset = 0;
    public $builds = [];

    public function build($totalItems, $perPage)
    {
        $this->builds[] = [$totalItems, $perPage];
        $this->per_page = $perPage;
    }
}
