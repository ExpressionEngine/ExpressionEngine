<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/../../../eeObjectMock.php';

class EE_TemplateTestBase extends \PHPUnit\Framework\TestCase
{
    protected $template;

    protected function setUp(): void
    {
        // Define essential EE constants if not already defined
        $this->defineConstants();

        // Initialize the EE mock environment
        $this->setupMinimalEE();

        // Load required CodeIgniter helpers
        if (!function_exists('directory_map')) {
            $helperPath = BASEPATH . 'helpers/directory_helper.php';
            if (!file_exists($helperPath)) {
                // Fallback: try to find it relative to this file
                $helperPath = __DIR__ . '/../../../../../../system/ee/legacy/helpers/directory_helper.php';
            }
            require_once $helperPath;
        }

        // Define helper functions that legacy code expects
        if (!function_exists('\bool_config_item')) {
            eval('
                function bool_config_item($item)
                {
                    if (function_exists("ee") && ee() !== null) {
                        $value = ee()->config->item($item);
                    } else {
                        $value = false;
                    }

                    return $value === "y" || $value === true || $value === 1 || $value === "1";
                }
            ');
        }

        // Include the EE_Template class
        $templatePath = BASEPATH . 'libraries/Template.php';
        if (!file_exists($templatePath)) {
            // Fallback: try to find it relative to this file
            $templatePath = __DIR__ . '/../../../../../../system/ee/legacy/libraries/Template.php';
        }
        require_once $templatePath;

        // Create template instance
        $this->template = new \EE_Template();

        // Set up common mocks
        $this->setupMocks();
    }

    protected function tearDown(): void
    {
        // Reset EE mocks between tests
        ee()->resetMocks();
    }

    private function defineConstants(): void
    {
        // Constants are already defined by bootstrap.php
        // Just ensure they exist as fallbacks
        if (!defined('SYSPATH')) {
            define('SYSPATH', realpath(getcwd() . '/system/') . '/');
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', SYSPATH . 'ee/legacy/');
        }
        if (!defined('PATH_CACHE')) {
            define('PATH_CACHE', BASEPATH . 'system/ee/cache/');
        }
        if (!defined('PATH_THIRD')) {
            define('PATH_THIRD', BASEPATH . 'user/addons/');
        }
        if (!defined('PATH_ADDONS')) {
            define('PATH_ADDONS', BASEPATH . 'system/ee/ExpressionEngine/Addons/');
        }
        if (!defined('PATH_THEMES')) {
            define('PATH_THEMES', BASEPATH . 'themes/');
        }
        if (!defined('PATH_TMPL')) {
            define('PATH_TMPL', BASEPATH . 'user/templates/');
        }
        if (!defined('LD')) {
            define('LD', '{');
        }
        if (!defined('RD')) {
            define('RD', '}');
        }
        if (!defined('AMP')) {
            define('AMP', '&');
        }
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }
        if (!defined('APP_VER')) {
            define('APP_VER', '7.5.13');
        }
    }

    private function setupMinimalEE(): void
    {
        // Ensure ee() function is available
        if (!function_exists('ee')) {
            function ee($mock = '')
            {
                return new eeSingletonMock($mock);
            }
        }
    }

    protected function setupMocks(): void
    {
        // Mock config with basic site settings
        $configMock = $this->getMockBuilder(\FakeConfig::class)
            ->setMethods(['site_url'])
            ->getMock();
        $configMock->items = [
            'site_id' => 1,
            'site_short_name' => 'default_site',
            'site_url' => 'https://example.com/',
            'site_name' => 'Test Site',
            'site_label' => 'Test Site',
            'site_description' => 'Test Description',
            'site_index' => '',
            'webmaster_email' => 'test@example.com',
            'multiple_sites_enabled' => 'n',
            'show_profiler' => 'n',
            'smart_static_parsing' => 'y',
            'site_404' => '',
            'enable_template_routes' => 'n',
            'strict_urls' => 'n',
            'save_tmpl_files' => 'n',
            'hidden_template_indicator' => '_',
            'hidden_template_404' => 'n',
            'template_loop_prevention' => 'y',
            'allow_php' => 'n',
            'enable_hit_tracking' => 'y',
            'max_caches' => 1000,
            'send_headers' => 'y',
            'encode_removed_text' => '',
            'enable_frontedit' => 'n',
            'disable_tag_caching' => 'n'
        ];
        $configMock->_global_vars = []; // Initialize as empty array
        $configMock->method('site_url')->willReturn('https://example.com/');
        ee()->setMock('config', $configMock);

        // Mock session
        $sessionMock = new \eeSingletonSessionMock();
        $sessionMock->setUserdata('member_id', 0);
        $sessionMock->setUserdata('group_id', 3);
        $sessionMock->setUserdata('role_id', 3);
        $sessionMock->setUserdata('primary_role_id', 3);
        $sessionMock->setUserdata('primary_role_description', 'Guest');
        $sessionMock->setUserdata('primary_role_name', 'guest');
        $sessionMock->setUserdata('primary_role_short_name', 'guest');
        $sessionMock->setUserdata('username', '');
        $sessionMock->setUserdata('screen_name', '');
        $sessionMock->setUserdata('avatar_filename', '');
        $sessionMock->setUserdata('avatar_width', 0);
        $sessionMock->setUserdata('avatar_height', 0);
        $sessionMock->setUserdata('email', '');
        $sessionMock->setUserdata('ip_address', '127.0.0.1');
        $sessionMock->setUserdata('total_entries', 0);
        $sessionMock->setUserdata('total_comments', 0);
        $sessionMock->setUserdata('private_messages', 0);
        $sessionMock->setUserdata('total_forum_posts', 0);
        $sessionMock->setUserdata('total_forum_topics', 0);
        $sessionMock->setUserdata('total_forum_replies', 0);
        $sessionMock->setUserdata('mfa_enabled', 'n');
        $sessionMock->setUserdata('group_description', 'Guests');
        $sessionMock->setUserdata('group_title', 'Guests');
        ee()->setMock('session', $sessionMock);

        // Mock db
        $dbMock = new \FakeDb();
        ee()->setMock('db', $dbMock);

        // Mock functions
        $functionsMock = new \FakeFunctions();
        ee()->setMock('functions', $functionsMock);

        // Mock URI
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment', 'segment_array', 'uri_string', 'page_query_string'])
            ->getMock();
        $uriMock->method('segment')->willReturnCallback(function($n) {
            $segments = ['', 'default', 'index'];
            return isset($segments[$n]) ? $segments[$n] : false;
        });
        $uriMock->method('segment_array')->willReturn(['default', 'index']);
        $uriMock->method('uri_string')->willReturn('default/index');
        $uriMock->method('page_query_string')->willReturn('');
        $uriMock->uri_string = 'default/index'; // Direct property for access without method calls
        $uriMock->page_query_string = ''; // Direct property
        ee()->setMock('uri', $uriMock);

        // Mock extensions
        $extensionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['active_hook', 'call'])
            ->getMock();
        $extensionsMock->method('active_hook')->willReturn(false);
        ee()->setMock('extensions', $extensionsMock);

