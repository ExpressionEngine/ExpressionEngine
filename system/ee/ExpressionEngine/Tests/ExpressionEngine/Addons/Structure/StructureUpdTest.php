<?php

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../Addons/structure/upd.structure.php';

use PHPUnit\Framework\TestCase;

class StructureUpdTest extends TestCase
{
    private $upd;

    protected function setUp(): void
    {
        ee()->resetMocks();

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });

        $this->upd = (new ReflectionClass('Structure_upd'))->newInstanceWithoutConstructor();
        $this->upd->version = '9.9.9';
    }

    public function testTabsAndResolveChannelType()
    {
        $tabs = $this->upd->tabs();
        $this->assertArrayHasKey('structure', $tabs);
        $this->assertArrayHasKey('parent_id', $tabs['structure']);
        $this->assertArrayHasKey('listing_channel', $tabs['structure']);

        $this->assertSame('page', $this->upd->resolve_channel_type('structure'));
        $this->assertSame('asset', $this->upd->resolve_channel_type('asset'));
        $this->assertSame('unmanaged', $this->upd->resolve_channel_type('anything-else'));
    }

    public function testCreateTableMethodsOnlyRunWhenTableMissing()
    {
        $db = new class {
            public $existing = [];
            public function table_exists($table)
            {
                return $this->existing[$table] ?? false;
            }
        };
        $dbforge = new class {
            public $created = [];
            public $addFieldCalls = 0;
            public function add_field($fields)
            {
                $this->addFieldCalls++;
            }
            public function add_key($key, $primary = false)
            {
            }
            public function create_table($table)
            {
                $this->created[] = $table;
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('dbforge', $dbforge);

        $this->invokePrivate('create_table_structure');
        $this->invokePrivate('create_table_structure_settings');
        $this->invokePrivate('create_table_structure_members');
        $this->invokePrivate('create_table_structure_channels');
        $this->invokePrivate('create_table_structure_nav_history');
        $this->invokePrivate('create_table_structure_listings');

        $this->assertSame(
            ['structure', 'structure_settings', 'structure_members', 'structure_channels', 'structure_nav_history', 'structure_listings'],
            $dbforge->created
        );

        $db->existing = array_fill_keys($dbforge->created, true);
        $createdBefore = count($dbforge->created);

        $this->invokePrivate('create_table_structure');
        $this->invokePrivate('create_table_structure_settings');
        $this->invokePrivate('create_table_structure_members');
        $this->invokePrivate('create_table_structure_channels');
        $this->invokePrivate('create_table_structure_nav_history');
        $this->invokePrivate('create_table_structure_listings');

        $this->assertSame($createdBefore, count($dbforge->created));
    }

    public function testConfirmSitePagesTypeAndPreviewUrl()
    {
        $db = new class {
            public $queries = [];
            public $previewFieldExists = true;
            public function field_data($table)
            {
                return [
                    (object) ['name' => 'site_pages', 'type' => 'text'],
                    (object) ['name' => 'site_label', 'type' => 'varchar'],
                ];
            }
            public function field_exists($field, $table)
            {
                if ($field === 'preview_url' && $table === 'channels') {
                    return $this->previewFieldExists;
                }
                return false;
            }
            public function query($sql)
            {
                $this->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        };
        $dbforge = new class {
            public $modified = [];
            public function modify_column($table, $data)
            {
                $this->modified[] = [$table, $data];
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('dbforge', $dbforge);

        $this->invokePrivate('confirm_site_pages_type');
        $this->invokePrivate('confirm_preview_url');

        $this->assertNotEmpty($dbforge->modified);
        $this->assertStringContainsString('UPDATE exp_channels c', implode("\n", $db->queries));

        $db->previewFieldExists = false;
        $queryCount = count($db->queries);
        $this->invokePrivate('confirm_preview_url');
        $this->assertSame($queryCount, count($db->queries));
    }

    public function testUninstallRemovesModuleArtifacts()
    {
        $db = new class {
            public $whereCalls = [];
            public $deleteCalls = [];
            public function select($fields)
            {
                return $this;
            }
            public function get_where($table, $where = [])
            {
                return new eeDbResultMock([['module_id' => 22]]);
            }
            public function where($field, $value)
            {
                $this->whereCalls[] = [$field, $value];
                return $this;
            }
            public function delete($table)
            {
                $this->deleteCalls[] = $table;
                return true;
            }
        };
        $dbforge = new class {
            public $dropped = [];
            public function drop_table($table)
            {
                $this->dropped[] = $table;
            }
        };
        ee()->setMock('db', $db);
        ee()->setMock('dbforge', $dbforge);
        ee()->setMock('load', new class {
            public function dbforge()
            {
            }
            public function library($name)
            {
            }
        });
        ee()->setMock('layout', new class {
            public $deletedTabs = [];
            public function delete_layout_tabs($tabs)
            {
                $this->deletedTabs[] = $tabs;
            }
        });

        $this->assertTrue($this->upd->uninstall());
        $this->assertContains('modules', $db->deleteCalls);
        $this->assertContains('actions', $db->deleteCalls);
        $this->assertContains('structure', $dbforge->dropped);
        $this->assertNotEmpty(ee()->layout->deletedTabs);
    }

    public function testConstructorInitializesVersionAndSql()
    {
        ee()->setMock('load', new class {
            public function add_package_path($path)
            {
            }
            public function library($name)
            {
            }
            public function dbforge()
            {
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });

        $upd = new Structure_upd();

        $this->assertSame(STRUCTURE_VERSION, $upd->version);
        $this->assertInstanceOf(Sql_structure::class, $upd->sql);
    }

    public function testInstallCreatesSchemaAndSeedsSettings()
    {
        $captured = (object) [
            'queries' => [],
            'inserts' => [],
            'insertStrings' => [],
            'createdTables' => [],
            'addedColumns' => [],
            'modifiedColumns' => [],
            'layoutTabs' => [],
        ];

        $db = new class($captured) extends FakeDb {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                if (strpos($sql, "SELECT * FROM exp_modules WHERE module_name = 'Pages'") !== false) {
                    return new class {
                        public $num_rows = 0;
                        public function result_array()
                        {
                            return [];
                        }
                        public function row($column = null)
                        {
                            return null;
                        }
                    };
                }

                if (strpos($sql, "SELECT * FROM exp_modules WHERE module_name = 'Structure'") !== false) {
                    return new class {
                        public $num_rows = 1;
                        public function result_array()
                        {
                            return [['module_id' => 42]];
                        }
                        public function row($column = null)
                        {
                            return $column === 'module_id' ? 42 : (object) ['module_id' => 42];
                        }
                    };
                }

                if (strpos($sql, 'SELECT * FROM exp_sites') !== false) {
                    return new class {
                        public $num_rows = 0;
                        public function result_array()
                        {
                            return [];
                        }
                        public function row($column = null)
                        {
                            return null;
                        }
                    };
                }

                return new eeDbResultMock([]);
            }
            public function insert($table, $data = null)
            {
                $this->captured->inserts[] = [$table, $data];
                return true;
            }
            public function insert_string($table, $data)
            {
                $this->captured->insertStrings[] = [$table, $data];
                return 'INSERT INTO exp_' . $table;
            }
            public function table_exists($table)
            {
                return false;
            }
            public function field_exists($field, $table)
            {
                return false;
            }
            public function field_data($table)
            {
                return [
                    (object) ['name' => 'site_pages', 'type' => 'text']
                ];
            }
        };

        $dbforge = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function add_field($fields)
            {
            }
            public function add_key($key, $primary = false)
            {
            }
            public function create_table($table)
            {
                $this->captured->createdTables[] = $table;
            }
            public function add_column($table, $fields)
            {
                $this->captured->addedColumns[] = [$table, $fields];
            }
            public function modify_column($table, $fields)
            {
                $this->captured->modifiedColumns[] = [$table, $fields];
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('dbforge', $dbforge);
        ee()->setMock('cp', new class {
            public function fetch_action_id($class, $method)
            {
                return 9001;
            }
        });
        ee()->setMock('layout', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function add_layout_tabs($tabs, $module)
            {
                $this->captured->layoutTabs[] = [$tabs, $module];
            }
        });
        ee()->setMock('load', new class {
            public function dbforge()
            {
            }
            public function library($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });

        $upd = new class extends Structure_upd {
            public $populateCalled = false;
            public function __construct()
            {
            }
            public function populate_listings()
            {
                $this->populateCalled = true;
            }
        };
        $upd->version = '9.9.9';

        $this->assertTrue($upd->install());
        $this->assertTrue($upd->populateCalled);
        $this->assertNotEmpty($captured->inserts);
        $this->assertContains('structure', $captured->createdTables);
        $this->assertContains('structure_settings', $captured->createdTables);
        $this->assertContains('structure_channels', $captured->createdTables);
        $this->assertContains('structure_listings', $captured->createdTables);
        $this->assertContains('structure_members', $captured->createdTables);
        $this->assertContains('structure_nav_history', $captured->createdTables);
        $this->assertNotEmpty($captured->layoutTabs);
        $this->assertNotEmpty($captured->insertStrings);
        $this->assertNotEmpty($captured->modifiedColumns);
    }

    public function testUpdateCoversLegacySchemaAndDataMigrations()
    {
        $captured = (object) [
            'queries' => [],
            'insertBatches' => [],
            'inserts' => [],
            'updates' => [],
            'modifiedColumns' => [],
            'addedColumns' => [],
            'setSitePages' => [],
            'updateIntegrityCalled' => 0,
            'layoutCalls' => [],
        ];

        $db = new class($captured) extends FakeDb {
            private $captured;
            private $fieldExistsMap = [];
            public function __construct($captured)
            {
                $this->captured = $captured;
                $this->fieldExistsMap = [
                    'structure_channels.split_assets' => false,
                    'structure.hidden' => false,
                    'structure_channels.show_in_page_selector' => false,
                    'structure.structure_url_title' => false,
                    'structure.template_id' => false,
                    'structure.updated' => false,
                    'channels.preview_url' => true,
                ];
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
                return new eeDbResultMock([]);
            }
            public function table_exists($table)
            {
                return false;
            }
            public function field_exists($field, $table)
            {
                return $this->fieldExistsMap[$table . '.' . $field] ?? false;
            }
            public function field_data($table)
            {
                return [
                    (object) ['name' => 'site_pages', 'type' => 'text']
                ];
            }
            public function insert_batch($table, $data)
            {
                $this->captured->insertBatches[] = [$table, $data];
                return true;
            }
            public function insert($table, $data = null)
            {
                $this->captured->inserts[] = [$table, $data];
                return true;
            }
            public function where($field, $value = null)
            {
                $this->captured->updates[] = ['where', $field, $value];
                return $this;
            }
            public function update($table, $data = null, $where = null)
            {
                $this->captured->updates[] = ['update', $table, $data, $where];
                return true;
            }
        };

        $dbforge = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function add_field($fields)
            {
            }
            public function add_key($key, $primary = false)
            {
            }
            public function create_table($table)
            {
            }
            public function add_column($table, $fields)
            {
                $this->captured->addedColumns[] = [$table, $fields];
            }
            public function modify_column($table, $fields)
            {
                $this->captured->modifiedColumns[] = [$table, $fields];
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('dbforge', $dbforge);
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('layout', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function delete_layout_tabs($tabs)
            {
                $this->captured->layoutCalls[] = 'delete';
            }
            public function add_layout_tabs($tabs, $module)
            {
                $this->captured->layoutCalls[] = 'add';
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });

        $upd = new class($captured) extends Structure_upd {
            private $captured;
            public $upgradeCalled = false;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function upgrade_to_ee2()
            {
                $this->upgradeCalled = true;
            }
        };
        $upd->version = '9.9.9';
        $upd->sql = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_site_pages()
            {
                return [
                    'uris' => [
                        1 => '/',
                        2 => '/child/'
                    ]
                ];
            }
            public function set_site_pages($siteId, $sitePages)
            {
                $this->captured->setSitePages[] = [$siteId, $sitePages];
            }
            public function update_integrity_data()
            {
                $this->captured->updateIntegrityCalled++;
            }
        };

        $upd->update('2.0.0');

        $this->assertTrue($upd->upgradeCalled);
        $this->assertNotEmpty($captured->insertBatches);
        $this->assertNotEmpty($captured->setSitePages);
        $this->assertSame('/child', $captured->setSitePages[0][1]['uris'][2]);
        $this->assertSame(1, $captured->updateIntegrityCalled);
        $this->assertNotEmpty($captured->layoutCalls);
        $this->assertNotEmpty($captured->modifiedColumns);
    }

    public function testPopulateListingsCreatesRowsForMissingNodesOnly()
    {
        $captured = (object) ['insertRows' => []];
        $addonDir = realpath(__DIR__ . '/../../../../Addons/structure');
        $cwd = getcwd();

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $captured;
            private $entryId = null;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value = null)
            {
                if ($field === 'entry_id') {
                    $this->entryId = (int) $value;
                }
                return $this;
            }
            public function get($table = null)
            {
                $channelByEntry = [
                    20 => 7,
                    30 => 8,
                    40 => 9,
                ];
                $channelId = $channelByEntry[$this->entryId] ?? 0;
                return new class($channelId) {
                    private $channelId;
                    public function __construct($channelId)
                    {
                        $this->channelId = $channelId;
                    }
                    public function row($column = null)
                    {
                        return $column === 'channel_id' ? $this->channelId : (object) ['channel_id' => $this->channelId];
                    }
                };
            }
            public function insert_string($table, $data)
            {
                $this->captured->insertRows[] = [$table, $data];
                return 'INSERT INTO exp_' . $table;
            }
            public function query($sql)
            {
                if (preg_match("/node\\.entry_id = '([0-9]+)'/", $sql, $match)) {
                    $entryId = (int) $match[1];

                    if ($entryId === 50) {
                        return new class {
                            public $num_rows = 1;
                            public function result_array()
                            {
                                return [[
                                    'entry_id' => 50,
                                    'lft' => 5,
                                    'rgt' => 6,
                                    'depth' => 1,
                                    'isLeaf' => 1,
                                    'numChildren' => 0,
                                ]];
                            }
                            public function row($column = null)
                            {
                                return null;
                            }
                        };
                    }

                    return new class {
                        public $num_rows = 0;
                        public function result_array()
                        {
                            return [];
                        }
                        public function row($column = null)
                        {
                            return null;
                        }
                    };
                }

                return new class {
                    public $num_rows = 0;
                    public function result_array()
                    {
                        return [];
                    }
                    public function row($column = null)
                    {
                        return null;
                    }
                };
            }
        });

        $this->upd->sql = new class {
            public function get_site_pages()
            {
                return [
                    'uris' => [
                        20 => '/parent/listing-one/',
                        30 => '/parent/listing-two/',
                        40 => '/parent/listing-skip/',
                        50 => '/existing/page/',
                    ],
                    'templates' => [
                        20 => [11],
                        30 => 12,
                        40 => 13,
                        50 => 14,
                    ]
                ];
            }
            public function get_pid_for_listing_entry($entryId)
            {
                $pidMap = [20 => 5, 30 => 6, 40 => null, 50 => 7];
                return array_key_exists($entryId, $pidMap) ? $pidMap[$entryId] : 0;
            }
        };

        try {
            chdir($addonDir);
            $this->upd->populate_listings();
        } finally {
            chdir($cwd);
        }

        $this->assertCount(2, $captured->insertRows);
        $this->assertSame('structure_listings', $captured->insertRows[0][0]);
        $this->assertSame(20, $captured->insertRows[0][1]['entry_id']);
        $this->assertSame('listing-one', $captured->insertRows[0][1]['uri']);
        $this->assertSame(11, $captured->insertRows[0][1]['template_id']);
        $this->assertSame(30, $captured->insertRows[1][1]['entry_id']);
        $this->assertSame('listing-two', $captured->insertRows[1][1]['uri']);
        $this->assertSame(12, $captured->insertRows[1][1]['template_id']);
    }

    public function testUpgradeToEe2MigratesLegacySettingsAndListings()
    {
        if (!defined('PATH_MOD')) {
            define('PATH_MOD', PATH_ADDONS);
        }
        if (!defined('PATH_PRO_ADDONS')) {
            define('PATH_PRO_ADDONS', PATH_ADDONS);
        }

        $captured = (object) [
            'inserted' => [],
            'deleted' => [],
            'emptied' => [],
            'updated' => [],
            'createdTables' => [],
            'layoutTabs' => [],
        ];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'uris' => [
                                2001 => '/parent/one/',
                                2002 => '/parent/two/',
                            ]
                        ]
                    ];
                }
                return null;
            }
        });
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return true;
            }
        });
        ee()->setMock('load', new class {
            public function dbforge()
            {
            }
            public function library($name)
            {
            }
            public function add_package_path($path)
            {
            }
            public function helper($name)
            {
            }
        });
        ee()->setMock('layout', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function add_layout_tabs($tabs, $module)
            {
                $this->captured->layoutTabs[] = [$tabs, $module];
            }
        });

        ee()->setMock('dbforge', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function add_field($fields)
            {
            }
            public function add_key($key, $primary = false)
            {
            }
            public function create_table($table)
            {
                $this->captured->createdTables[] = $table;
            }
            public function modify_column($table, $fields)
            {
            }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $captured;
            private $fromTable = null;
            private $whereMap = [];
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function field_exists($field, $table)
            {
                if ($table === 'structure' && in_array($field, ['channel_id', 'listing_cid'], true)) {
                    return false;
                }
                return true;
            }
            public function table_exists($table)
            {
                return false;
            }
            public function get_where($table, $where = [], $limit = null, $offset = null)
            {
                if ($table === 'extensions') {
                    return new class {
                        public $num_rows = 0;
                    };
                }
                return new eeDbResultMock([]);
            }
            public function get($table = null)
            {
                $useTable = $table ?: $this->fromTable;

                if ($useTable === 'structure_settings') {
                    return new eeDbResultMock([
                        ['site_id' => 1, 'var' => 'type_weblog_9', 'var_value' => 'structure'],
                        ['site_id' => 1, 'var' => 'template_weblog_9', 'var_value' => 21],
                        ['site_id' => 1, 'var' => 'action_ajax_move', 'var_value' => 123],
                    ]);
                }

                if ($useTable === 'structure as s') {
                    return new eeDbResultMock([
                        ['site_id' => 1, 'channel_id' => 100, 'listing_cid' => 9, 'template_id' => 21]
                    ]);
                }

                if ($useTable === 'channel_titles') {
                    return new eeDbResultMock([
                        ['site_id' => 1, 'entry_id' => 2001],
                        ['site_id' => 1, 'entry_id' => 2002],
                    ]);
                }

                $this->fromTable = null;
                $this->whereMap = [];
                return new eeDbResultMock([]);
            }
            public function from($table)
            {
                $this->fromTable = $table;
                return $this;
            }
            public function join($table, $condition, $type = '')
            {
                return $this;
            }
            public function where($field, $value = null)
            {
                $this->whereMap[$field] = $value;
                return $this;
            }
            public function insert($table, $data = null)
            {
                $this->captured->inserted[] = [$table, $data];
                return true;
            }
            public function delete($table, $where = null)
            {
                $this->captured->deleted[] = [$table, $where];
                return true;
            }
            public function empty_table($table)
            {
                $this->captured->emptied[] = $table;
                return true;
            }
            public function update($table, $data = null, $where = null)
            {
                $this->captured->updated[] = [$table, $data, $where];
                return true;
            }
            public function query($sql)
            {
                return new eeDbResultMock([]);
            }
        });

        $this->upd->upgrade_to_ee2();

        $insertTables = array_map(function ($row) {
            return $row[0];
        }, $captured->inserted);

        $this->assertContains('structure_channels', $insertTables);
        $this->assertContains('structure_listings', $insertTables);
        $this->assertContains('extensions', $insertTables);
        $this->assertContains('structure_settings', $captured->emptied);
        $this->assertNotEmpty($captured->createdTables);
        $this->assertNotEmpty($captured->layoutTabs);
        $this->assertNotEmpty($captured->updated);
    }

    private function invokePrivate($method, array $args = [])
    {
        $rm = new ReflectionMethod($this->upd, $method);
        $rm->setAccessible(true);
        return $rm->invokeArgs($this->upd, $args);
    }
}
