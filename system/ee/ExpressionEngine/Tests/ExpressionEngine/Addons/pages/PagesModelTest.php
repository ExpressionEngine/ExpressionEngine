<?php

require_once __DIR__ . '/PagesTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model
    {
    }
}

require_once __DIR__ . '/../../../../Addons/pages/models/pages_model.php';

class PagesModelTest extends PagesTestBase
{
    public function testFetchConfigurationBuildsExpectedQuery(): void
    {
        $captured = (object) [
            'calls' => [],
        ];

        $model = $this->makeModel($captured, [
            ['configuration_name' => 'homepage_display', 'configuration_value' => 'nested'],
        ]);

        $result = $model->fetch_configuration();

        $this->assertInstanceOf(eeDbResultMock::class, $result);
        $this->assertContains(['where', 'site_id', 1], $captured->calls);
        $this->assertContains(['where_in', 'configuration_name', ['homepage_display', 'default_channel']], $captured->calls);
    }

    public function testFetchSitePagesConfigUsesSiteIdFilter(): void
    {
        $captured = (object) ['calls' => []];
        $model = $this->makeModel($captured, []);

        $result = $model->fetch_site_pages_config();

        $this->assertInstanceOf(eeDbResultMock::class, $result);
        $this->assertContains(['where', 'site_id', 1], $captured->calls);
        $this->assertContains(['get', 'pages_configuration'], $captured->calls);
    }

    public function testFetchSitePagesReturnsModelSitePages(): void
    {
        $captured = (object) ['calls' => []];
        $model = $this->makeModel($captured, []);

        ee()->setMock('Model', new class {
            public function get($entity, $id = null)
            {
                return new class {
                    public function first()
                    {
                        return (object) ['site_pages' => [1 => ['uris' => [10 => '/hello']]]];
                    }
                };
            }
        });

        $pages = $model->fetch_site_pages();
        $this->assertSame([1 => ['uris' => [10 => '/hello']]], $pages);
    }

    public function testUpdatePagesConfigurationReplacesRows(): void
    {
        $captured = (object) ['calls' => []];
        $model = $this->makeModel($captured, []);

        $model->update_pages_configuration([
            'homepage_display' => 'nested',
            'template_channel_4' => 17,
        ]);

        $this->assertContains(['delete', 'pages_configuration'], $captured->calls);
        $this->assertContains(['insert', 'pages_configuration', ['configuration_name' => 'homepage_display', 'configuration_value' => 'nested', 'site_id' => 1]], $captured->calls);
        $this->assertContains(['insert', 'pages_configuration', ['configuration_name' => 'template_channel_4', 'configuration_value' => 17, 'site_id' => 1]], $captured->calls);
    }

    public function testDeleteSitePagesReturnsFalseWhenNoPages(): void
    {
        $captured = (object) ['calls' => []];
        $model = $this->makeModel($captured, []);

        $model = $this->modelWithFetchPages($model, false);

        $this->assertFalse($model->delete_site_pages([10 => 10]));
    }

