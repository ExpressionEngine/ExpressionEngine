<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

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
 * ExpressionEngine Username Filter Class
 *
 * This will provide the HTML for a filter that will list a set of usernames,
 * but only if there are 25 or less. If there are more then only a <input>
 * element will provided allowing for searching based on username.
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Username extends Filter {

	/**
	 * @var Query A Query Builder object for use in fetching usernames
	 */
	protected $builder;

	/**
	 * Construtor
	 *
	 * @param array $usernames An associative array of usernames to use for the
	 *   filter where the key is the User ID and the value is the Username. i.e.
	 *     '1' => 'admin',
	 *     '2' => 'johndoe'
	 * @return void
	 */
	public function __construct($usernames = array())
	{
		$this->name = 'filter_by_username';
		$this->label = 'username';
		$this->placeholder = lang('filter_by_username');
		$this->options = $usernames;
	}

	/**
	 * Sets the Query Builder property and builds a username list assuming
	 * there are no more than 25 users available and assuming no usernames
	 * were provided in the constructor.
	 *
	 * @param Query $builder A Query Builder object
	 * @return void
	 */
	public function setQuery(Query $builder)
	{
		$this->builder = $builder;

		// We will only display members if there are 25 or less
		if ( ! empty($this->usernames) || $builder->count() > 25)
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

	/**
	 * @see Filter::value For the parent behavior
	 *
	 * Overriding the value method to account for someone searching for a
	 * username, in which case we will use a $builder object (if provided)
	 * to resolve that username to an ID.
	 *
	 * @return mixed The value of the filter
	 */
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

	/**
	 * Validation: we should have a number representing the user id. If not,
	 * this is invalid. If we have a builder object then we will query for
	 * the user, and if not found it is invalid. Otherwise, this is valid.
	 */
	public function isValid()
	{
		$value = $this->value();

		if (is_numeric($value) && $value > 0)
		{
			if (isset($this->builder))
			{
				$member = $this->builder->filter('Member', $value)->first();
				if ( ! $member)
				{
					return FALSE;
				}
			}
			return TRUE;
		}

		return FALSE;
	}

}
// EOF