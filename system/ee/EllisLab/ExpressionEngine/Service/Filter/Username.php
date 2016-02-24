<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Username extends Filter {

	/**
	 * @var Builder A Query Builder object for use in fetching usernames
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
	public function setQuery(Builder $builder)
	{
		$this->builder = $builder;

		// Do not overwrite any provided/set usernames and only fetch and
		// display members if there are 25 or less
		if ( ! empty($this->options) || $builder->count() > 25)
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
	 * @return int[]|NULL The value of the filter (NULL if it has no value)
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
					$this->display_value = $value;
					$members = $this->builder->filter('username', 'LIKE', '%' . $value . '%')->all();
					if ($members->count() > 0)
					{
						$this->selected_value = $members->pluck('member_id');
					}
					else
					{
						$this->selected_value = array(-1);
					}
				}
			}
		}

		$value = parent::value();
		if ( ! is_array($value))
		{
			// Return NULL if it has no value
			if (is_null($value))
			{
				return NULL;
			}

			$value = array($value);
		}
		return $value;
	}

	/**
	 * Validation
	 *   Without a Query/Builder object:
	 *     - all ids in $this->value() must be in $this->options to be valid
	 *   With a Query/Builder object:
	 *     - the ids in $this->value() must return something from the builder
	 */
	public function isValid()
	{
		// A no value filter is still valid
		if (is_null($this->value()))
		{
			return TRUE;
		}

		// No Query Builder
		if (is_null($this->builder))
		{
			foreach ($this->value() as $value)
			{
				if ( ! array_key_exists($value, $this->options))
				{
					return FALSE;
				}
			}

			return TRUE;
		}
		else
		{
			// If we have a query builder and have less than 26 members, don't
			// bother hitting the DB for validity
			if ( ! empty($this->options))
			{
				foreach ($this->value() as $value)
				{
					if ( ! array_key_exists($value, $this->options))
					{
						return FALSE;
					}
				}

				return TRUE;
			}

			$members = $this->builder->filter('member_id', 'IN', $this->value())->all();

			return ($members->count() > 0);
		}

		return FALSE;
	}

	/**
	 * This renders the filter into HTML.
	 *
	 * @uses ViewFactory::make to create a View instance
	 * @uses \EllisLab\ExpressionEngine\Service\View\View::render to generate HTML
	 *
	 * @param ViewFactory $view A view factory responsible for making a view
	 * @param URL $url A URL object for use in generating URLs for the filter
	 *   options
	 * @return string Returns HTML
	 */
	public function render(ViewFactory $view, URL $url)
	{
		$options = $this->prepareOptions($url);

		if (empty($options))
		{
			return;
		}

		$value = $this->display_value;
		if (is_null($value))
		{
			$value = $this->value();
			$value = $value[0];

			$value = (array_key_exists($value, $this->options)) ?
				$this->options[$value] :
				$value;
		}

		$filter = array(
			'label'            => $this->label,
			'name'             => $this->name,
			'value'            => $value,
			'has_custom_value' => $this->has_custom_value,
			'has_list_filter'  => $this->has_list_filter,
			'custom_value'     => (array_key_exists($this->name, $_POST)) ? $_POST[$this->name] : FALSE,
			'placeholder'      => $this->placeholder,
			'options'          => $options,
		);
		return $view->make('_shared/filters/filter')->render($filter);
	}

}

// EOF