    public function testDeleteSitePagesRemovesUrisTemplatesAndPersists(): void
    {
        $captured = (object) ['calls' => [], 'setItems' => [], 'siteSaved' => 0];
        $model = $this->makeModel($captured, []);

        $sitePages = [
            1 => [
                'uris' => [10 => '/a', 20 => '/b', 30 => '/c'],
                'templates' => [10 => 2, 20 => 3, 30 => 4],
            ],
        ];
        $model = $this->modelWithFetchPages($model, $sitePages);

        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity, $id = null)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function first()
                    {
                        return new class($this->captured) {
                            private $captured;
                            public $site_pages;
                            public function __construct($captured)
                            {
                                $this->captured = $captured;
                            }
                            public function save()
                            {
                                $this->captured->siteSaved++;
                            }
                        };
                    }
                };
            }
        });

        $deleted = $model->delete_site_pages([10 => 10, 30 => 30]);
        $this->assertSame(2, $deleted);

        $this->assertSame('site_pages', $captured->setItems[0][0]);
        $this->assertArrayNotHasKey(10, $captured->setItems[0][1][1]['uris']);
        $this->assertArrayNotHasKey(30, $captured->setItems[0][1][1]['templates']);
        $this->assertSame(1, $captured->siteSaved);
    }

    public function testUpdatePagesConfigurationWithEmptyDataOnlyDeletesExistingRows(): void
    {
        $captured = (object) ['calls' => []];
        $model = $this->makeModel($captured, []);

        $model->update_pages_configuration([]);

        $this->assertContains(['delete', 'pages_configuration'], $captured->calls);
        $insertCalls = array_filter($captured->calls, function ($call) {
            return $call[0] === 'insert';
        });
        $this->assertSame([], array_values($insertCalls));
    }

    public function testDeleteSitePagesReturnsZeroAndPersistsWhenIdsDoNotMatch(): void
    {
        $captured = (object) ['calls' => [], 'setItems' => [], 'siteSaved' => 0];
        $model = $this->makeModel($captured, []);
        $sitePages = [
            1 => [
                'uris' => [10 => '/a'],
                'templates' => [10 => 2],
            ],
        ];
        $model = $this->modelWithFetchPages($model, $sitePages);

        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity, $id = null)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function first()
                    {
                        return new class($this->captured) {
                            private $captured;
                            public $site_pages;
                            public function __construct($captured)
                            {
                                $this->captured = $captured;
                            }
                            public function save()
                            {
                                $this->captured->siteSaved++;
                            }
                        };
                    }
                };
            }
        });

        $deleted = $model->delete_site_pages([99 => 99]);

        $this->assertSame(0, $deleted);
        $this->assertSame('/a', $captured->setItems[0][1][1]['uris'][10]);
        $this->assertSame(1, $captured->siteSaved);
    }

    private function makeModel($captured, array $rows): Pages_model
    {
        $db = new class($captured, $rows) {
            private $captured;
            private $rows;
            public function __construct($captured, $rows)
            {
                $this->captured = $captured;
                $this->rows = $rows;
            }
            public function select($fields = null)
            {
                $this->captured->calls[] = ['select', $fields];
                return $this;
            }
            public function from($table)
            {
                $this->captured->calls[] = ['from', $table];
                return $this;
            }
            public function where_in($field, $values)
            {
                $this->captured->calls[] = ['where_in', $field, $values];
                return $this;
            }
            public function where($field, $value)
            {
                $this->captured->calls[] = ['where', $field, $value];
                return $this;
            }
            public function get($table = null)
            {
                $this->captured->calls[] = ['get', $table];
                return new eeDbResultMock($this->rows);
            }
            public function delete($table)
            {
                $this->captured->calls[] = ['delete', $table];
                return true;
            }
            public function insert($table, $data)
            {
                $this->captured->calls[] = ['insert', $table, $data];
                return true;
            }
        };

        $config = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function item($key)
            {
                return $key === 'site_id' ? 1 : null;
            }
            public function set_item($key, $value)
            {
                $this->captured->setItems[] = [$key, $value];
            }
        };

        $model = (new ReflectionClass(Pages_model::class))->newInstanceWithoutConstructor();
        $model->db = $db;
        $model->config = $config;

        return $model;
    }

    private function modelWithFetchPages(Pages_model $model, $sitePages): Pages_model
    {
        return new class($model, $sitePages) extends Pages_model {
            private $inner;
            private $sitePages;
            public function __construct($inner, $sitePages)
            {
                foreach (get_object_vars($inner) as $k => $v) {
                    $this->$k = $v;
                }
                $this->inner = $inner;
                $this->sitePages = $sitePages;
            }
            public function fetch_site_pages()
            {
                return $this->sitePages;
            }
        };
    }
}
