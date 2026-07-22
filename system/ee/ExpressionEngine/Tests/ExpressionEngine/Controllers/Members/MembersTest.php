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
