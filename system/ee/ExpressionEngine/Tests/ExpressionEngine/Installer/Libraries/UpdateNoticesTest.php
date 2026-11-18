<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Libraries;

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/installer/libraries/Update_notices.php';

class UpdateNoticesTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testHeaderCreatesTableWhenMissingAndInsertsHeaderRow()
    {
        $db = new class {
            public $data_cache = ['cached' => true];
            public $tableExists = false;
            public $tableExistsCalls = [];
            public $insertCalls = [];
            public $truncateCalls = [];

            public function table_exists($table)
            {
                $this->tableExistsCalls[] = $table;

                return $this->tableExists;
            }

            public function insert($table, $data)
            {
                $this->insertCalls[] = [$table, $data];

                return true;
            }

            public function get($table)
            {
                return new class {
                    public function result()
                    {
                        return [];
                    }
                };
            }

            public function truncate($table)
            {
                $this->truncateCalls[] = $table;

                return true;
            }
        };

        $load = new class {
            public $dbforgeCalls = 0;
            public $libraryCalls = [];

            public function dbforge()
            {
                $this->dbforgeCalls++;
            }

            public function library($name)
            {
                $this->libraryCalls[] = $name;
            }
        };

        $dbforge = new class {
            public $addFieldCalls = [];
            public $addKeyCalls = [];

            public function add_field($fields)
            {
                $this->addFieldCalls[] = $fields;
            }

            public function add_key($key, $primary = false)
            {
                $this->addKeyCalls[] = [$key, $primary];
            }
        };

        $smartforge = new class {
            public $createTableCalls = [];

            public function create_table($table)
            {
                $this->createTableCalls[] = $table;
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('load', $load);
        ee()->setMock('dbforge', $dbforge);
        ee()->setMock('smartforge', $smartforge);

        $notices = new \Update_notices();
        $notices->setVersion('7.6.0');
        $notices->header('Header message');

        $this->assertSame([], $db->data_cache);
        $this->assertSame(['update_notices'], $db->tableExistsCalls);
        $this->assertSame(1, $load->dbforgeCalls);
        $this->assertSame(['smartforge'], $load->libraryCalls);
        $this->assertCount(1, $dbforge->addFieldCalls);
        $this->assertSame([['notice_id', true]], $dbforge->addKeyCalls);
        $this->assertSame(['update_notices'], $smartforge->createTableCalls);

        $this->assertCount(1, $db->insertCalls);
        $this->assertSame('update_notices', $db->insertCalls[0][0]);
        $this->assertSame(
            ['version' => '7.6.0', 'message' => 'Header message', 'is_header' => 1],
            $db->insertCalls[0][1]
        );
    }

    public function testItemSkipsTableCreationWhenTableExistsAndStoresNonHeaderRow()
    {
        $db = new class {
            public $data_cache = ['cached' => true];
            public $tableExists = true;
            public $insertCalls = [];

            public function table_exists($table)
            {
                return $this->tableExists;
            }

            public function insert($table, $data)
            {
                $this->insertCalls[] = [$table, $data];

                return true;
            }

            public function get($table)
            {
                return new class {
                    public function result()
                    {
                        return [];
                    }
                };
            }

            public function truncate($table)
            {
                return true;
            }
        };

        $load = new class {
            public $dbforgeCalls = 0;
            public $libraryCalls = [];

            public function dbforge()
            {
                $this->dbforgeCalls++;
            }

            public function library($name)
            {
                $this->libraryCalls[] = $name;
            }
        };

        $dbforge = new class {
            public $addFieldCalls = [];
            public $addKeyCalls = [];

            public function add_field($fields)
            {
                $this->addFieldCalls[] = $fields;
            }

            public function add_key($key, $primary = false)
            {
                $this->addKeyCalls[] = [$key, $primary];
            }
        };

        $smartforge = new class {
            public $createTableCalls = [];

            public function create_table($table)
            {
                $this->createTableCalls[] = $table;
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('load', $load);
        ee()->setMock('dbforge', $dbforge);
        ee()->setMock('smartforge', $smartforge);

        $notices = new \Update_notices();
        $notices->setVersion('7.6.1');
        $notices->item('Regular message');

        $this->assertSame([], $db->data_cache);
        $this->assertSame(0, $load->dbforgeCalls);
        $this->assertSame([], $load->libraryCalls);
        $this->assertSame([], $dbforge->addFieldCalls);
        $this->assertSame([], $dbforge->addKeyCalls);
        $this->assertSame([], $smartforge->createTableCalls);
        $this->assertCount(1, $db->insertCalls);
        $this->assertSame(
            ['version' => '7.6.1', 'message' => 'Regular message', 'is_header' => 0],
            $db->insertCalls[0][1]
        );
    }

    public function testGetReturnsRowsFromUpdateNoticesTable()
    {
        $rows = [
            ['notice_id' => 1, 'message' => 'one', 'version' => '7.6.0', 'is_header' => 0],
            ['notice_id' => 2, 'message' => 'two', 'version' => '7.6.0', 'is_header' => 1],
        ];

        $db = new class($rows) {
            public $data_cache = ['cached' => true];
            public $tableExists = true;
            public $getCalls = [];
            private $rows;

            public function __construct($rows)
            {
                $this->rows = $rows;
            }

            public function table_exists($table)
            {
                return $this->tableExists;
            }

            public function insert($table, $data)
            {
                return true;
            }

            public function get($table)
            {
                $this->getCalls[] = $table;

                return new class($this->rows) {
                    private $rows;

                    public function __construct($rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result()
                    {
                        return array_map(function ($row) {
                            return (object) $row;
                        }, $this->rows);
                    }
                };
            }

            public function truncate($table)
            {
                return true;
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('load', new class {
            public function dbforge()
            {
            }

            public function library($name)
            {
            }
        });
        ee()->setMock('dbforge', new class {
            public function add_field($fields)
            {
            }

            public function add_key($key, $primary = false)
            {
            }
        });
        ee()->setMock('smartforge', new class {
            public function create_table($table)
            {
            }
        });

        $notices = new \Update_notices();
        $result = $notices->get();

        $this->assertSame([], $db->data_cache);
        $this->assertSame(['update_notices'], $db->getCalls);
        $this->assertCount(2, $result);
        $this->assertSame('one', $result[0]->message);
        $this->assertSame(1, $result[1]->is_header);
    }

    public function testClearTruncatesTableAfterEnsuringItExists()
    {
        $db = new class {
            public $data_cache = ['cached' => true];
            public $tableExists = true;
            public $truncateCalls = [];

            public function table_exists($table)
            {
                return $this->tableExists;
            }

            public function insert($table, $data)
            {
                return true;
            }

            public function get($table)
            {
                return new class {
                    public function result()
                    {
                        return [];
                    }
                };
            }

            public function truncate($table)
            {
                $this->truncateCalls[] = $table;

                return true;
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('load', new class {
            public function dbforge()
            {
            }

            public function library($name)
            {
            }
        });
        ee()->setMock('dbforge', new class {
            public function add_field($fields)
            {
            }

            public function add_key($key, $primary = false)
            {
            }
        });
        ee()->setMock('smartforge', new class {
            public function create_table($table)
            {
            }
        });

        $notices = new \Update_notices();
        $notices->clear();

        $this->assertSame([], $db->data_cache);
        $this->assertSame(['update_notices'], $db->truncateCalls);
    }
}
