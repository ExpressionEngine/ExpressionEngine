<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Members;

use ExpressionEngine\Controller\Members\Members;
use ExpressionEngine\Service\Model\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class MembersTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (! defined('USERNAME_MAX_LENGTH')) {
            define('USERNAME_MAX_LENGTH', 50);
        }

        if (! defined('PASSWORD_MAX_LENGTH')) {
            define('PASSWORD_MAX_LENGTH', 72);
        }

        require_once APPPATH . 'core/Controller.php';
        require_once SYSPATH . 'ee/ExpressionEngine/Controller/Members/Members.php';
    }

    public function setUp(): void
    {
        ee()->resetMocks();
    }

    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testRenderMemberTabEncodesCustomFieldInstructions()
    {
        $instructions = 'Member notes < 5 minutes & "review" > draft';
        $encodedInstructions = htmlentities($instructions, ENT_QUOTES, 'UTF-8');

        ee()->setMock('Model', new MemberModelFactoryStub([
            new MemberFieldDisplayStub($instructions)
        ]));
        ee()->setMock('Format', new FormatStub());
        ee()->setMock('View', new ViewFactoryStub());

        $controller = (new ReflectionClass(Members::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(Members::class, 'renderMemberTab');
        \TestReflectionHelper::makeMethodAccessible($method);

        $rendered = $method->invoke($controller, null);

        $this->assertStringNotContainsString($instructions, $rendered);
        $this->assertStringContainsString($encodedInstructions, $rendered);
    }

    /**
     * Empty role groups remain available on the member creation form.
     *
     * @return void
     */
    public function testRenderRolesTabIncludesEmptyGroups()
    {
        $roles = new Collection(array((object) array('role_id' => 5, 'name' => 'Members')));
        $roleGroups = new Collection(array((object) array(
            'group_id' => 3,
            'name' => 'Empty group',
            'Roles' => new Collection(array((object) array('role_id' => 0))),
        )));

        $roleQuery = $this->makeQueryMock(array('fields', 'order', 'filter', 'all'), $roles);
        $roleGroupQuery = $this->makeQueryMock(array('with', 'fields', 'order', 'all'), $roleGroups);
        $model = $this->getMockBuilder(\stdClass::class)->addMethods(array('get'))->getMock();
        $model->method('get')->willReturnMap(array(
            array('Role', $roleQuery),
            array('RoleGroup', $roleGroupQuery),
        ));

        $permission = $this->getMockBuilder(\stdClass::class)->addMethods(array('isSuperAdmin'))->getMock();
        $permission->method('isSuperAdmin')->willReturn(false);

        $view = $this->getMockBuilder(\stdClass::class)->addMethods(array('render'))->getMock();
        $view->method('render')->willReturnCallback(function ($vars) {
            return json_encode($vars['settings']);
        });
        $viewFactory = $this->getMockBuilder(\stdClass::class)->addMethods(array('make'))->getMock();
        $viewFactory->method('make')->willReturn($view);

        $url = $this->getMockBuilder(\stdClass::class)->addMethods(array('compile'))->getMock();
        $url->method('compile')->willReturn('members/roles/groups/create');
        $urlFactory = $this->getMockBuilder(\stdClass::class)->addMethods(array('make'))->getMock();
        $urlFactory->method('make')->willReturn($url);

        ee()->setMock('Model', $model);
        ee()->setMock('Permission', $permission);
        ee()->setMock('View', $viewFactory);
        ee()->setMock('CP/URL', $urlFactory);

        $controller = (new ReflectionClass(Members::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(Members::class, 'renderRolesTab');
        \TestReflectionHelper::makeMethodAccessible($method);

        $this->assertStringContainsString('Empty group', $method->invoke($controller, null));
    }

    /**
     * Create a fluent model query mock.
     *
     * @param string[] $methods
     * @param Collection $result
     * @return object
     */
    private function makeQueryMock(array $methods, Collection $result)
    {
        $query = $this->getMockBuilder(\stdClass::class)->addMethods($methods)->getMock();

        foreach (array_diff($methods, array('all')) as $method) {
            $query->method($method)->willReturnSelf();
        }

        $query->method('all')->willReturn($result);

        return $query;
    }
}

class MemberModelFactoryStub
{
    private $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function make($model)
    {
        return new MemberModelStub($this->fields);
    }
}

class MemberModelStub
{
    private $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function getDisplay()
    {
        return new MemberDisplayStub($this->fields);
    }
}

class MemberDisplayStub
{
    private $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }
}

class MemberFieldDisplayStub
{
    private $instructions;

    public function __construct($instructions)
    {
        $this->instructions = $instructions;
    }

    public function getLabel()
    {
        return 'Biography';
    }

    public function getInstructions()
    {
        return $this->instructions;
    }

    public function getName()
    {
        return 'm_field_id_1';
    }

    public function getForm()
    {
        return '<input type="text" name="m_field_id_1">';
    }

    public function isRequired()
    {
        return false;
    }
}

class FormatStub
{
    public function make($format, $content)
    {
        return new TextFormatterStub($content);
    }
}

class TextFormatterStub
{
    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function convertToEntities()
    {
        $this->content = htmlentities($this->content, ENT_QUOTES, 'UTF-8');

        return $this;
    }

    public function compile()
    {
        return (string) $this->content;
    }

    public function __toString()
    {
        return $this->compile();
    }
}

class ViewFactoryStub
{
    public function make($name)
    {
        return new SectionViewStub();
    }
}

class SectionViewStub
{
    public function render($vars)
    {
        $output = '';

        foreach ($vars['settings'] as $setting) {
            if (isset($setting['desc'])) {
                $output .= (string) $setting['desc'];
            }
        }

        return $output;
    }
}
