<?php

require_once __DIR__ . '/../../../eeObjectMock.php';

use PHPUnit\Framework\TestCase;

if (!function_exists('bool_config_item')) {
    function bool_config_item($item)
    {
        if (function_exists('ee') && ee() !== null) {
            $value = ee()->config->item($item);
        } else {
            $value = false;
        }

        return $value === 'y' || $value === true || $value === 1 || $value === '1';
    }
}

abstract class PagesTestBase extends TestCase
{
    private static $formHelperLoaded = false;

    protected function setUp(): void
    {
        ee()->resetMocks();
        $_POST = [];
        $_GET = [];

        $this->loadPagesFormHelper();

        ee()->config->setItem('disable_csrf_protection', 'n');
        ee()->setMock('uri', new class {
            public function reformat($action)
            {
                return $action;
            }
        });
    }

    protected function invokePrivate($object, string $method, array $args = [])
    {
        $ref = new ReflectionMethod($object, $method);
        \TestReflectionHelper::makeAccessible($ref);
        return $ref->invokeArgs($object, $args);
    }

    private function loadPagesFormHelper(): void
    {
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        if (!defined('CSRF_TOKEN')) {
            define('CSRF_TOKEN', 'csrf-token');
        }

        if (self::$formHelperLoaded) {
            return;
        }

        require_once SYSPATH . 'ee/legacy/helpers/form_helper.php';
        self::$formHelperLoaded = true;
    }
}
