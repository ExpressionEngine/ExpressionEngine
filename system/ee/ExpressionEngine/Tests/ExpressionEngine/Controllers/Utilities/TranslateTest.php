<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Utilities;

use PHPUnit\Framework\TestCase;

class TranslateTest extends TestCase
{
    private $controller;

    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
        require_once(SYSPATH . 'ee/ExpressionEngine/Tests/eeObjectMock.php');
    }

    public function setUp(): void
    {
        if (function_exists('ee')) {
            ee()->resetMocks();
        }

        $reflection = new \ReflectionClass('ExpressionEngine\Controller\Utilities\Translate');
        $this->controller = $reflection->newInstanceWithoutConstructor();
    }

    public function tearDown(): void
    {
        if (function_exists('ee')) {
            ee()->resetMocks();
        }

        $this->controller = null;
    }

    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Utilities\Translate') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        // This one has more routable functions due to __call(), we need to
        // test those as well @TODO
        $this->assertEquals(array('index'), $controller_methods);
    }

    public function testGetAllowedTranslationKeysSkipsEmptyKey()
    {
        ee()->setMock('lang', new class {
            public function load($file, $language, $return)
            {
                return [
                    'alpha' => 'Alpha',
                    '' => '',
                    'beta' => 'Beta'
                ];
            }
        });

        $allowed = $this->invokePrivateMethod('getAllowedTranslationKeys', ['addons']);

        $this->assertSame(['alpha', 'beta'], $allowed);
    }

    public function testNormalizeSubmittedTranslationsRejectsUnexpectedKeys()
    {
        $payload_key = "x' => `id`, 'y";
        $normalized = $this->invokePrivateMethod('normalizeSubmittedTranslations', [[
            'csrf_token' => 'token',
            'alpha' => 'ok',
            $payload_key => 'pwned'
        ], ['alpha'], function ($value) {
            return $value;
        }]);

        $this->assertFalse($normalized['is_valid']);
        $this->assertSame([$payload_key], $normalized['unexpected_keys']);
        $this->assertSame([], $normalized['invalid_value_keys']);
    }

    public function testNormalizeSubmittedTranslationsUsesAllowlistOrderAndKnownFormKeys()
    {
        $normalized = $this->invokePrivateMethod('normalizeSubmittedTranslations', [[
            'csrf_token' => 'token',
            'second' => '2',
            'first' => '1',
            'site_id' => '1'
        ], ['first', 'second'], function ($value) {
            return $value;
        }]);

        $this->assertTrue($normalized['is_valid']);
        $this->assertSame(['first', 'second'], array_keys($normalized['translations']));
        $this->assertSame(['first' => '1', 'second' => '2'], $normalized['translations']);
    }

    public function testNormalizeSubmittedTranslationsRejectsNonScalarValues()
    {
        $normalized = $this->invokePrivateMethod('normalizeSubmittedTranslations', [[
            'alpha' => ['nested' => 'value'],
            'beta' => 'ok'
        ], ['alpha', 'beta'], function ($value) {
            return $value;
        }]);

        $this->assertFalse($normalized['is_valid']);
        $this->assertSame(['alpha'], $normalized['invalid_value_keys']);
        $this->assertSame(['beta' => 'ok'], $normalized['translations']);
    }

    public function testRenderLanguagePhpProducesLoadableSafeArray()
    {
        $translations = [
            "x' => `id`, 'y" => "keep `backticks` as text",
            'path' => 'C:\\temp\\data'
        ];

        $php = $this->invokePrivateMethod('renderLanguagePhp', [$translations]);
        $tmp = tempnam(sys_get_temp_dir(), 'translate_test_');
        file_put_contents($tmp, $php);

        $lang = null;
        require $tmp;
        unlink($tmp);

        $this->assertIsArray($lang);
        $this->assertSame($translations["x' => `id`, 'y"], $lang["x' => `id`, 'y"]);
        $this->assertSame($translations['path'], $lang['path']);
        $this->assertSame('', $lang['']);
    }

    /**
     * Invoke a private controller method to unit test isolated helper behavior.
     *
     * @param string $method
     * @param array<int, mixed> $args
     * @return mixed
     */
    private function invokePrivateMethod($method, array $args = [])
    {
        $reflection = new \ReflectionMethod($this->controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->controller, $args);
    }
}
