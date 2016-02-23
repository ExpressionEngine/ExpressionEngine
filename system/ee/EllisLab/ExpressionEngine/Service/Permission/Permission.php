<?php
namespace EllisLab\ExpressionEngine\Service\Permission;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Permission Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Permission {

	/**
	 * @var array $userdata An array of the session userdata
	 */
	protected $userdata;

	/**
	 * Constructor: sets the userdata.
	 *
	 * @param array $userdata The session userdata array
	 */
	public function __construct(array $userdata = array())
	{
		$this->userdata = $userdata;
	}

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

		$which = func_get_args();

		if ( ! count($which))
		{
			throw new \BadMethodCallException('Invalid parameter count, 1 or more arguments required.');
		}

		// Super Admins always have access
		if ($this->getUserdatum('group_id') == 1)
		{
			return TRUE;
		}

		foreach ($which as $w)
		{
			$k = $this->getUserdatum($w);

			if ( ! $k OR $k !== 'y')
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

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
		$which = func_get_args();

		if ( ! count($which))
		{
			throw new \BadMethodCallException('Invalid parameter count, 1 or more arguments required.');
		}

		// Super Admins always have access
		if ($this->getUserdatum('group_id') == 1)
		{
			return TRUE;
		}

		$result = FALSE;

		foreach ($which as $w)
		{
			$k = $this->getUserdatum($w);

			if ($k === TRUE OR $k == 'y')
			{
				return TRUE;
			}
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Get user datum
	 *
	 * Member access validation
	 *
	 * @param	string  any number of permission names
	 * @return	mixed    False if the requested userdata array key doesn't exist
	 *							otherwise returns the key's value
	 */
	protected function getUserdatum($which)
	{
		return ( ! isset($this->userdata[$which])) ? FALSE : $this->userdata[$which];
	}

}
// EOF
