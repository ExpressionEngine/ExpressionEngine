<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Design;

use ExpressionEngine\Model\Template\Template as TemplateModel;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    /** @var \ExpressionEngine\Controller\Design\Template */
    private $controller;

    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
    }

    /**
     * Reset shared state and create the controller without its constructor.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->session->resetUserdata();
        ee()->session->setUserdata('member_id', 10);
        ee()->setMock('input', new class {
            public function post($key)
            {
                return $_POST[$key] ?? null;
            }
        });
        ee()->setMock('localize', (object) ['now' => 1234567890]);

        $_POST = [];

        $reflection = new \ReflectionClass('ExpressionEngine\Controller\Design\Template');
        $this->controller = $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Reset shared request and dependency state.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_POST = [];
        ee()->resetMocks();
        ee()->session->resetUserdata();
        $this->controller = null;
    }

    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Design\Template') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('create', 'edit', 'search', 'searchtemplates', 'settings'), $controller_methods);
    }

    public function testNonSuperAdminCannotChangeExistingPhpSettings()
    {
        $this->setSuperAdmin(false);

        $template = new TestTemplateModel([
            'allow_php' => 'n',
            'php_parse_location' => 'o',
        ]);
        $template->markPersisted();

        $_POST = [
            'allow_php' => 'y',
            'php_parse_location' => 'i',
        ];

        $this->validateTemplate($template);

        $this->assertFalse($template->allow_php);
        $this->assertSame('o', $template->php_parse_location);
    }

    public function testNonSuperAdminNewTemplateUsesSafePhpDefaults()
    {
        $this->setSuperAdmin(false);

        $template = new TestTemplateModel([
            'allow_php' => 'y',
            'php_parse_location' => 'i',
        ]);

        $_POST = ['template_name' => 'example'];

        $this->validateTemplate($template);

        $this->assertFalse($template->allow_php);
        $this->assertSame('o', $template->php_parse_location);
    }

    public function testSuperAdminCanChangePhpSettings()
    {
        $this->setSuperAdmin(true);

        $template = new TestTemplateModel([
            'allow_php' => 'n',
            'php_parse_location' => 'o',
        ]);

        $_POST = [
            'allow_php' => 'y',
            'php_parse_location' => 'i',
        ];

        $this->validateTemplate($template);

        $this->assertTrue($template->allow_php);
        $this->assertSame('i', $template->php_parse_location);
    }

    /**
     * Configure the current member's Super Admin state.
     *
     * @param bool $isSuperAdmin
     * @return void
     */
    private function setSuperAdmin($isSuperAdmin)
    {
        ee()->setMock('Permission', new class($isSuperAdmin) {
            private $isSuperAdmin;

            public function __construct($isSuperAdmin)
            {
                $this->isSuperAdmin = $isSuperAdmin;
            }

            public function isSuperAdmin()
            {
                return $this->isSuperAdmin;
            }
        });
    }

    /**
     * Validate the template through the controller's save boundary.
     *
     * @param TemplateModel $template
     * @return ValidationResult
     */
    private function validateTemplate(TemplateModel $template)
    {
        $method = new \ReflectionMethod($this->controller, 'validateTemplate');
        \TestReflectionHelper::makeAccessible($method);

        return $method->invoke($this->controller, $template);
    }
}

/**
 * Template model double with isolated validation behavior.
 */
class TestTemplateModel extends TemplateModel
{
    /**
     * Return a successful validation result without external model services.
     *
     * @return ValidationResult
     */
    public function validate()
    {
        return new ValidationResult();
    }

    /**
     * Mark this template as an existing database record.
     *
     * @return void
     */
    public function markPersisted()
    {
        $this->_new = false;
    }
}
