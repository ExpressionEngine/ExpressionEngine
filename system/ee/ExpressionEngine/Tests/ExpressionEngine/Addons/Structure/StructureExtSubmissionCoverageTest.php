<?php

require_once __DIR__ . '/../../../eeObjectMock.php';

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
if (!defined('REQ')) {
    define('REQ', 'CP');
}
if (!defined('AJAX_REQUEST')) {
    define('AJAX_REQUEST', false);
}
if (!function_exists('load_class')) {
    function &load_class($class, $directory = 'core')
    {
        static $router;
        if ($router === null) {
            $router = new class {
                public function _parse_routes()
                {
                }
            };
        }
        return $router;
    }
}

require_once __DIR__ . '/../../../../Addons/structure/ext.structure.php';

use PHPUnit\Framework\TestCase;

class StructureExtSubmissionFixture extends Structure_ext
{
    public function __construct()
    {
    }
}

class StructureExtMutableInput
{
    public $postMap = [];
    public $getMap = [];

    public function post($key)
    {
        return $this->postMap[$key] ?? null;
    }

    public function get($key)
    {
        return $this->getMap[$key] ?? null;
    }
}

class StructureExtSubmissionCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    public function testWygwamConfigCoversLastCallAndListings()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://base.example/';
                }
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'url' => '{base_url}/',
                            'uris' => [15 => '/alpha/', 25 => '/listing-item/'],
                        ],
                    ];
                }
                return null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing = false)
            {
                return rtrim($base, '/') . '/' . ltrim($uri, '/');
            }
        });
        ee()->setMock('extensions', new class {
            public $last_call = ['link_types' => ['Legacy' => []]];
        });

        $fixture = new StructureExtSubmissionFixture();
        $fixture->sql = new class {
            public function get_data()
            {
                return [
                    15 => ['title' => 'Alpha', 'depth' => 1],
                    16 => ['title' => 'Missing In Site Pages', 'depth' => 2],
                ];
            }
            public function get_structure_channels($type = '')
            {
                if ($type === 'listing') {
                    return [
                        9 => ['channel_id' => 9, 'channel_title' => 'Listings'],
                    ];
                }
                return false;
            }
            public function get_entry_titles_by_channel($channelId)
            {
                return [
                    ['entry_id' => 25, 'title' => 'Listing Item'],
                    ['entry_id' => 26, 'title' => 'Skipped Listing Item'],
                ];
            }
        };

        $config = $fixture->wygwam_config(null, null);

        $this->assertArrayHasKey('link_types', $config);
        $this->assertArrayHasKey('Structure Pages', $config['link_types']);
        $this->assertCount(1, $config['link_types']['Structure Pages']);
        $this->assertSame('Alpha', $config['link_types']['Structure Pages'][0]['label']);

        $listingKey = 'Structure Listing: Listings';
        $this->assertArrayHasKey($listingKey, $config['link_types']);
        $this->assertCount(1, $config['link_types'][$listingKey]);
        $this->assertSame('Listing Item', $config['link_types'][$listingKey][0]['label']);
    }

    public function testSafecrackerSubmitEntryEndCoversEarlyReturnAndListingFlow()
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
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });

        $fixture = new StructureExtSubmissionFixture();
        $sql = new class {
            public $channelType = 'listing';
            public $saved = [];

            public function get_site_pages($cacheBust = false)
            {
                return [
                    'url' => '{base_url}/',
                    'uris' => [10 => '/parent/', 15 => '/parent/default-uri/'],
                    'templates' => [15 => 9],
                ];
            }
            public function get_channel_type($channelId)
            {
                return $this->channelType;
            }
            public function get_listing_entry($entryId)
            {
                return ['template_id' => 5, 'uri' => '/parent/default-uri/'];
            }
            public function get_default_template($channelId)
            {
                return 8;
            }
            public function is_valid_template($templateId)
            {
                return false;
            }
            public function get_listing_parent($channelId)
            {
                return 10;
            }
            public function get_parent_id($entryId, $default = null)
            {
                return 10;
            }
            public function create_full_uri($parentUri, $uri)
            {
                return rtrim($parentUri, '/') . '/' . ltrim($uri, '/');
            }
            public function get_listing_channel($parentId)
            {
                return 77;
            }
            public function get_hidden_state($entryId)
            {
                return 'n';
            }
            public function set_listing_data($entryData)
            {
                $this->saved[] = $entryData;
            }
        };
        $fixture->sql = $sql;

        $obj = new stdClass();
        $obj->channel = ['channel_id' => 2];
        $obj->entry = ['entry_id' => 15, 'url_title' => 'entry-title'];
        $obj->EE = (object) [
            'api_sc_channel_entries' => (object) [
                'data' => [
                    'structure_template_id' => 44,
                    'structure_uri' => 'custom-uri',
                    'structure_parent_id' => 10,
                    'structure_hidden' => 'y',
                ],
            ],
        ];

        $sql->channelType = null;
        $fixture->safecracker_submit_entry_end($obj);
        $this->assertSame([], $sql->saved);

        $sql->channelType = 'listing';
        $fixture->safecracker_submit_entry_end($obj);

        $this->assertCount(1, $sql->saved);
        $this->assertSame('/custom-uri', $sql->saved[0]['uri']);
        $this->assertSame('/parent/', $sql->saved[0]['parent_uri']);
        $this->assertSame(5, $sql->saved[0]['template_id']);
        $this->assertSame(77, $sql->saved[0]['listing_cid']);
        $this->assertSame('y', $sql->saved[0]['hidden']);
    }

    public function testChannelFormSubmitEntryEndCoversObjectArrayAndNewListingBranches()
    {
        $input = new StructureExtMutableInput();
        ee()->setMock('input', $input);
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
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });

        $fixture = new StructureExtSubmissionFixture();
        $sql = new class {
            public $channelType = 'listing';
            public $saved = [];

            public function get_site_pages($cacheBust = false)
            {
                return [
                    'url' => '{base_url}/',
                    'uris' => [10 => '/parent/', 15 => '/parent/existing-slug/'],
                    'templates' => [15 => 9, 99 => 9],
                ];
            }
            public function get_channel_type($channelId)
            {
                return $this->channelType;
            }
            public function get_listing_entry($entryId)
            {
                return ['template_id' => 5, 'uri' => '/parent/existing-slug/'];
            }
            public function get_default_template($channelId)
            {
                return 8;
            }
            public function is_valid_template($templateId)
            {
                return false;
            }
            public function get_listing_parent($channelId)
            {
                return 10;
            }
            public function get_parent_id($entryId, $default = null)
            {
                return 10;
            }
            public function create_full_uri($parentUri, $uri)
            {
                return rtrim($parentUri, '/') . '/' . ltrim($uri, '/');
            }
            public function get_listing_channel($parentId)
            {
                return 77;
            }
            public function get_hidden_state($entryId)
            {
                return 'n';
            }
            public function set_listing_data($entryData)
            {
                $this->saved[] = $entryData;
            }
        };
        $fixture->sql = $sql;

        $sql->channelType = null;
        $objNull = new stdClass();
        $objNull->channel = (object) ['channel_id' => 2];
        $objNull->entry = (object) ['entry_id' => 15, 'url_title' => 'ignored'];
        $fixture->channel_form_submit_entry_end($objNull);

        $sql->channelType = 'listing';
        $input->postMap = [
            'structure_template_id' => 111,
            'structure_uri' => 'from-post',
            'structure_parent_id' => 10,
        ];
        $objObject = new stdClass();
        $objObject->channel = (object) ['channel_id' => 2];
        $objObject->entry = (object) ['entry_id' => 15, 'url_title' => 'object-title'];
        $fixture->channel_form_submit_entry_end($objObject);

        $input->postMap = [];
        $objArray = new stdClass();
        $objArray->channel = ['channel_id' => 2];
        $objArray->entry = [
            'entry_id' => 15,
            'url_title' => 'array-title',
            'structure_template_id' => 222,
            'structure_uri' => 'from-array',
            'structure_parent_id' => 10,
            'structure_hidden' => 'n',
        ];
        $fixture->channel_form_submit_entry_end($objArray);

        $objNew = new stdClass();
        $objNew->channel = (object) ['channel_id' => 2];
        $objNew->entry = (object) ['entry_id' => 99, 'url_title' => 'brand-new'];
        $fixture->channel_form_submit_entry_end($objNew);

        $this->assertCount(3, $sql->saved);
        $this->assertSame('existing-slug', $sql->saved[0]['uri']);
        $this->assertSame('existing-slug', $sql->saved[1]['uri']);
        $this->assertSame('brand-new', $sql->saved[2]['uri']);
    }

    public function testSessionsStartLivePreviewPathPopulatesGlobals()
    {
        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public $_global_vars = [];
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://base.example/';
                }
                return null;
            }
        });
        ee()->setMock('input', new class {
            public function post($key)
            {
                if ($key === 'title') {
                    return 'Preview Title';
                }
                if ($key === 'structure__uri') {
                    return 'child';
                }
                if ($key === 'structure__parent_id') {
                    return 10;
                }
                return null;
            }
            public function get($key)
            {
                return null;
            }
        });
        ee()->setMock('uri', new class {
            public $segments = ['cp', 'publish', 'preview', 'entry', '15'];
            public $query_string = '';
            public $uri_string = '';
            public $rsegments = [];
            public function total_segments()
            {
                return count($this->segments);
            }
            public function segment($index)
            {
                return $this->segments[$index - 1] ?? null;
            }
            public function _set_uri_string($value)
            {
                $this->uri_string = trim($value, '/');
            }
            public function _explode_segments()
            {
                $this->segments = array_values(array_filter(explode('/', trim($this->uri_string, '/'))));
            }
            public function _reindex_segments()
            {
                $this->segments = array_values($this->segments);
            }
        });

        $fixture = new StructureExtSubmissionFixture();
        $fixture->site_pages = [
            'url' => '{base_url}/',
            'uris' => [10 => '/parent/', 15 => '/parent/existing-slug/'],
            'templates' => [10 => 8, 15 => 9],
        ];
        $fixture->sql = new class {
            public function get_uri()
            {
                return 'cp/publish/preview/entry/15';
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_channel_by_entry_id($entryId)
            {
                return 2;
            }
            public function get_channel_type($channelId)
            {
                return 'page';
            }
            public function get_parent_id($entryId, $default = null)
            {
                return 10;
            }
            public function get_page_title($entryId)
            {
                return 'Page ' . $entryId;
            }
            public function is_listing_entry($entryId)
            {
                return false;
            }
            public function get_listing_channel($entryId)
            {
                return false;
            }
            public function get_channel_name_by_channel_id($channelId)
            {
                return 'pages';
            }
            public function get_hidden_state($entryId)
            {
                return 'n';
            }
            public function get_child_entries($entryId)
            {
                if ((int) $entryId === 15) {
                    return [31, 32];
                }
                if ((int) $entryId === 10) {
                    return [15];
                }
                return [];
            }
            public function get_listing_channel_short_name($channelId)
            {
                return false;
            }
        };

        $fixture->sessions_start(new stdClass());

        $this->assertSame('15', (string) ee()->config->_global_vars['structure:page:entry_id']);
        $this->assertSame('/parent/child', ee()->config->_global_vars['structure:page:uri']);
        $this->assertSame(10, ee()->config->_global_vars['structure:parent:entry_id']);
    }
}
