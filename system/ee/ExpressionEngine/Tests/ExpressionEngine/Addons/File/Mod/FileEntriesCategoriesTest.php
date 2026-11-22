<?php

use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'file/mod.file.php';
require_once BASEPATH . 'libraries/Functions.php';
require_once BASEPATH . 'database/DB_driver.php';
require_once BASEPATH . 'database/DB_active_rec.php';

class FileEntriesCategoriesTest extends TestCase
{
    /**
     * Prepare file and category fixtures in an isolated database.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        ee()->resetMocks();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO SQLite is required for the file query fixtures.');
        }

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("
            CREATE TABLE exp_files (file_id INTEGER, site_id INTEGER, upload_location_id INTEGER, model_type TEXT, upload_date INTEGER);
            CREATE TABLE exp_categories (cat_id INTEGER, group_id INTEGER);
            CREATE TABLE exp_file_categories (file_id INTEGER, cat_id INTEGER);
            CREATE TABLE exp_upload_prefs (id INTEGER);
            INSERT INTO exp_files VALUES
                (1, 1, 6, 'File', 1), (2, 1, 6, 'File', 2),
                (3, 1, 6, 'File', 3), (4, 1, 6, 'File', 4),
                (5, 2, 8, 'File', 5), (6, 1, 7, 'File', 6),
                (7, 1, 6, 'File', 7), (8, 1, 6, 'File', 8),
                (9, 1, 6, 'FileDirectory', 9);
            INSERT INTO exp_categories VALUES (8, 1), (24, 2), (30, 1), (40, 3);
            INSERT INTO exp_file_categories VALUES
                (1, 8), (2, 24), (3, 8), (3, 24),
                (4, 8), (4, 24), (4, 30), (4, 40),
                (5, 8), (5, 24), (6, 8), (6, 24),
                (8, 30), (9, 8), (9, 24);
            INSERT INTO exp_upload_prefs VALUES (6), (7), (8);
        ");

        ee()->setMock('db', new FileEntriesCategoriesDatabase($pdo));
        ee()->setMock('functions', new EE_Functions());
        ee()->setMock('config', new FakeConfig());
        ee()->setMock('uri', (object) [
            'page_query_string' => '', 'query_string' => '', 'uri_string' => '',
        ]);
    }

    /**
     * Release the shared ExpressionEngine mocks.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
        parent::tearDown();
    }

    /**
     * Verify category filters return the intended files.
     *
     * @dataProvider categoryFilters
     * @param string $category
     * @param array $expected
     * @return void
     */
    public function testCategoryFilters($category, array $expected): void
    {
        $this->assertSame($expected, $this->getFiles(['category' => $category]));
    }

    /**
     * Supply category combinations and their matching file IDs.
     *
     * @return array
     */
    public function categoryFilters(): array
    {
        return [
            'single category' => ['8', [1, 3, 4, 6]],
            'either category' => ['8|24', [1, 2, 3, 4, 6]],
            'both categories' => ['8&24', [3, 4, 6]],
            'three categories' => ['8&24&30', [4]],
            'reversed categories' => ['24&8', [3, 4, 6]],
            'repeated category' => ['8&8&24', [3, 4, 6]],
            'surrounding whitespace' => [' 8 & 24 ', [3, 4, 6]],
            'missing category' => ['8&99', []],
            'quoted category value' => ["8&24') OR 1=1 --", []],
            'exclude intersection' => ['not 8&24', [1, 2, 7, 8]],
            'exclude missing intersection' => ['not 8&99', [1, 2, 3, 4, 6, 7, 8]],
            'existing pipe exclusion' => ['not 8|24', [4, 7, 8]],
        ];
    }

    /**
     * Preserve the other file query filters with an intersection.
     *
     * @return void
     */
    public function testIntersectionPreservesOtherFilters(): void
    {
        $this->assertSame([3, 4], $this->getFiles(['category' => '8&24', 'directory_id' => '6']));
        $this->assertSame([4], $this->getFiles(['category' => '8&24', 'category_group' => '3']));
        $this->assertSame([6], $this->getFiles(['category' => '8&24', 'file_id' => '6']));
        $this->assertSame([4], $this->getFiles(['category' => '8&24', 'limit' => '1', 'offset' => '1']));
        $this->assertSame([1, 2, 8], $this->getFiles(['category' => 'not 8&24', 'uncategorized_entries' => 'n']));
    }

    /**
     * Count and paginate distinct matching files.
     *
     * @return void
     */
    public function testIntersectionPagination(): void
    {
        $pagination = new class {
            public $paginate = true;
            public $offset = 1;
            public $per_page;
            public $total;

            /**
             * Record the pagination total and page size.
             *
             * @param int $total
             * @param int $limit
             * @return void
             */
            public function build($total, $limit): void
            {
                $this->total = $total;
                $this->per_page = $limit;
            }
        };

        $this->assertSame([4], $this->getFiles(['category' => '8&24', 'limit' => '1'], $pagination));
        $this->assertSame(3, $pagination->total);
    }

    /**
     * Run the production file query and return its matching file IDs.
     *
     * @param array $params
     * @param object|null $pagination
     * @return array
     */
    private function getFiles(array $params, $pagination = null): array
    {
        $template = new class extends FakeTemplate {
            /**
             * Match the production template's default for missing parameters.
             *
             * @param string $key
             * @param mixed $default
             * @return mixed
             */
            public function fetch_param($key, $default = false)
            {
                return parent::fetch_param($key, $default);
            }
        };
        $template->site_ids = [1];
        $template->setMap(array_merge(['dynamic' => 'no', 'sort' => 'asc'], $params));
        ee()->setMock('TMPL', $template);

        $file = new File();
        $file->enable = ['categories' => true, 'pagination' => true];
        $pagination = $pagination ?? (object) ['paginate' => false];
        $method = new ReflectionMethod($file, '_get_file_data');
        TestReflectionHelper::makeMethodAccessible($method);
        $result = $method->invoke($file, $pagination);

        return is_array($result) ? [] : array_map('intval', array_column($result->result_array(), 'file_id'));
    }
}

class FileEntriesCategoriesDatabase extends CI_DB_active_record
{
    private $pdo;
    protected $_random_keyword = 'RANDOM()';

    /**
     * Attach the in-memory fixture database to the production query builder.
     *
     * @param PDO $pdo
     * @return void
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        parent::__construct(['dbprefix' => 'exp_']);
    }

    /**
     * Execute a generated query against the fixtures.
     *
     * @param string $sql
     * @param mixed $binds
     * @param bool $return_object
     * @return eeDbResultMock
     * @throws PDOException
     */
    public function query($sql, $binds = false, $return_object = true)
    {
        return new eeDbResultMock($this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Escape a value using the fixture database.
     *
     * @param string $str
     * @param bool $like
     * @return string
     */
    public function escape_str($str, $like = false)
    {
        return substr($this->pdo->quote($str), 1, -1);
    }

    /**
     * Keep the static fixture identifiers unchanged.
     *
     * @param string $item
     * @return string
     */
    protected function _escape_identifiers($item)
    {
        return $item;
    }

    /**
     * Join the query's table names.
     *
     * @param array $tables
     * @return string
     */
    protected function _from_tables($tables)
    {
        return implode(', ', $tables);
    }

    /**
     * Apply the page size and offset to the fixture query.
     *
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @return string
     */
    protected function _limit($sql, $limit, $offset)
    {
        return $sql . ' LIMIT ' . $limit . ($offset ? ' OFFSET ' . $offset : '');
    }
}