        // Mock core
        $coreMock = $this->getMockBuilder('stdClass')
            ->setMethods(['set_newrelic_transaction'])
            ->getMock();
        $coreMock->method('set_newrelic_transaction')->willReturn(null);
        ee()->setMock('core', $coreMock);

        // Mock load
        $loadMock = new \eeSingletonLoadMock();
        ee()->setMock('load', $loadMock);

        // Mock input
        $inputMock = new \eeSingletonInputMock();
        ee()->setMock('input', $inputMock);

        // Mock localize
        $localizeMock = $this->getMockBuilder('stdClass')
            ->setMethods(['now', 'string_to_timestamp', 'format_date'])
            ->getMock();
        $localizeMock->method('now')->willReturn(time());
        $localizeMock->method('string_to_timestamp')->willReturn(time());
        $localizeMock->method('format_date')->willReturnCallback(function($format, $timestamp, $localize = true) {
            // Handle EE date format strings that start with %
            if (strpos($format, '%') === 0) {
                $phpFormat = str_replace(
                    ['%Y', '%m', '%d', '%H', '%i', '%s'],
                    ['Y', 'm', 'd', 'H', 'i', 's'],
                    $format
                );
                return date($phpFormat, $timestamp);
            }
            return date($format, $timestamp);
        });
        // Add format property with common date formats
        $localizeMock->format = [
            '%D, %M %j, %Y' => 'D, M j, Y',
            '%M %j, %Y' => 'M j, Y',
            '%m/%d/%Y' => 'm/d/Y',
            '%m/%d/%y' => 'm/d/y',
            '%Y-%m-%d' => 'Y-m-d',
            '%d %M %Y' => 'd M Y'
        ];
        // Add now property
        $localizeMock->now = time();
        ee()->setMock('localize', $localizeMock);

        // Mock relative_date
        $relativeDateMock = $this->getMockBuilder('stdClass')
            ->setMethods(['create', 'calculate', 'render', 'valid_units'])
            ->getMock();
        $relativeDateMock->valid_units = ['years', 'months', 'days', 'hours', 'minutes', 'seconds'];
        $relativeDateMock->method('create')->willReturnSelf();
        $relativeDateMock->method('calculate')->willReturnSelf();
        $relativeDateMock->method('render')->willReturn('1 hour ago');
        ee()->setMock('relative_date', $relativeDateMock);

        // Mock logger
        $loggerMock = new \eeSingletonLoggerMock();
        ee()->setMock('logger', $loggerMock);

        // Mock cache
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get', 'save', 'get_metadata', 'delete'])
            ->getMock();
        $cacheMock->method('get')->willReturn(false);
        $cacheMock->method('save')->willReturn(true);
        $cacheMock->method('get_metadata')->willReturn([]);
        ee()->setMock('cache', $cacheMock);

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get', 'make'])
            ->getMock();
        $modelMock->method('get')->willReturnCallback(function($modelName) {
            return new class {
                public function with($relation) {
                    return $this;
                }
                public function fields() {
                    return $this;
                }
                public function all() {
                    return new class {
                        public function getDictionary() {
                            return [];
                        }
                    };
                }
            };
        });
        $modelMock->method('make')->willReturnCallback(function($modelName, $data = []) {
            return new class($data) {
                private $data;
                public function __construct($data) {
                    $this->data = $data;
                }
                public function save() {
                    return true;
                }
                public function getId() {
                    return 1;
                }
                public function Roles() {
                    return new class {
                        public function all() {
                            return new class {
                                public function pluck() {
                                    return [];
                                }
                            };
                        }
                    };
                }
            };
        });
        ee()->setMock('Model', $modelMock);

        // Mock Addon service
        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['all'])
            ->getMock();
        $addonMock->method('all')->willReturn([]);
        ee()->setMock('Addon', $addonMock);
    }
}