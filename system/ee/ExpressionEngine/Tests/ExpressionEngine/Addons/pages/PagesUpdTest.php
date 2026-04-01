<?php

require_once __DIR__ . '/PagesTestBase.php';
require_once __DIR__ . '/../../../../Addons/pages/upd.pages.php';

class PagesUpdTest extends PagesTestBase
{
    public function testConstructorCallsParentConstructor(): void
    {
        ee()->setMock('Addon', new class {
            public function get($shortname)
            {
                return new class {
                    public function getVersion()
                    {
                        return '2.2.0';
                    }
                    public function getModuleClass()
                    {
                        return 'Pages';
                    }
                    public function hasExtension()
                    {
                        return false;
                    }
                    public function hasControlPanel()
                    {
                        return false;
                    }
                    public function getControlPanelClass()
                    {
                        return 'Pages_mcp';
                    }
                };
            }
        });

        $upd = new Pages_upd();

        $this->assertSame('pages', $upd->shortname);
        $this->assertSame('2.2.0', $upd->version);
    }

    public function testTabsReturnsExpectedTabConfig(): void
    {
        $upd = $this->makeUpdater();
        $tabs = $upd->tabs();

        $this->assertArrayHasKey('pages', $tabs);
        $this->assertArrayHasKey('pages_template_id', $tabs['pages']);
        $this->assertArrayHasKey('pages_uri', $tabs['pages']);
    }

