<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

// Bootstrap minimal EE environment
if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../../../../../');
}
if (!defined('APPPATH')) {
    define('APPPATH', BASEPATH . 'ee/');
}
if (!defined('SYSPATH')) {
    define('SYSPATH', BASEPATH . '../');
}
if (!defined('PATH_CACHE')) {
    define('PATH_CACHE', SYSPATH . 'user/cache/');
}
if (!defined('PATH_THIRD')) {
    define('PATH_THIRD', SYSPATH . 'user/addons/');
}
if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', SYSPATH . 'ee/ExpressionEngine/Addons/');
}
if (!defined('PATH_THEMES')) {
    define('PATH_THEMES', realpath(SYSPATH . '../themes') . '/');
}

// Template constants
if (!defined('LD')) {
    define('LD', '{');
}
if (!defined('RD')) {
    define('RD', '}');
}
if (!defined('AMP')) {
    define('AMP', '&amp;');
}

// Include required files
require_once APPPATH . '../legacy/libraries/Template.php';

// Include eeObjectMock for mocking
require_once __DIR__ . '/../../../eeObjectMock.php';

/**
 * Base test class for EE_Template tests
 *
 * Provides common setup and mocking infrastructure for all EE_Template test classes.
 */
class EE_TemplateTestBase extends TestCase
{
    /**
     * @var \EE_Template
     */
    protected $template;

    /**
     * @var TestEnvironment
     */
    protected $__EE_TEST_ENV__;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize test environment
        $this->__EE_TEST_ENV__ = new \TestEnvironment();

        // Set up mocks for EE_Template dependencies
        $this->setupMocks();

        // Create EE_Template instance
        $this->template = new \EE_Template();
    }

    protected function tearDown(): void
    {
        // Reset mocks after each test
        if (isset($this->__EE_TEST_ENV__)) {
            ee()->resetMocks();
        }

        parent::tearDown();
    }

    /**
     * Set up the necessary mocks for EE_Template testing
     */
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
            'multiple_sites_enabled' => 'n',
            'show_profiler' => 'n'
        ];
        $configMock->method('site_url')->willReturn('https://example.com/');
        $this->__EE_TEST_ENV__->setMock('config', $configMock);

        // Mock session
        $sessionMock = new \eeSingletonSessionMock();
        $sessionMock->setUserdata('member_id', 0);
        $sessionMock->setUserdata('group_id', 3);
        $sessionMock->setUserdata('role_id', 3);
        $this->__EE_TEST_ENV__->setMock('session', $sessionMock);

        // Mock database
        $dbMock = new \FakeDb();
        $this->__EE_TEST_ENV__->setMock('db', $dbMock);

        // Mock functions
        $functionsMock = new \FakeFunctions();
        $this->__EE_TEST_ENV__->setMock('functions', $functionsMock);

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
        $this->__EE_TEST_ENV__->setMock('uri', $uriMock);

        // Mock extensions
        $extensionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['active_hook', 'call'])
            ->getMock();
        $extensionsMock->method('active_hook')->willReturn(false);
        $this->__EE_TEST_ENV__->setMock('extensions', $extensionsMock);

        // Mock core
        $coreMock = $this->getMockBuilder('stdClass')
            ->setMethods(['set_newrelic_transaction'])
            ->getMock();
        $coreMock->method('set_newrelic_transaction')->willReturn(null);
        $this->__EE_TEST_ENV__->setMock('core', $coreMock);

        // Mock load
        $loadMock = new \eeSingletonLoadMock();
        $this->__EE_TEST_ENV__->setMock('load', $loadMock);

        // Mock input
        $inputMock = new \eeSingletonInputMock();
        $this->__EE_TEST_ENV__->setMock('input', $inputMock);

        // Mock localize
        $localizeMock = $this->getMockBuilder('stdClass')
            ->setMethods(['now', 'string_to_timestamp', 'format_date'])
            ->getMock();
        $localizeMock->method('now')->willReturn(time());
        $localizeMock->method('string_to_timestamp')->willReturn(time());
        $localizeMock->method('format_date')->willReturn('2024-01-01');
        $this->__EE_TEST_ENV__->setMock('localize', $localizeMock);

        // Mock logger
        $loggerMock = new \eeSingletonLoggerMock();
        $this->__EE_TEST_ENV__->setMock('logger', $loggerMock);

        // Mock cache
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get', 'save', 'get_metadata', 'delete'])
            ->getMock();
        $cacheMock->method('get')->willReturn(false);
        $cacheMock->method('save')->willReturn(true);
        $cacheMock->method('get_metadata')->willReturn([]);
        $this->__EE_TEST_ENV__->setMock('cache', $cacheMock);
    }
}
