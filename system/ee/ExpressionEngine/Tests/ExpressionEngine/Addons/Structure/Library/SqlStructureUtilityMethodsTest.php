<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../../Addons/structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureUtilityFixture extends Sql_structure
{
    private $fixtureSettings;
    private $fixtureSitePages;

    public function __construct(array $settings = [], array $sitePages = [])
    {
        $this->fixtureSettings = $settings;
        $this->fixtureSitePages = $sitePages;
    }

    public function get_settings()
    {
        return $this->fixtureSettings;
    }

    public function get_site_pages($cache_bust = false, $force = false)
    {
        return $this->fixtureSitePages;
    }
}

class SqlStructureUtilityMethodsTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    public function testStringUtilityMethods()
    {
        $sql = new SqlStructureUtilityFixture();

        $this->assertSame('HelloWorld', $sql->create_uri('Hello World!'));
        $this->assertSame('/parent/child/', $sql->create_page_uri('/parent', 'child'));
        $this->assertSame('/parent/child/', $sql->create_full_uri('/parent', 'child'));
        $this->assertSame(2, $sql->count_segments('/alpha/beta/'));
        $this->assertNull($sql->count_segments(''));
        $this->assertSame('gamma', $sql->get_slug('/alpha/beta/gamma'));
        $this->assertSame(['alpha', 'beta', 'gamma'], $sql->get_slug('/alpha/beta/gamma', true));
        $this->assertFalse($sql->get_slug(false));
        $this->assertSame(2, $sql->get_parent_uri_depth('/a/b/'));
        $this->assertSame(0, $sql->get_parent_uri_depth(null));
    }

    public function testReindexAtOnePreservesOriginalKeysWhileAddingOneBasedCopies()
    {
        $sql = new SqlStructureUtilityFixture();
        $rows = [
            ['entry_id' => 10],
            ['entry_id' => 11],
        ];

        $this->assertSame([
            0 => ['entry_id' => 10],
            1 => ['entry_id' => 10],
            2 => ['entry_id' => 11],
        ], $sql->reindex_at_one($rows));
    }

    public function testReindexAtOneReturnsEmptyArrayForEmptyInput()
    {
        $sql = new SqlStructureUtilityFixture();

        $this->assertSame([], $sql->reindex_at_one([]));
    }

    public function testReindexAtOneWarnsAndReturnsOriginalValueForNonIterableInput()
    {
        $sql = new SqlStructureUtilityFixture();
        $warning = null;

        set_error_handler(function ($number, $message) use (&$warning) {
            $warning = [$number, $message];

            return true;
        });

        try {
            $result = $sql->reindex_at_one(null);
        } finally {
            restore_error_handler();
        }

        $this->assertNull($result);
        $this->assertSame(E_WARNING, $warning[0]);
        $this->assertStringContainsString('foreach', $warning[1]);
    }

    public function testReindexAtOneAddsOneBasedCopiesWithoutRemovingSparseKeys()
    {
        $sql = new SqlStructureUtilityFixture();
        $rows = [
            5 => ['entry_id' => 50],
            9 => ['entry_id' => 90],
        ];

        $this->assertSame([
            5 => ['entry_id' => 50],
            9 => ['entry_id' => 90],
            1 => ['entry_id' => 50],
            2 => ['entry_id' => 90],
        ], $sql->reindex_at_one($rows));
    }

    public function testGetUriAndThemeUrlAndSiteId()
    {
        $config = new class {
            public $items = ['site_id' => 2, 'theme_folder_url' => 'https://cdn.example.com/themes'];
            public function item($key)
            {
                return $this->items[$key] ?? null;
            }
            public function slash_item($key)
            {
                return rtrim($this->items[$key] ?? '', '/') . '/';
            }
        };

        ee()->setMock('config', $config);
        ee()->setMock('uri', new class {
            public function uri_string()
            {
                return 'docs//intro/P20';
            }
        });

        $sql = new SqlStructureUtilityFixture(['add_trailing_slash' => 'y']);
        $this->assertSame('/docs/intro/', $sql->get_uri());
        $this->assertSame(2, $sql->get_site_id());

        $first = $sql->theme_url();
        $second = $sql->theme_url();
        $expectedThemeUrl = (defined('URL_THEMES') ? URL_THEMES : 'https://cdn.example.com/themes/third_party/') . 'structure/';
        $this->assertSame($expectedThemeUrl, $first);
        $this->assertSame($first, $second);

        $config->items['site_id'] = 'abc';
        $this->assertSame(1, $sql->get_site_id());

        ee()->setMock('uri', new class {
            public function uri_string()
            {
                return 'P12';
            }
        });
        $this->assertSame('/', $sql->get_uri());
    }

    public function testModuleAndExtensionInstallChecksAndModuleId()
    {
        $cache = new class {
            public $store = [];
            public function get($key)
            {
                return $this->store[$key] ?? false;
            }
            public function save($key, $value)
            {
                $this->store[$key] = $value;
                return true;
            }
        };

        $db = new class {
            public $moduleRows;
            public $extensionRows = 1;
            public function __construct()
            {
                $this->moduleRows = [(object) ['module_id' => 55]];
            }
            public function query($sql)
            {
                if (strpos($sql, 'exp_extensions') !== false) {
                    return new class($this->extensionRows) {
                        public $num_rows;
                        public function __construct($count)
                        {
                            $this->num_rows = $count;
                        }
                    };
                }

                return new class($this->moduleRows) {
                    private $rows;
                    public function __construct($rows)
                    {
                        $this->rows = $rows;
                    }
                    public function result()
                    {
                        return $this->rows;
                    }
                };
            }
        };

        ee()->setMock('cache', $cache);
        ee()->setMock('db', $db);

        $sql = new SqlStructureUtilityFixture();
        $this->assertTrue($sql->module_is_installed());
        $this->assertSame(55, $sql->get_module_id());
        $this->assertTrue($sql->extension_is_installed());

        unset($cache->store['/Structure/module_id_query']);
        $db->moduleRows = [];
        $db->extensionRows = 0;
        $this->assertFalse($sql->module_is_installed());
        $this->assertFalse($sql->get_module_id());
        $this->assertFalse($sql->extension_is_installed());
    }

    public function testIsValidTemplateAndDuplicateListingUri()
    {
        $db = new class extends eeDbArMock {
            public $duplicateCount = 2;
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'templates') {
                    return new eeDbResultMock(isset($where['template_id']) && (int) $where['template_id'] === 9 ? [['template_id' => 9]] : []);
                }

                return new class($this->duplicateCount > 0 ? 1 : 0) {
                    public $num_rows;
                    public function __construct($count)
                    {
                        $this->num_rows = $count;
                    }
                };
            }
            public function query($sql)
            {
                return new class($this->duplicateCount) {
                    public $num_rows;
                    public function __construct($count)
                    {
                        $this->num_rows = $count;
                    }
                };
            }
        };

        ee()->setMock('db', $db);
        $sql = new SqlStructureUtilityFixture();

        $this->assertFalse($sql->is_valid_template('abc'));
        $this->assertTrue($sql->is_valid_template(9));
        $this->assertFalse($sql->is_valid_template(999));
        $this->assertSame(3, $sql->is_duplicate_listing_uri(10, 'child', 5));

        $db->duplicateCount = 0;
        $this->assertFalse($sql->is_duplicate_listing_uri(10, 'child', 5));
    }

    public function testUserAccessCoversSettingsAndDbBranches()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 1;
            }
        });
        ee()->setMock('session', (object) ['userdata' => ['group_id' => 1]]);

        $sql = new SqlStructureUtilityFixture();
        $this->assertSame('all', $sql->user_access('perm_delete'));
        $this->assertTrue($sql->user_access('perm_publish'));

        ee()->setMock('session', (object) ['userdata' => ['group_id' => 7]]);
        $this->assertTrue($sql->user_access('perm_reorder', ['perm_reorder_7' => 'y']));
        $this->assertSame('n', $sql->user_access('perm_reorder', ['perm_reorder_7' => 'n']));
        $this->assertFalse($sql->user_access('perm_reorder', ['perm_edit_7' => 'y']));

        ee()->setMock('db', new class {
            public $rows = 1;
            public function select($field)
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value)
            {
                return $this;
            }
            public function or_where($field, $value)
            {
                return $this;
            }
            public function num_rows()
            {
                return $this->rows;
            }
        });

        $this->assertSame('all', $sql->user_access('perm_reorder'));
        $this->assertTrue($sql->user_access('perm_publish'));

        ee()->db->rows = 0;
        $this->assertFalse($sql->user_access('perm_publish'));
    }
}