    public function testInstallCreatesTableAndAddsLayoutTabs(): void
    {
        $captured = (object) [
            'queries' => [],
            'layoutAdds' => [],
            'moduleSaves' => 0,
            'migrations' => [],
        ];

        $upd = $this->makeUpdater($captured);

        ee()->setMock('db', new class($captured) {
            public $char_set = 'utf8mb4';
            public $dbcollat = 'utf8mb4_unicode_ci';
            public $data_cache = ['old' => true];
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function field_exists($field, $table)
            {
                return false;
            }
            public function escape_str($value)
            {
                return $value;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
                return true;
            }
        });
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
            public function add_layout_tabs($tabs, $shortname)
            {
                $this->captured->layoutAdds[] = [$tabs, $shortname];
            }
        });

        $this->assertTrue($upd->install());

        $this->assertGreaterThanOrEqual(2, count($captured->queries));
        $this->assertStringContainsString('ALTER TABLE `exp_sites` ADD `site_pages` TEXT NOT NULL', $captured->queries[0]);
        $this->assertStringContainsString('CREATE TABLE `exp_pages_configuration`', implode("\n", $captured->queries));
        $this->assertSame(1, $captured->moduleSaves);
        $this->assertSame('pages', $captured->layoutAdds[0][1]);
        $this->assertContains('pages', $captured->migrations);
    }

    public function testInstallSkipsSitePagesAlterWhenColumnAlreadyExists(): void
    {
        $captured = (object) [
            'queries' => [],
            'layoutAdds' => [],
            'moduleSaves' => 0,
            'migrations' => [],
        ];

        $upd = $this->makeUpdater($captured);

        ee()->setMock('db', new class($captured) {
            public $char_set = 'utf8mb4';
            public $dbcollat = 'utf8mb4_unicode_ci';
            public $data_cache = [];
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function field_exists($field, $table)
            {
                return true;
            }
            public function escape_str($value)
            {
                return $value;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
                return true;
            }
        });
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
            public function add_layout_tabs($tabs, $shortname)
            {
                $this->captured->layoutAdds[] = [$tabs, $shortname];
            }
        });

        $this->assertTrue($upd->install());
        $querySql = implode("\n", $captured->queries);
        $this->assertStringNotContainsString('ALTER TABLE `exp_sites` ADD `site_pages` TEXT NOT NULL', $querySql);
        $this->assertStringContainsString('CREATE TABLE `exp_pages_configuration`', $querySql);
    }

    public function testUninstallDropsConfigurationTableAndRemovesTabs(): void
    {
        $captured = (object) [
            'queries' => [],
            'layoutDeletes' => [],
            'deletes' => [],
            'rollbacks' => [],
        ];
        $upd = $this->makeUpdater($captured);

        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
                return true;
            }
        });
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
                $this->captured->layoutDeletes[] = $tabs;
            }
        });

        $this->assertTrue($upd->uninstall());

        $this->assertContains('pages', $captured->rollbacks);
        $this->assertContains('module', $captured->deletes);
        $this->assertSame('DROP TABLE `exp_pages_configuration`', $captured->queries[0]);
        $this->assertNotEmpty($captured->layoutDeletes);
    }

    public function testUpdateReturnsFalseWhenCurrentMatchesVersion(): void
    {
        $upd = $this->makeUpdater();
        $upd->version = '2.2.0';
        $this->assertFalse($upd->update('2.2.0'));
    }

    public function testUpdateRunsLegacyAnd22MigrationsWhenNeeded(): void
    {
        $captured = (object) [
            'where' => [],
            'updates' => [],
            'layoutUpdates' => [],
        ];
        $upd = $this->makeUpdater();
        $upd->version = '2.2.0';

        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function where($field, $value)
            {
                $this->captured->where[] = [$field, $value];
                return $this;
            }
            public function update($table, $data)
            {
                $this->captured->updates[] = [$table, $data];
                return true;
            }
            public function get($table)
            {
                return new eeDbResultMock([
                    [
                        'layout_id' => 1,
                        'field_layout' => serialize([
                            'publish' => [
                                'pages_uri' => ['visible' => true],
                                'pages_template_id' => ['visible' => true],
                                'other_field' => ['visible' => true],
                            ],
                        ]),
                    ],
                ]);
            }
            public function update_batch($table, $rows, $key)
            {
                $this->captured->layoutUpdates[] = [$table, $rows, $key];
                return true;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });

        $this->assertTrue($upd->update('2.0'));

        $this->assertContains(['module_name', 'Pages'], $captured->where);
        $this->assertContains(['modules', ['has_publish_fields' => 'y']], $captured->updates);
        $this->assertNotEmpty($captured->layoutUpdates);

        $row = $captured->layoutUpdates[0][1][0];
        $layout = unserialize($row['field_layout']);
        $this->assertArrayHasKey('pages__pages_uri', $layout['publish']);
        $this->assertArrayHasKey('pages__pages_template_id', $layout['publish']);
    }

    public function testUpdateAtVersion21SkipsLegacyBranchButRuns22Migration(): void
    {
        $captured = (object) [
            'where' => [],
            'updates' => [],
            'layoutUpdates' => [],
        ];
        $upd = $this->makeUpdater();
        $upd->version = '2.2.0';

        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function where($field, $value)
            {
                $this->captured->where[] = [$field, $value];
                return $this;
            }
            public function update($table, $data)
            {
                $this->captured->updates[] = [$table, $data];
                return true;
            }
            public function get($table)
            {
                return new eeDbResultMock([
                    [
                        'layout_id' => 2,
                        'field_layout' => serialize([
                            'publish' => [
                                'pages_uri' => ['visible' => true],
                                'pages_template_id' => ['visible' => true],
                            ],
                        ]),
                    ],
                ]);
            }
            public function update_batch($table, $rows, $key)
            {
                $this->captured->layoutUpdates[] = [$table, $rows, $key];
                return true;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });

        $this->assertTrue($upd->update('2.1'));
        $this->assertSame([], $captured->where);
        $this->assertSame([], $captured->updates);
        $this->assertNotEmpty($captured->layoutUpdates);
    }

    public function testUpdateAtVersion22SkipsLegacyAnd22Migrations(): void
    {
        $captured = (object) [
            'where' => [],
            'updates' => [],
            'getCalls' => 0,
            'layoutUpdates' => 0,
        ];
        $upd = $this->makeUpdater();
        $upd->version = '2.2.0';

        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function where($field, $value)
            {
                $this->captured->where[] = [$field, $value];
                return $this;
            }
            public function update($table, $data)
            {
                $this->captured->updates[] = [$table, $data];
                return true;
            }
            public function get($table)
            {
                $this->captured->getCalls++;
                return new eeDbResultMock([]);
            }
            public function update_batch($table, $rows, $key)
            {
                $this->captured->layoutUpdates++;
                return true;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });

        $this->assertTrue($upd->update('2.2'));
        $this->assertSame([], $captured->where);
        $this->assertSame([], $captured->updates);
        $this->assertSame(0, $captured->getCalls);
        $this->assertSame(0, $captured->layoutUpdates);
    }

    public function testDo22UpdateReturnsEarlyWhenNoLayouts(): void
    {
        $upd = $this->makeUpdater();
        $captured = (object) ['updateBatchCalls' => 0];

        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($table)
            {
                return new class {
                    public function num_rows()
                    {
                        return 0;
                    }
                };
            }
            public function update_batch($table, $rows, $key)
            {
                $this->captured->updateBatchCalls++;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });

        $this->assertNull($this->invokePrivate($upd, '_do_22_update'));
        $this->assertSame(0, $captured->updateBatchCalls);
    }

    private function makeUpdater($captured = null): Pages_upd
    {
        if ($captured === null) {
            $captured = (object) ['moduleSaves' => 0, 'migrations' => [], 'deletes' => [], 'rollbacks' => []];
        }

        ee()->setMock('Addon', new class {
            public function get($shortname)
            {
                return new class {
                    public function getVersion()
                    {
                        return '2.2.0';
                    }
                    public function getModuleClass()
                    {
                        return 'Pages';
                    }
                    public function hasExtension()
                    {
                        return false;
                    }
                    public function hasControlPanel()
                    {
                        return false;
                    }
                    public function getControlPanelClass()
                    {
                        return 'Pages_mcp';
                    }
                };
            }
        });
        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function make($entity, $data)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function save()
                    {
                        $this->captured->moduleSaves++;
                    }
                };
            }
            public function get($entity)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function filter($field, $value)
                    {
                        return $this;
                    }
                    public function delete()
                    {
                        $this->captured->deletes[] = 'module';
                        return true;
                    }
                    public function first()
                    {
                        return null;
                    }
                };
            }
        });
        ee()->setMock('Migration', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function migrateAllByType($shortname)
            {
                $this->captured->migrations[] = $shortname;
            }
            public function rollbackAllByType($shortname, $keep = false)
            {
                $this->captured->rollbacks[] = $shortname;
            }
        });

        $upd = (new ReflectionClass(Pages_upd::class))->newInstanceWithoutConstructor();
        $upd->addon = ee('Addon')->get('pages');
        $upd->shortname = 'pages';
        $upd->version = '2.2.0';
        $upd->actions = [];
        $upd->methods = [];
        $upd->settings = [];

        return $upd;
    }
}
