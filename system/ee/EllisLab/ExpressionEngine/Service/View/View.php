<?php

namespace EllisLab\ExpressionEngine\Service\View;

use EE_Loader;
use View as LegacyView;

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
	 * @var obj An instance of EE_Loader
	 */
	protected $loader;

	/**
	 * @var obj An instance of View
	 */
	protected $view;

	/**
	 * @var array An indexed array for storing the names of blocks consumed via
	 * startBlock() and endBlock()
	 */
	private $blockStack;

	/**
	 * Constructor: sets depdencies
	 *
	 * @param str $path The path to the view template file
	 * @param obj $loader An instance of EE_Loader
	 * @param obj $view An instnace of View
	 * @return void
	 */
	public function __construct($path, EE_Loader $loader, LegacyView $view)
	{
		$this->path = $path;
		$this->loader = $loader;
		$this->view = $view;
	}

	/**
	 * Loads a template file from disk using the supplied variables and returns
	 * the rendered HTML
	 *
	 * @param str   $path The absolute path to the view template file to load
	 * @param array $vars An associative array of variables to use inside the
	 *   template. ex: "title" => "Hello World!"
	 * @param bool  $rewrite Do we need to rewrite the template file to replace
	 *   PHP's short tags "<?=" with "<?php echo "?
	 * @return str The rendered HTML
	 */
	public function parse($path, $vars, $rewrite = FALSE)
	{
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
	 * Renders a view template file
	 * @see Loader::view()
	 *
	 * @param array $vars An associative array of variables to use inside the
	 *   template. ex: "title" => "Hello World!"
	 * @return str The rendered HTML
	 */
	public function render(array $vars)
	{
		return $this->loader->view($this->path, $vars, TRUE);
	}

	/**
	 * Loads, renders, and returns a view relative to EE's view path
	 * @see EE_Loader::ee_view()
	 *
	 * @param $view The relative path/name of the view ex: '_shared/form'
	 * @param array $vars An associative array of variables to use inside the
	 *   template. ex: "title" => "Hello World!"
	 * @param bool  $return Whether to return or output the results
	 * @return str|null Either HTML or nothing depending on $return
	 */
	public function ee_view($view, $vars = array(), $return = FALSE)
	{
		return $this->loader->ee_view($view, $vars, $return);
	}

	/**
	 * Loads, renders, and returns a view. The view in question may be relative
	 * to an add-on rather than to EE itself.
	 * @see Loader::view()
	 *
	 * @param $view The relative path/name of the view ex: '_shared/form'
	 * @param array $vars An associative array of variables to use inside the
	 *   template. ex: "title" => "Hello World!"
	 * @param bool  $return Whether to return or output the results
	 * @return str|null Either HTML or nothing depending on $return
	 */
	public function view($view, $vars = array(), $return = FALSE)
	{
		return $this->loader->view($view, $vars, $return);
	}

	/**
	 * Allows our Views to define blocks to be used in a template/layout context.
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
	 * Allows our Views to define blocks to be used in a template/layout context
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
			return; // @TODO Throw an error?
		}

		$buffer = '';

		if ($append && isset($this->view->blocks[$name]))
		{
			$buffer .= $this->view->blocks[$name];
		}

		$buffer .= ob_get_contents();
		ob_end_clean();

		$this->view->blocks[$name] = $buffer;
	}
}
// EOF