<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../eeObjectMock.php';

/**
 * Base test class for EE_Template advanced method tests
 * Provides common mocking and setup for methods that need complex EE mocking
 */
abstract class EE_TemplateAdvancedMethodsTestBase extends TestCase
{
    protected $template;

    protected function setUp(): void
    {
        parent::setUp();

        // Define essential EE constants if not already defined
        $this->defineConstants();

        // Initialize the EE mock environment
        $this->setupMinimalEE();

        // Load required CodeIgniter helpers
        if (!function_exists('directory_map')) {
            require_once SYSPATH . 'ee/legacy/helpers/directory_helper.php';
        }

        // Include the EE_Template class
        require_once SYSPATH . 'ee/legacy/libraries/Template.php';

        // Create template instance
        $this->template = new \EE_Template();

        // Reset any existing mocks
        ee()->resetMocks();

        // Set up common mocks needed for template processing
        $this->setupCommonMocks();
    }

    protected function tearDown(): void
    {
        // Reset mocks after each test
        ee()->resetMocks();
        parent::tearDown();
    }

    private function defineConstants(): void
    {
        if (!defined('BASEPATH')) {
            define('BASEPATH', realpath(__DIR__ . '/../../../../../../') . '/');
        }
        if (!defined('APPPATH')) {
            define('APPPATH', BASEPATH . 'system/ee/legacy/');
        }
        if (!defined('SYSPATH')) {
            define('SYSPATH', BASEPATH . 'system/ee/');
        }
        if (!defined('PATH_CACHE')) {
            define('PATH_CACHE', BASEPATH . 'system/ee/cache/');
        }
        if (!defined('PATH_TMPL')) {
            define('PATH_TMPL', BASEPATH . 'system/ee/templates/');
        }
        if (!defined('LD')) {
            define('LD', '{');
        }
        if (!defined('RD')) {
            define('RD', '}');
        }
        if (!defined('QUERY_MARKER')) {
            define('QUERY_MARKER', '?');
        }
        if (!defined('SLASH')) {
            define('SLASH', '/');
        }
        if (!defined('SELF')) {
            define('SELF', 'index.php');
        }
        if (!defined('DEBUG')) {
            define('DEBUG', 1);
        }
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }
        if (!defined('REQ')) {
            define('REQ', 'PAGE');
        }
        if (!defined('CSRF_TOKEN')) {
            define('CSRF_TOKEN', 'test_token');
        }
    }

    private function setupMinimalEE(): void
    {
        // Initialize minimal EE environment for testing
        global $CFG;

        // Set up basic config
        if (!isset($CFG)) {
            $CFG = new \stdClass();
        }

        $CFG->config = [
            'site_id' => 1,
            'site_short_name' => 'default_site',
            'site_404' => 'error/404',
            'multiple_sites_enabled' => 'n',
            'template_loop_prevention' => 'y',
            'debug' => 1,
            'save_tmpl_files' => 'n'
        ];

        // Initialize empty global variables array
        if (!isset($CFG->config['_global_vars'])) {
            $CFG->config['_global_vars'] = [];
        }

        // Set up basic EE superglobal
        if (!isset($GLOBALS['EE'])) {
            $GLOBALS['EE'] = new \stdClass();
        }
    }

    /**
     * Set up common mocks used across advanced template method tests
     */
    protected function setupCommonMocks()
    {
        // Mock config with common settings
        $configMock = $this->getMockBuilder('stdClass')
            ->setMethods(['item', 'setItem', 'site_url'])
            ->getMock();
        $configMock->method('item')->willReturnCallback(function($key) {
            $defaults = [
                'site_id' => 1,
                'site_short_name' => 'default_site',
                'site_404' => 'error/404',
                'multiple_sites_enabled' => 'n',
                'template_loop_prevention' => 'y',
                'debug' => 1,
                'save_tmpl_files' => 'n'
            ];
            return $defaults[$key] ?? null;
        });
        $configMock->method('setItem')->willReturnSelf();
        $configMock->method('site_url')->willReturn('https://example.com/');
        $configMock->_global_vars = [];
        ee()->setMock('config', $configMock);

        // Mock session
        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['userdata', 'setUserdata', 'flashdata', 'getMember'])
            ->getMock();

        // Add userdata property directly with all user_vars
        $sessionMock->userdata = [
            'member_id' => 1,
            'group_id' => 1,
            'group_description' => 'Members',
            'group_title' => 'Members',
            'primary_role_id' => 3,
            'primary_role_description' => 'Guest',
            'primary_role_name' => 'Guest',
            'primary_role_short_name' => 'guest',
            'username' => 'testuser',
            'screen_name' => 'Test User',
            'avatar_filename' => '',
            'avatar_width' => '',
            'avatar_height' => '',
            'email' => 'test@example.com',
            'ip_address' => '127.0.0.1',
            'total_entries' => 0,
            'total_comments' => 0,
            'private_messages' => 0,
            'total_forum_posts' => 0,
            'total_forum_topics' => 0,
            'total_forum_replies' => 0,
            'mfa_enabled' => false,
            'role_id' => 3
        ];
        $sessionMock->method('userdata')->willReturnCallback(function($key, $default = false) use ($sessionMock) {
            return isset($sessionMock->userdata[$key]) ? $sessionMock->userdata[$key] : $default;
        });
        $sessionMock->method('setUserdata')->willReturnSelf();
        $sessionMock->method('flashdata')->willReturn([]);
        $sessionMock->method('getMember')->willReturn(new class {
            public function getAllRoles() {
                return new class {
                    public function pluck($field) {
                        return [3]; // Return guest role ID
                    }
                };
            }
        });
        ee()->setMock('session', $sessionMock);

        // Mock functions
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['create_url', 'extract_path', 'redirect', 'prep_conditionals', 'evaluate'])
            ->getMock();
        $functionsMock->method('create_url')->willReturnCallback(function($path) {
            // If it's already a full URL, return as-is
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }
            return 'https://example.com/' . ltrim($path, '/');
        });
        $functionsMock->method('extract_path')->willReturnCallback(function($path) {
            // Extract path from format like "=url" or "path:variable"
            if (preg_match("#=(.*)#", $path, $match)) {
                return trim($match[1], '}"\'');
            }
            return str_replace(['path:', 'redirect:'], '', $path);
        });
        $functionsMock->method('redirect')->willReturnCallback(function($url) {
            // Mock redirect - don't actually redirect in tests
            throw new \Exception("Redirect called: {$url}");
        });
        $functionsMock->method('prep_conditionals')->willReturnArgument(0);
        $functionsMock->method('evaluate')->willReturnCallback(function($code) {
            // Safe evaluation for testing
            ob_start();
            eval('?>' . $code);
            return ob_get_clean();
        });
        ee()->setMock('functions', $functionsMock);

        // Mock URI
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment', 'segment_array', 'uri_string'])
            ->getMock();
        $uriMock->method('segment')->willReturnCallback(function($n) {
            $segments = ['', 'news', 'article', '123'];
            return isset($segments[$n]) ? $segments[$n] : false;
        });
        $uriMock->method('segment_array')->willReturn(['', 'news', 'article', '123']);
        $uriMock->method('uri_string')->willReturn('news/article/123');
        ee()->setMock('uri', $uriMock);

        // Mock database
        $dbMock = $this->getMockBuilder('eeDbArMock')->getMock();
        ee()->setMock('db', $dbMock);

        // Mock Model for getMemberVariables
        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();
        $roleQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fields', 'all'])
            ->getMock();
        $roleQueryMock->method('fields')->willReturnSelf();
        $roleQueryMock->method('all')->willReturn([
            new class {
                public function getId() { return 3; }
                public $short_name = 'guest';
                public $name = 'Guest';
            }
        ]);
        $modelMock->method('get')->willReturn($roleQueryMock);
        ee()->setMock('Model', $modelMock);

        // Mock localize
        $localizeMock = $this->getMockBuilder('stdClass')
            ->setMethods(['now', 'format_date'])
            ->getMock();
        $localizeMock->method('now')->willReturn(time());
        $localizeMock->method('format_date')->willReturnCallback(function($format, $timestamp) {
            return date($format, $timestamp);
        });
        $localizeMock->format = []; // Add format property for date constants
        $localizeMock->now = time(); // Add now property
        ee()->setMock('localize', $localizeMock);

        // Mock output
        $outputMock = $this->getMockBuilder('stdClass')
            ->setMethods(['set_status_header', 'set_output', '_display', 'fatal_error'])
            ->getMock();
        $outputMock->method('set_status_header')->willReturnSelf();
        $outputMock->method('set_output')->willReturnSelf();
        $outputMock->method('_display')->willReturn(null);
        $outputMock->method('fatal_error')->willReturnCallback(function($msg) {
            throw new \Exception("Fatal error: {$msg}");
        });
        ee()->setMock('output', $outputMock);

        // Mock extensions
        $extensionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['active_hook', 'call'])
            ->getMock();
        $extensionsMock->method('active_hook')->willReturn(false);
        $extensionsMock->method('call')->willReturn(null);
        ee()->setMock('extensions', $extensionsMock);
    }

    /**
     * Helper to set up template with specific parameters
     */
    protected function setupTemplateWithParams($params = [])
    {
        $this->template->tagparams = $params;

        // Use reflection to set protected ignore_fetch property
        $reflection = new \ReflectionClass($this->template);
        $ignoreFetchProperty = $reflection->getProperty('ignore_fetch');
        \TestReflectionHelper::makePropertyAccessible($ignoreFetchProperty);
        $ignoreFetchProperty->setValue($this->template, ['url_title']);
    }

    /**
     * Helper to set up template with specific no_results content
     */
    protected function setupTemplateWithNoResults($noResults = '')
    {
        // Use reflection to set protected no_results property
        $reflection = new \ReflectionClass($this->template);
        $noResultsProperty = $reflection->getProperty('no_results');
        \TestReflectionHelper::makePropertyAccessible($noResultsProperty);
        $noResultsProperty->setValue($this->template, $noResults);
    }

    /**
     * Helper to create a mock template for embed testing
     */
    protected function createMockEmbedTemplate($group = 'news', $template = 'article', $content = 'Embedded content')
    {
        // Mock the fetch_template method to return our test content
        $originalFetchTemplate = $this->template->fetch_template ?? null;
        $this->template->fetch_template = function($tg, $t) use ($group, $template, $content) {
            if ($tg === $group && $t === $template) {
                return $content;
            }
            return false;
        };

        return $originalFetchTemplate;
    }

    /**
     * Helper to restore original fetch_template method
     */
    protected function restoreFetchTemplate($originalMethod = null)
    {
        if ($originalMethod) {
            $this->template->fetch_template = $originalMethod;
        } elseif (method_exists($this->template, 'fetch_template')) {
            // Restore original method if it exists
            unset($this->template->fetch_template);
        }
    }
}
