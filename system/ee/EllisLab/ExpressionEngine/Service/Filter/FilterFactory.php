<?php
namespace EllisLab\ExpressionEngine\Service\Filter;


use EllisLab\ExpressionEngine\Service\Filter;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;
use EllisLab\ExpressionEngine\Service\Dependency\ServiceProvider;
use EllisLab\ExpressionEngine\Library\CP\URL;

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
 * ExpressionEngine FilterFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class FilterFactory {

	/**
	 * @var InjectionContainer A referrence to a InjectionContainer
	 */
	protected $container;

	/**
	 * @var Filter\Filter[] Our collection of filters
	 */
	protected $filters = array();

	/**
	 * Constructs the FilterFactory. It requires a ViewFactory instance since
	 * Filters will need a View in order to render (see: render()).
	 *
	 * @param ViewFactory $view The ViewFactory to use for this FilterFactory
	 * @return void
	 */
	public function __construct(ViewFactory $view)
	{
		$this->view = $view;
	}

	/**
	 * Sets the InjectionContainer for the Factory
	 *
	 * @param InjectionContainer $container The container to use
	 * @return self This returns a reference to itself
	 */
	public function setDIContainer(ServiceProvider $container)
	{
		$this->container = $container;
		return $this;
	}

	/**
	 * Instantiates and returns a new Custom Filter object. This is especially
	 * useful for one-off Filters.
	 *
	 * @see Filter\Filter::options For the format of the $options array
	 *
	 * @param string $name    The name="" attribute for this filter
	 * @param string $label   A language key to be used for the display label
	 * @param array  $options An associative array to use to build the option
	 *                        list.
	 * @return Filter\Custom  Returns a Custom Filter object.
	 */
	public function make($name, $label, array $options)
	{
		return new Filter\Custom($name, $label, $options);
	}

	/**
	 * This will add a filter to the $filters array. It will also instantiate
	 * a new named filter either via a local `createDefault{$name}()` method
	 * or a bound method on the InjectionContainer.
	 *
	 * @param Filter\Filter|string $filter If a Filter object is passed in it
	 *   will be added directly. Otherwise the first argument passed in will be
	 *   used as the name of the filter to instantiate.
	 * @param mixed $filter,... An unlimited optional number of arguments to
	 *   pass to the construction of the $filter
	 * @throws Exception if a named filter cannot be constructed
	 * @return self This returns a reference to itself
	 */
	public function add($filter)
	{
		if ($filter instanceof Filter\Filter)
		{
			$this->filters[] = $filter;
			return $this;
		}

		$args = func_get_args();
		$name = array_shift($args);

		$default = "createDefault{$name}";

		if (method_exists($this, $default))
		{
			$this->filters[] = call_user_func_array(
				array($this, $default),
				$args
			);
		}
		elseif (isset($this->container))
		{
			$this->filters[] = $this->container->make($name, $args);
		}
		else
		{
			throw new \Exception('Unknown filter: ' . $name);
		}

		return $this;
	}

	/**
	 * Renames the last filter to be added
	 *
	 * @param string $name The new name="" attribute for the previous filter
	 * @throws Exception if no filters have been added
	 * @return self This returns a reference to itself
	 */
	public function withName($name)
	{
		if (empty($this->filters))
		{
			throw new \Exception('No filters have been addded. Cannot rename a filter.');
		}

		$filter = end($this->filters);
		$filter->name = $name;
		return $this;
	}

	/**
	 * This will render the filters down to HTML by looping through all the
	 * Filters and calling their individual render() methods.
	 *
	 * @param URL $base_url A URL object reference to use when constructing URLs
	 * @return string Returns HTML
	 */
	public function render(URL $base_url)
	{
		$url = clone $base_url;
		$url->addQueryStringVariables($this->values());

		$filters = array();

		foreach ($this->filters as $filter)
		{
			$html = $filter->render($this->view, $url);
			if ( ! empty($html))
			{
				$filters[] = $html;
			}
		}

		$vars = array(
			'filters' => $filters,
			'has_reset' => $this->canReset(),
			'reset_url' => $base_url
		);

		return $this->view->make('_shared/filters/filters')->render($vars);
	}

	/**
	 * Checks to see if we can offer a reset filter action.
	 *
	 * @return bool TRUE if any filter can be reset; FALSE otherwise
	 */
	public function canReset()
	{
		foreach ($this->filters as $filter)
		{
			if ($filter->canReset())
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * This will grab all the values from the Filters by looping through them
	 * and calling their individual value() methods.
	 *
	 * @return array Returns an associative array of the values where the key
	 *               is the filter's name and the value is the value. i.e.
	 *                 'filter_by_site' => 3,
	 *                 'perpage' => 50
	 */
	public function values()
	{
		$values = array();

		foreach ($this->filters as $filter)
		{
			$values[$filter->name] = $filter->value();
		}

		return $values;
	}

	/**
	 * This will instantiate and return a default Date filter
	 *
	 * @return Filter\Date a Date Filter object
	 */
	protected function createDefaultDate()
	{
		return new Filter\Date();
	}

	/**
	 * This will instantiate and return a default Site filter
	 *
	 * @todo Use the $container to make Config->item
	 * @todo Use the $container to make Session->userdata
	 *
	 * @return Filter\Site a Site Filter object
	 */
	protected function createDefaultSite()
	{
		$msmEnabled = (ee()->config->item('multiple_sites_enabled') == 'y') ? TRUE : FALSE;
		$sites = array();
		if ($msmEnabled)
		{
			$sites = ee()->session->userdata('assigned_sites');
		}

		$filter = new Filter\Site($sites);

		if ($msmEnabled)
		{
			$filter->enableMSM();
		}

		return $filter;
	}

	/**
	 * This will instantiate and return a default Perpage filter
	 *
	 * @param  int $total The total number of items available
	 * @param  string $lang_key The optional lang key to use for the "All
	 *                          <<$total>> items" option
	 * @param  bool $is_modal Is this Perpage filter in/for a modal?
	 * @return Filter\Perpage a Perpage Filter object
	 */
	protected function createDefaultPerpage($total, $lang_key = NULL, $is_modal = FALSE)
	{
		if ( ! isset($lang_key))
		{
			return new Filter\Perpage($total);
		}

		return new Filter\Perpage($total, $lang_key, $is_modal);
	}

	/**
	 * This will instantiate and return a default Username filter
	 *
	 * @todo Figure out what to do when container is set and $usernames are
	 *   passed in.
	 *
	 * @uses InjectionContainer::make to create a Model/Query object
	 * @uses Filter\Username::setQuery to set a Model/Query object in order to
	 *   fetch a list of usernames
	 *
	 * @param array $usernames An associative array of usernames to use for the
	 *   filter where the key is the User ID and the value is the Username. i.e.
	 *     '1' => 'admin',
	 *     '2' => 'johndoe'
	 * @return Filter\Username a Username Filter object
	 */
	protected function createDefaultUsername($usernames = array())
	{
		$filter = new Filter\Username($usernames);

		if (isset($this->container))
		{
			$filter->setQuery($this->container->make('Model')->get('Member')->order('username', 'asc'));
		}

		return $filter;
	}

}

// EOF
