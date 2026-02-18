<?php

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../Addons/structure/Conduit/StaticCache.php';

if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
if (!defined('PATH_ADDONS')) {
    $addonsPath = defined('SYSPATH')
        ? (SYSPATH . 'ee/ExpressionEngine/Addons/')
        : (__DIR__ . '/../../../../Addons/');
    define('PATH_ADDONS', $addonsPath);
}
if (!defined('PATH_PRO_ADDONS')) {
    define('PATH_PRO_ADDONS', PATH_ADDONS);
}
if (!defined('PATH_MOD')) {
    define('PATH_MOD', PATH_ADDONS);
}

require_once __DIR__ . '/../../../../Addons/structure/tab.structure.php';

use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Structure\Conduit\StaticCache;
use PHPUnit\Framework\TestCase;

if (!class_exists('Cache')) {
    class Cache
    {
        public const GLOBAL_SCOPE = 'global';
    }
}

class StructureTabWrapperFixture extends Structure_tab
{
    public $calls = [];

    public function __construct()
    {
    }

    public function publish_tabs($channel_id, $entry_id = '')
    {
        $this->calls[] = ['publish_tabs', $channel_id, $entry_id];
        return ['ok' => 'tabs'];
    }

    public function publish_data_delete_db($params)
    {
        $this->calls[] = ['publish_data_delete_db', $params];
    }

    public function publish_data_db($params, $channel_entry = null)
    {
        $this->calls[] = ['publish_data_db', $params, $channel_entry];
        return ['ok' => 'saved'];
    }

    public function validate_publish($params, $channel_entry = null)
    {
        $this->calls[] = ['validate_publish', $params, $channel_entry];
        return ['ok' => 'validated'];
    }
}

class StructureTabTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        StaticCache::clear();
    }

    public function testWrapperMethodsDelegateToCoreMethods()
    {
        $tab = new StructureTabWrapperFixture();
        $entry = (object) ['entry_id' => 11];

        $this->assertSame(['ok' => 'tabs'], $tab->display(7, 11));
        $tab->delete(['entry_ids' => [1, 2]]);
        $this->assertSame(['ok' => 'saved'], $tab->save($entry, ['entry_id' => 11]));
        $this->assertSame(['ok' => 'validated'], $tab->validate($entry, ['entry_id' => 11]));

        $this->assertSame('publish_tabs', $tab->calls[0][0]);
        $this->assertSame('publish_data_delete_db', $tab->calls[1][0]);
        $this->assertSame('publish_data_db', $tab->calls[2][0]);
        $this->assertSame('validate_publish', $tab->calls[3][0]);
    }

    public function testConstructorInitializesSqlAndStructure()
    {
        ee()->setMock('uri', (object) ['page_query_string' => '', 'query_string' => '']);
        ee()->setMock('pagination', new class {
            public function create()
            {
                return new stdClass();
            }
        });
        ee()->setMock('functions', new class {
            public function fetch_assigned_channels()
            {
                return [];
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['url' => '/', 'uris' => [1 => '/'], 'templates' => [1 => 1]]];
                }
                if ($key === 'reserved_category_word') {
                    return 'category';
                }
                if ($key === 'use_category_name') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function add_package_path($path)
            {
            }
            public function library($name)
            {
            }
            public function helper($name)
            {
            }
        });
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return true;
            }
        });
        ee()->setMock('db', new class {
            public function query($sql)
            {
                return new class {
                    public function num_rows()
                    {
                        return 0;
                    }
                    public function result_array()
                    {
                        return [];
                    }
                    public function result()
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

        $tab = new Structure_tab();

        $this->assertSame(STRUCTURE_VERSION, $tab->version);
        $this->assertInstanceOf(Sql_structure::class, $tab->sql);
        $this->assertInstanceOf(Structure::class, $tab->structure);
    }

    public function testDefaultTabRenderTableCellAndTableConfig()
    {
        ee()->setMock('functions', new class {
            public function fetch_site_index($a = 0, $b = 0)
            {
                return 'https://example.com/';
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class {
            public $pages = ['uris' => [22 => '/docs/page']];
            public function get_site_pages()
            {
                return $this->pages;
            }
        };

        $settings = $tab->default_tab();
        $this->assertSame('text', $settings[0]['field_type']);
        $this->assertSame(['encode' => false], $tab->getTableColumnConfig());
        $this->assertStringContainsString('<a href="https://example.com/docs/page"', $tab->renderTableCell('', '', (object) ['entry_id' => 22]));
        $this->assertSame('', $tab->renderTableCell('', '', (object) ['entry_id' => 999]));
    }

    public function testUtilityFieldBuildersAndCreateUri()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 1;
            }
        });
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                if ($key === 'structure__parent_id') {
                    return null;
                }
                return false;
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;
                    public function __construct($value)
                    {
                        $this->value = $value;
                    }
                    public function urlSlug()
                    {
                        $this->value = strtolower(str_replace(' ', '-', trim($this->value)));
                        return $this;
                    }
                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class {
            public function get_templates()
            {
                return [
                    ['template_id' => 2, 'group_name' => 'pages', 'template_name' => 'index'],
                    ['template_id' => 3, 'group_name' => 'blog', 'template_name' => 'show'],
                ];
            }
            public function get_data()
            {
                return [];
            }
        };
        $tab->structure = new class {
            public function get_structure_channels($type = '', $channelId = null)
            {
                if ($type === 'listing') {
                    return [
                        2 => ['channel_title' => 'News Listings'],
                        3 => ['channel_title' => 'Events Listings'],
                    ];
                }
                return [];
            }
        };

        $templates = $tab->get_template_fields(0, [], 5, []);
        $this->assertSame('NONE', $templates[0]);
        $this->assertSame('pages/index', $templates[2]);

        $parents = $tab->get_parent_fields(10, [
            10 => ['parent_id' => 0, 'title' => 'Self', 'depth' => 1],
            11 => ['parent_id' => 10, 'title' => 'Child', 'depth' => 2],
            12 => ['parent_id' => 0, 'title' => 'Sibling', 'depth' => 1],
        ]);
        $this->assertArrayNotHasKey(10, $parents);
        $this->assertArrayNotHasKey(11, $parents);
        $this->assertArrayHasKey(12, $parents);

        $listingChannels = $tab->get_listing_channels(0, [], 3);
        $this->assertArrayHasKey('n', $listingChannels);
        $this->assertArrayHasKey(2, $listingChannels);
        $this->assertArrayNotHasKey(3, $listingChannels);

        $this->assertSame('my-page-title', $tab->create_uri('My Page Title'));
    }

    public function testPublishDataDeleteDbAndCloneData()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                return null;
            }
        });
        ee()->setMock('db', new class extends FakeDb {
            public function order_by($field, $direction = '')
            {
                return $this;
            }
        });

        $deleted = (object) ['values' => []];
        $tab = $this->makeTab();
        $tab->structure = new class($deleted) {
            private $deleted;
            public function __construct($deleted)
            {
                $this->deleted = $deleted;
            }
            public function delete_data($ids)
            {
                $this->deleted->values[] = $ids;
            }
        };
        $tab->sql = new class {
            public function get_site_pages($cacheBust = false, $force = false)
            {
                return ['uris' => [100 => '/home', 200 => 'copy_home']];
            }
        };

        $tab->publish_data_delete_db(['entry_ids' => [5, 6]]);
        $tab->publish_data_delete_db(9);
        $this->assertSame([[5, 6], 9], $deleted->values);

        /** @var ChannelEntry&PHPUnit\Framework\MockObject\MockObject $entry */
        $entry = $this->getMockBuilder(ChannelEntry::class)->disableOriginalConstructor()->getMock();
        $entry->entry_id = 999;

        $values = $tab->cloneData($entry, ['uri' => '/home']);
        $this->assertSame('copy_copy_home', $values['uri']);
        $this->assertSame('copy_copy_home', $_POST['structure__uri']);
    }

    public function testCloneDataEarlyReturnAndNoConflictPath()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 'underscore';
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class {
            public function get_site_pages($cacheBust = false, $force = false)
            {
                return ['uris' => []];
            }
        };

        /** @var ChannelEntry&PHPUnit\Framework\MockObject\MockObject $entry */
        $entry = $this->getMockBuilder(ChannelEntry::class)->disableOriginalConstructor()->getMock();
        $entry->entry_id = 50;

        $this->assertSame(['uri' => ''], $tab->cloneData($entry, ['uri' => '']));
        $this->assertSame('/home', $tab->cloneData($entry, ['uri' => '/home'])['uri']);
    }

    public function testValidatePublishSupportsSimpleAndPageGuardPaths()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });

        $captured = (object) ['set_channel_ids' => []];
        $sql = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function set_channel_ids($entryId, $channelId)
            {
                $this->captured->set_channel_ids[] = [$entryId, $channelId];
            }
        };

        $tab = $this->makeTab();
        $tab->sql = $sql;
        $tab->structure = new class {
            public function get_structure_channels()
            {
                return [4 => ['type' => 'listing']];
            }
        };
        $this->assertTrue($tab->validate_publish([['channel_id' => 4, 'entry_id' => 12, 'parent_id' => 0]]));

        ee()->setMock('db', new class {
            public function query($sql)
            {
                preg_match("/node\\.entry_id = '([^']+)'/", $sql, $m);
                $entryId = isset($m[1]) ? (int) $m[1] : 0;

                if ($entryId === 12) {
                    return new class {
                        public $num_rows = 1;
                        public function result_array()
                        {
                            return [[
                                'entry_id' => 12,
                                'lft' => 2,
                                'rgt' => 9,
                                'depth' => 1,
                                'isLeaf' => 0,
                                'numChildren' => 3,
                            ]];
                        }
                    };
                }

                return new class {
                    public $num_rows = 1;
                    public function result_array()
                    {
                        return [[
                            'entry_id' => 7,
                            'lft' => 4,
                            'rgt' => 5,
                            'depth' => 2,
                            'isLeaf' => 1,
                            'numChildren' => 0,
                        ]];
                    }
                };
            }
        });

        $tab->structure = new class {
            public function get_structure_channels()
            {
                return [4 => ['type' => 'page']];
            }
        };
        $result = $tab->validate_publish([['channel_id' => 4, 'entry_id' => 12, 'parent_id' => 7]]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('You can not nest a page below itself.', $result);
    }

    public function testValidatePublishPagePathCanPass()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 1;
            }
        });
        ee()->setMock('db', new class {
            public function query($sql)
            {
                preg_match("/node\\.entry_id = '([^']+)'/", $sql, $m);
                $entryId = isset($m[1]) ? (int) $m[1] : 0;

                if ($entryId === 100) {
                    return new class {
                        public $num_rows = 1;
                        public function result_array()
                        {
                            return [['entry_id' => 100, 'lft' => 10, 'rgt' => 11, 'depth' => 2, 'isLeaf' => 1, 'numChildren' => 0]];
                        }
                    };
                }

                return new class {
                    public $num_rows = 1;
                    public function result_array()
                    {
                        return [['entry_id' => 1, 'lft' => 1, 'rgt' => 20, 'depth' => 0, 'isLeaf' => 0, 'numChildren' => 10]];
                    }
                };
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class {
            public function set_channel_ids($entryId, $channelId)
            {
            }
        };
        $tab->structure = new class {
            public function get_structure_channels()
            {
                return [8 => ['type' => 'page']];
            }
        };

        $this->assertTrue($tab->validate_publish([['channel_id' => 8, 'entry_id' => 100, 'parent_id' => 1]]));
    }

    public function testValidatePublishCoversChannelEntryAndFallbackIdBranches()
    {
        $captured = (object) ['set_channel_ids' => []];

        $tab = $this->makeTab();
        $tab->sql = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function set_channel_ids($entryId, $channelId)
            {
                $this->captured->set_channel_ids[] = [$entryId, $channelId];
            }
        };
        $tab->structure = new class {
            public function get_structure_channels()
            {
                return [
                    4 => ['type' => 'listing'],
                    5 => ['type' => 'listing'],
                ];
            }
        };

        $entry = (object) ['channel_id' => 4, 'entry_id' => 55];
        $this->assertTrue($tab->validate_publish(['parent_id' => 8], $entry));

        $this->assertTrue($tab->validate_publish([
            0 => ['channel_id' => 4],
            'entry_id' => 66
        ]));

        $this->assertTrue($tab->validate_publish([
            0 => ['channel_id' => 5]
        ]));

        $this->assertContains([55, 4], $captured->set_channel_ids);
        $this->assertContains([66, 4], $captured->set_channel_ids);
        $this->assertContains([0, 5], $captured->set_channel_ids);
    }

    public function testGetListingChannelsHandlesCachedEmptySentinel()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 1;
            }
        });
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                if ($key === 'structure__parent_id') {
                    return null;
                }
                return false;
            }
        });

        StaticCache::set('get_listing_channels__get_structure_channels_listing', 'EMPTY');

        $tab = $this->makeTab();
        $tab->sql = new class {
            public function get_data()
            {
                return [];
            }
        };
        $tab->structure = new class {
            public function get_structure_channels($type = '')
            {
                return [99 => ['channel_title' => 'Should Not Be Used']];
            }
        };

        $channels = $tab->get_listing_channels(0, [], 99);
        $this->assertSame(['n' => '==None Selected=='], $channels);
    }

    public function testPublishDataDbCoversPageAndListingSaveFlows()
    {
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        $_POST = [];

        $captured = (object) [
            'setData' => [],
            'listingData' => [],
            'cacheDeletes' => 0,
            'listingDupeChecks' => [],
        ];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return null;
            }
        });
        ee()->setMock('cache', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function delete($path, $scope)
            {
                $this->captured->cacheDeletes++;
            }
        });
        ee()->setMock('Validation', new class {
            public function make($rules)
            {
                return new class {
                    public function validate($data)
                    {
                        return new class {
                            public function isValid()
                            {
                                return false;
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;
                    public function __construct($value)
                    {
                        $this->value = $value;
                    }
                    public function urlSlug()
                    {
                        $this->value = strtolower(trim(str_replace(' ', '-', $this->value)));
                        return $this;
                    }
                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                return false;
            }
        });
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                if (strpos($sql, 'SELECT entry_id FROM exp_channel_titles WHERE entry_id = ') !== false) {
                    return new class {
                        public $num_rows = 1;
                    };
                }
                return new eeDbResultMock([]);
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_site_pages($cacheBust = false)
            {
                return [
                    'uris' => [2 => '/parent/', 44 => '/blog/'],
                    'templates' => [101 => 3, 222 => 4]
                ];
            }
            public function get_parent_id($entryId, $default = null)
            {
                return 2;
            }
            public function get_hidden_state($entryId)
            {
                return 'n';
            }
            public function get_listing_channel($entryId)
            {
                return 0;
            }
            public function is_duplicate_page_uri($entryId, $uri)
            {
                return '/parent/custom-uri-2/';
            }
            public function get_listing_parent($channelId)
            {
                return 44;
            }
            public function is_duplicate_listing_uri($entryId, $uri, $parentId)
            {
                $this->captured->listingDupeChecks[] = [$entryId, $uri, $parentId];
                return $uri === 'listing-entry_1' ? false : 2;
            }
            public function set_listing_data($data)
            {
                $this->captured->listingData[] = $data;
            }
        };
        $tab->structure = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_structure_channels($type = '', $channelId = null)
            {
                return [
                    5 => ['type' => 'page', 'template_id' => 3],
                    9 => ['type' => 'listing', 'template_id' => 4],
                ];
            }
            public function create_page_uri($parentUri, $uri)
            {
                return rtrim($parentUri, '/') . '/' . trim($uri, '/') . '/';
            }
            public function set_data($data, $cacheBust = false)
            {
                $this->captured->setData[] = [$data, $cacheBust];
            }
        };

        $pageEntry = (object) [
            'entry_id' => 101,
            'channel_id' => 5,
            'site_id' => 1,
            'title' => 'Page Title',
            'url_title' => 'page-title',
        ];
        $tab->publish_data_db([
            'parent_id' => 2,
            'template_id' => 3,
            'hidden' => 'y',
            'uri' => 'Custom Uri',
            'listing_channel' => 'n',
            'meta' => [
                'channel_id' => 5,
                'site_id' => 1,
                'title' => 'Page Title'
            ],
        ], $pageEntry);

        $listingEntry = (object) [
            'entry_id' => 222,
            'channel_id' => 9,
            'site_id' => 1,
            'title' => 'Listing Title',
            'url_title' => 'listing-title',
        ];
        $tab->publish_data_db([
            'template_id' => 4,
            'uri' => 'Listing Entry',
            'meta' => [
                'channel_id' => 9,
                'site_id' => 1,
                'title' => 'Listing Title'
            ],
        ], $listingEntry);

        $this->assertCount(1, $captured->setData);
        $this->assertSame('/parent/custom-uri-2/', $captured->setData[0][0]['uri']);
        $this->assertTrue($captured->setData[0][1]);
        $this->assertCount(1, $captured->listingData);
        $this->assertSame('listing-entry_1', $captured->listingData[0]['uri']);
        $this->assertSame('/blog/', $captured->listingData[0]['parent_uri']);
        $this->assertSame(2, $captured->cacheDeletes);
        $this->assertCount(2, $captured->listingDupeChecks);
    }

    public function testPublishTabsBuildsSettingsForManagedPageChannel()
    {
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([]);
        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });
        ee()->setMock('cp', new class {
            public function add_js_script($type, $name)
            {
            }
        });
        ee()->setMock('javascript', new class {
            public function output($script)
            {
            }
        });
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                return false;
            }
            public function get($key)
            {
                return false;
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
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return null;
            }
        });
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                if (strpos($sql, 'SELECT listing_cid FROM exp_structure WHERE listing_cid != 0') !== false) {
                    return new eeDbResultMock([]);
                }
                return new eeDbResultMock([]);
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class {
            public function get_channel_by_entry_id($entryId)
            {
                return 5;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'n'];
            }
            public function get_site_pages($cacheBust = false)
            {
                return [
                    'uris' => [
                        7 => '/parent/',
                        10 => '/parent/current/'
                    ],
                    'templates' => [
                        10 => 2
                    ]
                ];
            }
            public function get_data()
            {
                return [
                    7 => ['parent_id' => 0, 'depth' => 0, 'title' => 'Parent', 'listing_cid' => 0],
                    10 => ['parent_id' => 7, 'depth' => 1, 'title' => 'Current', 'listing_cid' => 0],
                ];
            }
            public function get_listing_parent($channelId)
            {
                return false;
            }
            public function get_hidden_state($entryId)
            {
                return 'n';
            }
            public function get_templates()
            {
                return [
                    ['template_id' => 2, 'group_name' => 'pages', 'template_name' => 'index']
                ];
            }
        };
        $tab->structure = new class {
            public function get_structure_channels($type = '', $channelId = null)
            {
                if ($type === 'page') {
                    return [5 => ['type' => 'page', 'template_id' => 2]];
                }
                if ($type === 'listing') {
                    return [9 => ['channel_title' => 'Listing Channel', 'template_id' => 3]];
                }
                if ($channelId === 5) {
                    return [5 => ['type' => 'page', 'template_id' => 2]];
                }
                return [5 => ['type' => 'page', 'template_id' => 2]];
            }
        };

        $settings = $tab->publish_tabs(5, 10);

        $this->assertArrayHasKey('parent_id', $settings);
        $this->assertArrayHasKey('uri', $settings);
        $this->assertArrayHasKey('template_id', $settings);
        $this->assertArrayHasKey('hidden', $settings);
        $this->assertArrayHasKey('listing_channel', $settings);
        $this->assertSame('current', $settings['uri']['field_data']);
    }

    public function testPublishTabsReturnsEmptyWhenChannelIsUnmanaged()
    {
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });

        $tab = $this->makeTab();
        $tab->structure = new class {
            public function get_structure_channels($type = '', $channelId = null)
            {
                return [5 => ['type' => 'unmanaged']];
            }
        };
        $tab->sql = new class {
            public function get_channel_by_entry_id($entryId)
            {
                return 5;
            }
        };

        $this->assertSame([], $tab->publish_tabs(5, 0));
    }

    public function testPublishTabsCoversCachedCpAndExtensionModifyBranches()
    {
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        StaticCache::set('publish_tabs__get_structure_channels', [5 => ['type' => 'page', 'template_id' => 2]]);
        StaticCache::set('publish_tabs__get_structure_channels_page', [5 => ['type' => 'page', 'template_id' => 2]]);
        StaticCache::set('publish_tabs__get_structure_channels_channel_id_5', [5 => ['type' => 'page', 'template_id' => 2]]);

        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([]);
        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('cp', new class {
            public function add_js_script($type, $name)
            {
            }
        });
        ee()->setMock('javascript', new class {
            public function output($script)
            {
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
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
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                $map = [
                    'channel_id' => 5,
                    'entry_id' => 10,
                    'parent_id' => 7,
                ];
                return array_key_exists($key, $map) ? $map[$key] : false;
            }
            public function get($key)
            {
                return $key === 'parent_id' ? 7 : false;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return $name === 'structure_modify_publish_tab_settings';
            }
            public function call($name, ...$args)
            {
                $settings = $args[0];
                $settings['modified'] = true;
                return $settings;
            }
        });
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                if (strpos($sql, 'SELECT listing_cid FROM exp_structure WHERE listing_cid != 0') !== false) {
                    return new eeDbResultMock([['listing_cid' => 22]]);
                }
                return new eeDbResultMock([]);
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class {
            public function get_channel_by_entry_id($entryId)
            {
                return 5;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'n'];
            }
            public function get_site_pages($cacheBust = false)
            {
                return [
                    'uris' => [
                        7 => '/parent/',
                        10 => '/',
                    ],
                    'templates' => [
                        10 => 2
                    ]
                ];
            }
            public function get_data()
            {
                return [
                    7 => ['parent_id' => 0, 'depth' => 0, 'title' => 'Parent', 'listing_cid' => 0],
                    10 => ['parent_id' => 7, 'depth' => 1, 'title' => 'Current', 'listing_cid' => 0],
                ];
            }
            public function get_listing_parent($channelId)
            {
                return false;
            }
            public function get_hidden_state($entryId)
            {
                return 'n';
            }
            public function get_templates()
            {
                return [
                    ['template_id' => 2, 'group_name' => 'pages', 'template_name' => 'index']
                ];
            }
        };
        $tab->structure = new class {
            public function get_structure_channels($type = '', $channelId = null)
            {
                if ($type === 'listing') {
                    return [22 => ['channel_title' => 'Used Listing'], 23 => ['channel_title' => 'Free Listing']];
                }
                return [5 => ['type' => 'page', 'template_id' => 2]];
            }
        };

        $settings = $tab->publish_tabs(0, '');

        $this->assertArrayHasKey('modified', $settings);
        $this->assertSame('/', $settings['uri']['field_data']);
    }

    public function testPublishDataDbReturnsEarlyForBulkAction()
    {
        $_POST['bulk_action'] = 'delete';

        $tab = $this->makeTab();
        $tab->publish_data_db([]);

        $this->assertTrue(true);
        unset($_POST['bulk_action']);
    }

    public function testPublishDataDbCoversModDataAndValidationTruePaths()
    {
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        $_POST = [];

        $captured = (object) ['setData' => []];
        $input = new class {
            public $map = [];
            public function get_post($key)
            {
                return array_key_exists($key, $this->map) ? $this->map[$key] : false;
            }
        };

        ee()->setMock('input', $input);
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });
        ee()->setMock('Validation', new class {
            public function make($rules)
            {
                return new class {
                    public function validate($data)
                    {
                        return new class {
                            public function isValid()
                            {
                                return true;
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return $name === 'structure_allow_dupes';
            }
            public function call($name, ...$args)
            {
                return true;
            }
        });
        ee()->setMock('cache', new class {
            public function delete($path, $scope)
            {
            }
        });
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                if (strpos($sql, 'SELECT entry_id FROM exp_channel_titles WHERE entry_id = ') !== false) {
                    return new class {
                        public $num_rows = 1;
                    };
                }
                return new eeDbResultMock([]);
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class {
            public function get_site_pages($cacheBust = false)
            {
                return [
                    'uris' => [15 => '/parent/'],
                    'templates' => [333 => 9],
                ];
            }
            public function is_duplicate_page_uri($entryId, $uri)
            {
                return false;
            }
        };
        $tab->structure = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_structure_channels($type = '', $channelId = null)
            {
                return [5 => ['type' => 'page', 'template_id' => 9]];
            }
            public function create_page_uri($parentUri, $uri)
            {
                return rtrim($parentUri, '/') . '/' . trim($uri, '/') . '/';
            }
            public function set_data($data, $cacheBust = false)
            {
                $this->captured->setData[] = $data;
            }
        };

        $input->map = ['structure_parent_id' => false, 'structure__parent_id' => null];
        $tab->publish_data_db([
            'entry_id' => 333,
            'meta' => ['channel_id' => 5, 'site_id' => 1, 'title' => 'Meta Title'],
            'mod_data' => [
                'parent_id' => [15],
                'template_id' => 9,
                'hidden' => 'y',
                'uri' => 'Provided URI',
                'listing_channel' => 'n',
            ],
        ]);

        $input->map = ['structure_parent_id' => 13, 'structure__parent_id' => null];
        $tab->publish_data_db([
            'entry_id' => 334,
            'meta' => ['channel_id' => 5, 'site_id' => 1, 'title' => 'Meta Title'],
            'mod_data' => [
                'template_id' => 9,
                'hidden' => 'y',
                'uri' => 'Provided URI',
                'listing_channel' => 'n',
            ],
        ]);

        $this->assertCount(2, $captured->setData);
        $this->assertSame(15, $captured->setData[0]['parent_id']);
        $this->assertSame(13, $captured->setData[1]['parent_id']);
    }

    public function testPublishDataDbCoversEe2FallbackBranches()
    {
        $_POST = [];

        ee()->setMock('input', new class {
            public function get_post($key)
            {
                return false;
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });
        ee()->setMock('Validation', new class {
            public function make($rules)
            {
                return new class {
                    public function validate($data)
                    {
                        return new class {
                            public function isValid()
                            {
                                return false;
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;
                    public function __construct($value)
                    {
                        $this->value = $value;
                    }
                    public function urlSlug()
                    {
                        $this->value = strtolower(trim(str_replace(' ', '-', $this->value)));
                        return $this;
                    }
                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return null;
            }
        });
        ee()->setMock('cache', new class {
            public function delete($path, $scope)
            {
            }
        });

        $captured = (object) ['setData' => []];
        $tab = $this->makeTab();
        $tab->sql = new class {
            public function get_site_pages($cacheBust = false)
            {
                return ['uris' => [], 'templates' => []];
            }
            public function get_parent_id($entryId, $default = null)
            {
                return 0;
            }
            public function get_hidden_state($entryId)
            {
                return 'n';
            }
            public function get_listing_channel($entryId)
            {
                return 0;
            }
            public function is_duplicate_page_uri($entryId, $uri)
            {
                return false;
            }
        };
        $tab->structure = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_structure_channels($type = '', $channelId = null)
            {
                return [0 => ['type' => 'page', 'template_id' => 2]];
            }
            public function create_page_uri($parentUri, $uri)
            {
                return '/' . trim($uri, '/') . '/';
            }
            public function set_data($data, $cacheBust = false)
            {
                $this->captured->setData[] = $data;
            }
        };

        $tab->publish_data_db([]);

        $this->assertCount(1, $captured->setData);
        $this->assertSame(0, $captured->setData[0]['entry_id']);
        $this->assertSame(0, $captured->setData[0]['parent_id']);
    }

    public function testPublishDataDbCoversExistingEntryFallbackAndUrlTitlePaths()
    {
        $_POST = [];

        $captured = (object) ['setData' => []];

        ee()->setMock('input', new class {
            public function get_post($key)
            {
                return false;
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });
        ee()->setMock('Validation', new class {
            public function make($rules)
            {
                return new class {
                    public function validate($data)
                    {
                        return new class {
                            public function isValid()
                            {
                                return false;
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;
                    public function __construct($value)
                    {
                        $this->value = $value;
                    }
                    public function urlSlug()
                    {
                        $this->value = strtolower(trim(str_replace(' ', '-', $this->value)));
                        return $this;
                    }
                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return null;
            }
        });
        ee()->setMock('cache', new class {
            public function delete($path, $scope)
            {
            }
        });
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                if (strpos($sql, 'entry_id = 11') !== false) {
                    return new class {
                        public $num_rows = 0;
                    };
                }
                if (strpos($sql, 'SELECT entry_id FROM exp_channel_titles WHERE entry_id = ') !== false) {
                    return new class {
                        public $num_rows = 1;
                    };
                }
                return new eeDbResultMock([]);
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class {
            public function get_site_pages($cacheBust = false)
            {
                return [
                    'uris' => [22 => '/foo/bar/'],
                    'templates' => [22 => 7],
                ];
            }
            public function get_parent_id($entryId, $default = null)
            {
                return 9;
            }
            public function get_hidden_state($entryId)
            {
                return 'y';
            }
            public function get_listing_channel($entryId)
            {
                return 0;
            }
            public function is_duplicate_page_uri($entryId, $uri)
            {
                return false;
            }
        };
        $tab->structure = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_structure_channels($type = '', $channelId = null)
            {
                return [5 => ['type' => 'page', 'template_id' => 9]];
            }
            public function create_page_uri($parentUri, $uri)
            {
                return rtrim($parentUri, '/') . '/' . trim($uri, '/') . '/';
            }
            public function set_data($data, $cacheBust = false)
            {
                $this->captured->setData[] = $data;
            }
        };

        $tab->publish_data_db(['entry_id' => 11, 'meta' => ['channel_id' => 5, 'site_id' => 1]]);
        $tab->publish_data_db(['entry_id' => 22, 'meta' => ['channel_id' => 5, 'site_id' => 1]]);

        $entry = (object) ['entry_id' => 33, 'channel_id' => 5, 'site_id' => 1, 'url_title' => 'from-url-title'];
        $tab->publish_data_db([], $entry);

        $this->assertCount(2, $captured->setData);
        $this->assertStringContainsString('bar', $captured->setData[0]['uri']);
        $this->assertStringContainsString('from-url-title', $captured->setData[1]['uri']);
    }

    public function testPublishDataDbCoversListingHookPaths()
    {
        $_POST = [];

        $captured = (object) ['listingData' => []];

        ee()->setMock('input', new class {
            public function get_post($key)
            {
                return false;
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });
        ee()->setMock('Validation', new class {
            public function make($rules)
            {
                return new class {
                    public function validate($data)
                    {
                        return new class {
                            public function isValid()
                            {
                                return false;
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;
                    public function __construct($value)
                    {
                        $this->value = $value;
                    }
                    public function urlSlug()
                    {
                        $this->value = strtolower(trim(str_replace(' ', '-', $this->value)));
                        return $this;
                    }
                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });
        ee()->setMock('cache', new class {
            public function delete($path, $scope)
            {
            }
        });
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                if (strpos($sql, 'SELECT entry_id FROM exp_channel_titles WHERE entry_id = ') !== false) {
                    return new class {
                        public $num_rows = 1;
                    };
                }
                return new eeDbResultMock([]);
            }
        });

        $state = (object) ['parent' => 0];
        ee()->setMock('extensions', new class($state) {
            private $state;
            public function __construct($state)
            {
                $this->state = $state;
            }
            public function active_hook($name)
            {
                return in_array($name, ['structure_listing_parent', 'structure_allow_dupes'], true);
            }
            public function call($name, ...$args)
            {
                if ($name === 'structure_listing_parent') {
                    return $this->state->parent;
                }
                if ($name === 'structure_allow_dupes') {
                    return true;
                }
                return null;
            }
        });

        $tab = $this->makeTab();
        $tab->sql = new class($captured, $state) {
            private $captured;
            private $state;
            public function __construct($captured, $state)
            {
                $this->captured = $captured;
                $this->state = $state;
            }
            public function get_site_pages($cacheBust = false)
            {
                return ['uris' => [44 => '/listing-parent/'], 'templates' => []];
            }
            public function get_listing_parent($channelId)
            {
                return $this->state->parent;
            }
            public function get_listing_channel($entryId)
            {
                return 9;
            }
            public function is_duplicate_listing_uri($entryId, $uri, $parentId)
            {
                return false;
            }
            public function set_listing_data($data)
            {
                $this->captured->listingData[] = $data;
            }
        };
        $tab->structure = new class {
            public function get_structure_channels($type = '', $channelId = null)
            {
                return [9 => ['type' => 'listing', 'template_id' => 2]];
            }
        };

        $entry = (object) ['entry_id' => 90, 'channel_id' => 9, 'site_id' => 1, 'title' => 'Listing Item'];

        $state->parent = 0;
        $tab->publish_data_db(['uri' => 'listing-item', 'template_id' => [2]], $entry);

        $state->parent = 44;
        $tab->publish_data_db(['uri' => 'listing-item', 'template_id' => [2]], $entry);

        $this->assertCount(1, $captured->listingData);
        $this->assertSame('/listing-parent/', $captured->listingData[0]['parent_uri']);
    }

    private function makeTab()
    {
        return (new ReflectionClass('Structure_tab'))->newInstanceWithoutConstructor();
    }
}
