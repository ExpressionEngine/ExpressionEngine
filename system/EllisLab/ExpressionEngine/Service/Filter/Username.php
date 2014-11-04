<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Service\Model\Query\Query;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Perpage Filter Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Username extends Filter {

	protected $builder;

	public function __construct($usernames = array())
	{
		$this->name = 'filter_by_username';
		$this->label = 'username';
		$this->placeholder = lang('filter_by_username');
		$this->options = $usernames;
	}

	public function setQuery(Query $builder)
	{
		$this->builder = $builder;

		// We will only display members if there are 25 or less
		if ($builder->count() > 25)
		{
			return;
		}

		$members = $builder->all();
		if ($members)
		{
			$options = array();

			foreach ($members as $member)
			{
				$options[$member->member_id] = $member->username;
			}

			$this->options = $options;
		}
	}

	public function value()
	{
		if (isset($this->builder))
		{
			$value = (isset($_POST[$this->name])) ? $_POST[$this->name] : NULL;
			if ($value)
			{
				if ( ! is_numeric($value))
				{
					$member = $this->builder->filter('username', $value)->first();
					if ($member)
					{
						$this->selected_value = $member->member_id;
					}
				}
			}
		}

		return parent::value();
	}

	public function isValid()
	{
		$value = $this->value();

		if (is_numeric($value) && $value > 0)
		{
			return TRUE;
		}

		return FALSE;
	}

}
// END CLASS

/* End of file Username.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/Filter/Username.php */