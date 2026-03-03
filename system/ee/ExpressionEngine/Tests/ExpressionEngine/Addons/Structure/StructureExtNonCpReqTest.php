<?php

use PHPUnit\Framework\TestCase;

class StructureExtNonCpReqTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCpCustomMenuReturnsTrueOutsideCpRequest()
    {
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
            define('REQ', 'PAGE');
        }
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }

        require_once __DIR__ . '/../../../../Addons/structure/ext.structure.php';

        ee()->resetMocks();

        $ext = (new ReflectionClass('Structure_ext'))->newInstanceWithoutConstructor();
        $menu = new class {
            public $items = [];
            public function addItem($title, $url)
            {
                $this->items[] = [$title, $url];
            }
        };

        $this->assertTrue($ext->cp_custom_menu($menu));
        $this->assertSame([], $menu->items);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAfterSaveAndLoginEarlyReturnBranchesOutsideCpAndAjax()
    {
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
            define('REQ', 'ACTION');
        }
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', true);
        }

        require_once __DIR__ . '/../../../../Addons/structure/ext.structure.php';

        ee()->resetMocks();
        ee()->setMock('functions', new class {
            public function redirect($url)
            {
                throw new RuntimeException('redirect should not be called');
            }
        });

        $ext = (new ReflectionClass('Structure_ext'))->newInstanceWithoutConstructor();
        $ext->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_login' => 'y'];
            }
        };

        $this->assertFalse($ext->after_channel_entry_save((object) ['channel_id' => 2], []));
        $this->assertNull($ext->cp_member_login());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSessionsStartCoversActionPreviewAndActionPagePaths()
    {
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
            define('REQ', 'ACTION');
        }
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }

        require_once __DIR__ . '/../../../../Addons/structure/ext.structure.php';

        ee()->resetMocks();

        $input = new class {
            public $act = 99;
            public function get($key)
            {
                if ($key === 'ACT') {
                    return $this->act;
                }
                return null;
            }
            public function post($key)
            {
                if ($key === 'title') {
                    return 'Action Preview';
                }
                if ($key === 'structure__uri') {
                    return 'child';
                }
                if ($key === 'structure__parent_id') {
                    return 10;
                }
                return null;
            }
        };
        ee()->setMock('input', $input);
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
        ee()->setMock('uri', new class {
            public $segments = ['cp', 'publish', 'preview', 'entry', '15'];
            public $rsegments = [];
            public $query_string = '';
            public $uri_string = '';
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
        ee()->setMock('db', new class {
            public function select($field)
            {
                return $this;
            }
            public function where($field, $value)
            {
                return $this;
            }
            public function get($table)
            {
                return new class {
                    public function num_rows()
                    {
                        return 1;
                    }
                    public function row($column = null)
                    {
                        return 99;
                    }
                };
            }
        });

        $ext = (new ReflectionClass('Structure_ext'))->newInstanceWithoutConstructor();
        $ext->site_pages = [
            'url' => '{base_url}/',
            'uris' => [10 => '/parent/', 15 => '/parent/child/', 20 => '/alpha/'],
            'templates' => [10 => 8, 15 => 9, 20 => 6],
        ];
        $ext->sql = new class {
            public $uri = 'action/live-preview';
            public function get_uri()
            {
                return $this->uri;
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
                if ((int) $entryId === 20) {
                    return [21];
                }
                if ((int) $entryId === 10) {
                    return [15, 20];
                }
                return [];
            }
            public function get_listing_channel_short_name($channelId)
            {
                return false;
            }
        };

        // ACTION live preview branch
        $ext->sessions_start(new stdClass());

        // ACTION non-preview branch
        $input->act = 0;
        $ext->sql->uri = '/alpha/';
        ee()->uri->segments = ['alpha', 'P4'];
        ee()->uri->uri_string = 'alpha/P4';
        $ext->sessions_start(new stdClass());

        $this->assertSame('20', (string) ee()->config->_global_vars['structure:page:entry_id']);
    }

}
