<?php

namespace EllisLab\ExpressionEngine\Service\View;

use EllisLab\ExpressionEngine\Core\Provider;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine View Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class View {

	/**
	 * @var str The path to the view template file ex. '_shared/form'
	 */
	protected $path;

	/**
	 * @var EllisLab\ExpressionEngine\Core\Provider
	 */
	protected $provider;

	/**
	 * @var Parent view object, if the view is being extended
	 */
	protected $parent;

	/**
	 * @var Array of blocks in the current parsing pass
	 */
	 protected $blocks = array();

	/**
	 * @var Array of variables in the current parsing pass
	 */
	protected $processing = array();

	/**
	 * @var Array of disabled view features
	 */
	protected $disabled = array();

	/**
	 * @var array An indexed array for storing the names of blocks consumed via
	 * startBlock() and endBlock()
	 */
	private $blockStack;

	/**
	 * Constructor
	 *
	 * @param String   $path    Path to the view file, ex: '_shared/form'
	 * @param Provider $provider Provider for the current context
	 */
	public function __construct($path, Provider $provider)
	{
		$this->path = $path;
		$this->provider = $provider;
	}

	/**
	 * Renders the view
	 *
	 * @param Array $vars An associative array of variables to use inside the
	 *   template. ex: "title" => "Hello World!"
	 * @return String The rendered HTML
	 */
	public function render(array $vars = array())
	{
		$path = $this->getPath();

		$vars['blocks'] = $this->blocks;

		$this->processing = $vars;

		// parse the current view
		$output = $this->parse($path, $vars);

		if ($this->parent)
		{
			$vars['EE_rendered_view'] = $output;

			$output = $this->parent->render($vars);
		}

		$this->processing = array();

		return $output;
	}

	/**
	 * Load a view file, replace variables, and return the result
	 *
	 * @param  String $path Full path to a view file
	 * @param  Array  $vars Variables to replace in the view file
	 * @return String Parsed view file
	 */
	protected function parse($path, $vars)
	{
		$rewrite = (version_compare(PHP_VERSION, '5.4.0') < 0 && @ini_get('short_open_tag') === FALSE);

		extract($vars);

		ob_start();

		if ($rewrite)
		{
			echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($path))));
		}
		else
		{
			include($path);
		}

		$buffer = ob_get_contents();
		ob_end_clean();

		return $buffer;
	}

	/**
	 * Loads, renders, and (optionally) returns a sub-view
	 *
	 * @param String $view The name of the sub-view
	 * @param Array  $vars Additional variables to pass to the sub-view
	 * @param bool  $return Whether to return a string or output the results
	 * @return String The parsed sub-view
	 */
	public function view($view, $vars = array(), $return = FALSE)
	{
		$vars = array_merge($this->processing, $vars);

		$result = $this->makeView($view)->render($vars);

		if ($return === FALSE)
		{
			ob_start();
			echo $result;
			ob_end_flush();
		}

		return $result;
	}

	/**
	 * Loads, renders, and (optionally) returns a sub-view. Basically the same
	 * as `view()`, but automatically forces an ee:prefix.
	 *
	 * @param String $view The name of the sub-view
	 * @param Array  $vars Additional variables to pass to the sub-view
	 * @param bool  $return Whether to return a string or output the results
	 * @return String The parsed sub-view
	 */
	public function ee_view($view, $vars = array(), $return = FALSE)
	{
		return $this->view('ee:'.$view, $vars, $return);
	}

	/**
	 * Extend the current view with a parent view
	 *
	 * @param  String $which   Parent view
	 * @param  array  $disable Items to disable in the parent view
	 * @return void
	 */
	public function extend($which, $disable = array())
	{
		$this->parent = $this->makeView($which)->disable($disable);
	}

	/**
	 * Disable a view feature
	 *
	 * @param  String|Array $which Feature or features to disable
	 * @return $this
	 */
	public function disable($which)
	{
		if ( ! is_array($which))
		{
			$which = array($which);
		}

		while ($el = array_pop($which))
		{
			$this->disabled[] = $el;
		}

		return $this;
	}

	/**
	 * Check if a view element or feature is disabled
	 *
	 * @param  String $which Name of a view element/feature
	 * @return bool Is disabled?
	 */
	public function disabled($which)
	{
		return in_array($which, $this->disabled);
	}

	/**
	 * Check if a view element or feature is enabled
	 *
	 * @param  String $which Name of a view element/feature
	 * @return bool Is enabled?
	 */
	public function enabled($which)
	{
		return ! $this->disabled($which);
	}

	/**
	 * Allows our Views to define blocks to be used in a template/layout provider.
	 * This will start a new block overwriting any previously defined block of
	 * the same name.
	 *
	 * @param str $name The name of the block
	 */
	public function startBlock($name)
	{
		$this->blockStack[] = array($name, FALSE);
		ob_start();
	}

	/**
	 * Allows our Views to define blocks to be used in a template/layout provider
	 * This will start a new block or append to a previously defined block of
	 * the same name.
	 *
	 * @param str $name The name of the block
	 */
	public function startOrAppendBlock($name)
	{
		$this->blockStack[] = array($name, TRUE);
		ob_start();
	}

	/**
	 * Ends the block storing the buffer on the View::blocks array based on the
	 * most recently specified name via startBlock.
	 */
	public function endBlock()
	{
		list($name, $append) = array_pop($this->blockStack);

		if ($name === NULL)
		{
			throw new \Exception('View: Attempted to end block without opening');
		}

		$buffer = '';

		if ($append && isset($this->blocks[$name]))
		{
			$buffer .= $this->blocks[$name];
		}

		$buffer .= ob_get_contents();
		ob_end_clean();

		$this->blocks[$name] = $buffer;
	}

	/**
	 * Create a new view object. Change to the requested provider scope
	 * if required to speed up new sub-views.
	 *
	 * It's tempting to pass the factory to this class or to grab a factory
	 * instance from the provider to avoid the duplication with the factory's
	 * `make()` method, but you still end up having to compare prefixes to ensure
	 * subviews are correctly scoped. Which is to say: it's not worth the law
	 * of demeter violation to get this DRY as it will end up being the same
	 * length again.
	 *
	 * If anything the solution will likely come from a change to the providers.
	 *
	 * @param  String $view Subview name, potentially with prefix
	 * @return View         The subview instance
	 */
	protected function makeView($view)
	{
		$provider = $this->provider;

		if (strpos($view, ':'))
		{
			list($prefix, $view) = explode(':', $view, 2);

			if ($provider->getPrefix() != $prefix)
			{
				$provider = $provider->make('App')->get($prefix);
			}
		}

		return new static($view, $provider);
	}

	/**
	 * Get the full server path to the view file backing this
	 * view object.
	 *
	 * @return String The full server path
	 */
	protected function getPath()
	{
		$path = $this->provider->getPath().'/';
		$view_path = $this->provider->get('views');

		$path .= $view_path ?: 'views';

		return $path.'/'.$this->path.'.php';
	}
}
// EOF
