<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Permission;

/**
 * Permission Service
 */
class Permission {

	/**
	 * @var array $userdata An array of the session userdata
	 */
	protected $userdata;

	/**
	 * @var array $permissions An array of granted permissions
	 */
	protected $permissions;

	protected $roles;

	protected $model_delegate;

	protected $site_id;

	/**
	 * Constructor: sets the userdata.
	 *
	 * @param array $userdata The session userdata array
	 */
	public function __construct($model_delegate, array $userdata = [], array $permissions = [], array $roles = [], $site_id = 1)
	{
		$this->model_delegate = $model_delegate;
		$this->userdata = $userdata;
		$this->permissions = $permissions;
		$this->roles = $roles;
		$this->site_id = $site_id;
	}

	public function rolesThatHave($permission, $site_id = NULL)
	{
		$site_id = ($site_id) ?: $this->site_id;
		$groups = $this->model_delegate->get('Permission')
			->fields('role_id')
			->filter('site_id', $site_id)
			->filter('permission', $permission)
			->all();

		if ($groups)
		{
			return $groups->pluck('role_id');
		}

		return [];
	}

	public function rolesThatCan($permission, $site_id = NULL)
	{
		return $this->rolesThatHave('can_' . $permission, $site_id);
	}

	public function isSuperAdmin()
	{
		return isset($this->roles[1]);
	}

	public function hasRole($role)
	{
		if (is_numeric($role))
		{
			return isset($this->roles[$role]);
		}

		return in_array($role, $this->roles);
	}

	public function hasAnyRole($roles)
	{
		foreach ($roles as $role)
		{
			if ($this->hasRole($role))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Has a single permission
	 *
	 * Member access validation
	 *
	 * @param	string  single permission name
	 * @return	bool    TRUE if member has permission
	 */
	public function has()
	{
		$which = func_get_args();

		if (count($which) !== 1)
		{
			throw new \BadMethodCallException('Invalid parameter count, must have exactly 1.');
		}

		return $this->hasAll($which[0]);
	}

	public function can($which)
	{
		return $this->has('can_' . $which);
	}

	/**
	 * Has All
	 *
	 * Member access validation
	 *
	 * @param	mixed   array or any number of permission names
	 * @return	bool    TRUE if member has all permissions
	 */
	public function hasAll()
	{
		$which = $this->prepareArguments(func_get_args());

		if ( ! count($which))
		{
			throw new \BadMethodCallException('Invalid parameter count, 1 or more arguments required.');
		}

		// Super Admins always have access
		if ($this->isSuperAdmin())
		{
			return TRUE;
		}

		foreach ($which as $w)
		{
			if ( ! $this->check($w))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Has Any
	 *
	 * Member access validation
	 *
	 * @param	mixed   array or any number of permission names
	 * @return	bool    TRUE if member has any permissions in the set
	 */
	public function hasAny()
	{
		$which = $this->prepareArguments(func_get_args());

		if ( ! count($which))
		{
			throw new \BadMethodCallException('Invalid parameter count, 1 or more arguments required.');
		}

		// Super Admins always have access
		if ($this->isSuperAdmin())
		{
			return TRUE;
		}

		foreach ($which as $w)
		{
			if ($this->check($w))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	protected function prepareArguments($which)
	{
		$args = [];

		foreach ($which as $w)
		{
			if (is_array($w))
			{
				$args += $w;
			}
			else
			{
				$args[] = $w;
			}
		}

		return $args;
	}

	/**
	 * Check for the permission first looking in the userdata then in the permission array
	 *
	 * @param string $which any number of permission names
	 * @return bool TRUE if the permission is in the userdata or the permission key exists; FALSE otherwise
	 */
	protected function check($which)
	{
		$k = $this->getUserdatum($which);

		if ($k === TRUE OR $k == 'y')
		{
			return TRUE;
		}

		return array_key_exists($which, $this->permissions);
	}

	/**
	 * Get user datum
	 *
	 * Member access validation
	 *
	 * @param	string $which any number of permission names
	 * @return	mixed    False if the requested userdata array key doesn't exist
	 *							otherwise returns the key's value
	 */
	protected function getUserdatum($which)
	{
		return ( ! isset($this->userdata[$which])) ? FALSE : $this->userdata[$which];
	}

}
// EOF
