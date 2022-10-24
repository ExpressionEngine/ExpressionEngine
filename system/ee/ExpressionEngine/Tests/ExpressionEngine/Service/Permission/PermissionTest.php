<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Permission;

use ExpressionEngine\Service\Permission\Permission;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    public function testHas()
    {
        $permission = new Permission(null, array('can_edit_all_comments' => 'y', 'group_id' => 5), [], [5 => 'Members']);
        $this->assertTrue($permission->has('can_edit_all_comments'));
        $this->assertFalse($permission->has('can_edit_all_the_things'));
    }

    public function testHasSuperAdmin()
    {
        $permission = new Permission(null, array('can_edit_all_comments' => 'y', 'group_id' => 1), [], [1 => 'Super Admin']);
        $this->assertTrue($permission->has('can_edit_all_comments'));
        $this->assertTrue($permission->has('can_edit_all_the_things'));
    }

    public function testHasAny()
    {
        $permission = new Permission(null, array('can_edit_own_comments' => 'y', 'group_id' => 5), [], [5 => 'Members']);
        $this->assertTrue($permission->hasAny('can_edit_own_comments', 'can_edit_all_the_things'));
        $this->assertFalse($permission->hasAny('can_edit_all_the_things'));
    }

    public function testHasAnySuperAdmin()
    {
        $permission = new Permission(null, array('can_edit_own_comments' => 'y', 'group_id' => 1), [], [1 => 'Super Admin']);
        $this->assertTrue($permission->hasAny('can_edit_own_comments', 'can_edit_all_the_things'));
        $this->assertTrue($permission->hasAny('can_edit_all_the_things'));
    }

    public function testHasAll()
    {
        $permission = new Permission(null, array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 5), [], [5 => 'Members']);
        $this->assertFalse($permission->hasAll('can_edit_own_comments', 'can_edit_all_the_things'));
        $this->assertFalse($permission->hasAll('can_edit_all_the_things'));
    }

    public function testHasAllSuperAdmin()
    {
        $permission = new Permission(null, array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 1), [], [1 => 'Super Admin']);
        $this->assertTrue($permission->hasAll('can_edit_own_comments', 'can_edit_all_the_things'));
        $this->assertTrue($permission->hasAll('can_edit_all_the_things'));
    }

    public function testHasException()
    {
        $this->expectException(\BadFunctionCallException::class);
        $permission = new Permission(null, array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 5), [], [5 => 'Members']);
        $permission->has('can_edit_own_comments', 'can_edit_all_the_things');
    }

    public function testHasAnyException()
    {
        $this->expectException(\BadFunctionCallException::class);
        $permission = new Permission(null, array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 5), [], [5 => 'Members']);
        $permission->hasAny();
    }

    public function testHasAllException()
    {
        $this->expectException(\BadFunctionCallException::class);
        $permission = new Permission(null, array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 5), [], [5 => 'Members']);
        $permission->hasAll();
    }
}
