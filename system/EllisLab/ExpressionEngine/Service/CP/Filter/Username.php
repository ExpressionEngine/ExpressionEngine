<?php
namespace EllisLab\ExpressionEngine\Service\CP\Filter;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

	public function __construct()
	{
		$this->name = 'filter_by_username';
		$this->label = 'username';
		$this->placeholder = lang('filter_by_username');

		$members = ee()->api->get('Member')->all();
		if ($members)
		{
			$value = $this->value();
			if ($value)
			{
				if (is_numeric($value))
				{
					$member = ee()->api->get('Member', $value)->first();
					if ($member)
					{
						// $this->display_value = $member->username;
					}
				}
				else
				{
					$member = ee()->api->get('Member')->filter('username', $value)->first();
					if ($member)
					{
						// $this->display_value = $value;
						$this->selected_value = $member->member_id;
					}
				}
			}

			$options = array();

			foreach ($members as $member)
			{
				$options[$member->member_id] = $member->username;
			}

			$this->options = $options;
		}
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
/* Location: ./system/EllisLab/ExpressionEngine/Service/CP/Filter/Username.php */