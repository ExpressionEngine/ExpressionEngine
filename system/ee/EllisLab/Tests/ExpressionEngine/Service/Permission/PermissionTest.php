<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Permission;

use EllisLab\ExpressionEngine\Service\Permission\Permission;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase {

	public function testHas()
	{
		$permission = new Permission(array('can_edit_all_comments' => 'y', 'group_id' => 5));
		$this->assertTrue($permission->has('can_edit_all_comments'));
		$this->assertFalse($permission->has('can_edit_all_the_things'));
	}

	public function testHasSuperAdmin()
	{
		$permission = new Permission(array('can_edit_all_comments' => 'y', 'group_id' => 1));
		$this->assertTrue($permission->has('can_edit_all_comments'));
		$this->assertTrue($permission->has('can_edit_all_the_things'));
	}

	public function testHasAny()
	{
		$permission = new Permission(array('can_edit_own_comments' => 'y', 'group_id' => 5));
		$this->assertTrue($permission->hasAny('can_edit_own_comments', 'can_edit_all_the_things'));
		$this->assertFalse($permission->hasAny('can_edit_all_the_things'));
	}

	public function testHasAnySuperAdmin()
	{
		$permission = new Permission(array('can_edit_own_comments' => 'y', 'group_id' => 1));
		$this->assertTrue($permission->hasAny('can_edit_own_comments', 'can_edit_all_the_things'));
		$this->assertTrue($permission->hasAny('can_edit_all_the_things'));
	}


	public function testHasAll()
	{
		$permission = new Permission(array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 5));
		$this->assertFalse($permission->hasAll('can_edit_own_comments', 'can_edit_all_the_things'));
		$this->assertFalse($permission->hasAll('can_edit_all_the_things'));
	}

	public function testHasAllSuperAdmin()
	{
		$permission = new Permission(array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 1));
		$this->assertTrue($permission->hasAll('can_edit_own_comments', 'can_edit_all_the_things'));
		$this->assertTrue($permission->hasAll('can_edit_all_the_things'));
	}

	/**
     * @expectedException BadFunctionCallException
     */
	public function testHasException()
	{
		$permission = new Permission(array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 1));
		$permission->has('can_edit_own_comments', 'can_edit_all_the_things');
	}

	/**
     * @expectedException BadFunctionCallException
     */
	public function testHasAnyException()
	{
	$permission = new Permission(array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 1));
		$permission->hasAny();
	}


	/**
     * @expectedException BadFunctionCallException
     */
	public function testHasAllException()
	{
		$permission = new Permission(array('can_edit_all_comments' => 'n', 'can_edit_own_comments' => 'y', 'group_id' => 1));
		$permission->hasAll();
	}

}
