<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\Cache;

/**
 * Base class for Cache_database driver tests
 */
abstract class CacheDatabaseTestBase extends CacheTestBase
{
    /**
     * Mock database query result
     *
     * @var object
     */
    protected $mockQueryResult;

    /**
     * Mock dbforge
     *
     * @var object
     */
    protected $mockDbforge;

    /**
     * Set up test environment with database mocks
     */
    public function setUp(): void
    {
        parent::setUp();

        // Load the database driver
        $this->loadDatabaseDriver();

        // Set up database mocks
        $this->setupDatabaseMocks();
    }

    /**
     * Load the Cache_database driver file
     */
    protected function loadDatabaseDriver()
    {
        $driverPath = BASEPATH . 'libraries/Cache/drivers/Cache_database.php';
        if (file_exists($driverPath)) {
            require_once $driverPath;
        }
    }

    /**
     * Set up database mocks for testing
     */
    protected function setupDatabaseMocks()
    {
        // Create a mock query result object
        $this->mockQueryResult = new class {
            public $rows = [];
            public $numRows = 0;

            public function num_rows()
            {
                return $this->numRows;
            }

            public function row()
            {
                return $this->numRows > 0 ? $this->rows[0] : null;
            }

            public function setRows($rows)
            {
                $this->rows = $rows;
                $this->numRows = count($rows);
            }
        };

        // Create a fluent mock database object
        $mockDb = new class {
            public $queryResult;
            public $insertCalled = false;
            public $updateCalled = false;
            public $deleteCalled = false;
            public $selectCalled = false;
            public $whereCalled = false;
            public $likeCalled = false;
            public $fromCalled = false;
            public $getCalled = false;
            public $lastInsertData = [];
            public $lastUpdateData = [];
            public $lastWhereKey = null;
            public $lastWhereValue = null;
            public $lastLikeField = null;
            public $lastLikeValue = null;
            public $lastLikeSide = null;
            public $tableExistsReturn = true;

            public function select($fields)
            {
                $this->selectCalled = true;
                return $this;
            }

            public function from($table)
            {
                $this->fromCalled = true;
                return $this;
            }

            public function where($key, $value = null)
            {
                $this->whereCalled = true;
                $this->lastWhereKey = $key;
                $this->lastWhereValue = $value;
                return $this;
            }

            public function like($field, $value, $side = 'both')
            {
                $this->likeCalled = true;
                $this->lastLikeField = $field;
                $this->lastLikeValue = $value;
                $this->lastLikeSide = $side;
                return $this;
            }

            public function get()
            {
                $this->getCalled = true;
                return $this->queryResult;
            }

            public function insert($table, $data)
            {
                $this->insertCalled = true;
                $this->lastInsertData = $data;
                return true;
            }

            public function update($table, $data)
            {
                $this->updateCalled = true;
                $this->lastUpdateData = $data;
                return true;
            }

            public function delete($table)
            {
                $this->deleteCalled = true;
                return true;
            }

            public function table_exists($table)
            {
                return $this->tableExistsReturn;
            }

            public function reset()
            {
                $this->insertCalled = false;
                $this->updateCalled = false;
                $this->deleteCalled = false;
                $this->selectCalled = false;
                $this->whereCalled = false;
                $this->likeCalled = false;
                $this->fromCalled = false;
                $this->getCalled = false;
                $this->lastInsertData = [];
                $this->lastUpdateData = [];
                $this->lastWhereKey = null;
                $this->lastWhereValue = null;
                $this->lastLikeField = null;
                $this->lastLikeValue = null;
                $this->lastLikeSide = null;
            }
        };

        // Assign the query result to the mock db
        $mockDb->queryResult = $this->mockQueryResult;

        // Set the mock database on the EE object
        ee()->setMock('db', $mockDb);

        // Create a mock dbforge object
        $this->mockDbforge = new class {
            public $fields = [];
            public $keys = [];
            public $primaryKeys = [];
            public $createTableCalled = false;
            public $createTableReturn = true;

            public function add_field($fields)
            {
                $this->fields = array_merge($this->fields, $fields);
                return $this;
            }

            public function add_key($key, $primary = false)
            {
                if ($primary) {
                    $this->primaryKeys[] = $key;
                } else {
                    $this->keys[] = $key;
                }
                return $this;
            }

            public function create_table($table, $if_not_exists = false)
            {
                $this->createTableCalled = true;
                return $this->createTableReturn;
            }

            public function reset()
            {
                $this->fields = [];
                $this->keys = [];
                $this->primaryKeys = [];
                $this->createTableCalled = false;
            }
        };

        ee()->setMock('dbforge', $this->mockDbforge);
    }

    /**
     * Create a Cache_database driver instance
     *
     * @return \EE_Cache_database
     */
    protected function makeDatabaseDriver(): \EE_Cache_database
    {
        return new \EE_Cache_database();
    }

    /**
     * Set up a mock query result with specific data
     *
     * @param array $rows Array of row objects to return
     */
    protected function setQueryResult(array $rows)
    {
        $this->mockQueryResult->setRows($rows);
    }

    /**
     * Create a mock cache row object
     *
     * @param string $data The serialized data
     * @param int $ttl The TTL in seconds
     * @param int $createdAt The created timestamp
     * @return object
     */
    protected function makeCacheRow($data, $ttl = 60, $createdAt = null)
    {
        if ($createdAt === null) {
            $createdAt = ee()->localize->now;
        }

        return (object) [
            'data' => $data,
            'ttl' => $ttl,
            'created_at' => $createdAt
        ];
    }

    /**
     * Reset all database mocks
     */
    protected function resetDatabaseMocks()
    {
        ee()->db->reset();
        $this->mockDbforge->reset();
        $this->setQueryResult([]);
    }

    /**
     * Invoke a protected or private method on an object
     *
     * @param object $object The object
     * @param string $method The method name
     * @param array $args The arguments to pass
     * @return mixed The method return value
     */
    protected function invokeProtectedMethod($object, string $method, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
