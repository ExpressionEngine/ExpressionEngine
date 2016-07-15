<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2016, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Router Class
 *
 * Parses URIs and determines routing
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @author		EllisLab Dev Team
 * @category	Libraries
 * @link		http://codeigniter.com/user_guide/general/routing.html
 */
class EE_Router {

	var $config;
	var $routes			= array();
	var $class			= '';
	var $method			= 'index';
	var $directory		= '';
	var $default_controller;
	var $namespace_prefix = '';

	/**
	 * Constructor
	 *
	 * Runs the route mapping function.
	 */
	function __construct()
	{
		$this->config = load_class('Config', 'core');
		$this->uri = load_class('URI', 'core');
		log_message('debug', "Router Class Initialized");
	}

	// --------------------------------------------------------------------

	/**
	 * Set the route mapping
	 *
	 * This function determines what should be served based on the URI request,
	 * as well as any "routes" that have been set in the routing config file.
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_routing()
	{
		// Are query strings enabled in the config file?  Normally CI doesn't utilize query strings
		// since URI segments are more search-engine friendly, but they can optionally be used.
		// If this feature is enabled, we will gather the directory/class/method a little differently
		$segments = array();
		if ($this->config->item('enable_query_strings') === TRUE AND isset($_GET[$this->config->item('controller_trigger')]))
		{
			if (isset($_GET[$this->config->item('directory_trigger')]))
			{
				$this->set_directory(trim($this->uri->_filter_uri($_GET[$this->config->item('directory_trigger')])));
				$segments[] = rtrim($this->fetch_directory(), '/');
			}

			if (isset($_GET[$this->config->item('controller_trigger')]))
			{
				$this->set_class(trim($this->uri->_filter_uri($_GET[$this->config->item('controller_trigger')])));
				$segments[] = $this->fetch_class();
			}

			if (isset($_GET[$this->config->item('function_trigger')]))
			{
				$this->set_method(trim($this->uri->_filter_uri($_GET[$this->config->item('function_trigger')])));
				$segments[] = $this->fetch_method();
			}
		}

		// Load the routes.php file.
		if (defined('EE_APPPATH'))
		{
			require_once APPPATH.'config/routes.php';
		}
		else
		{
			$route = $this->config->loadFile('routes');
		}

		$this->routes = ( ! isset($route) OR ! is_array($route)) ? array() : $route;
		unset($route);

		// Set the default controller so we can display it in the event
		// the URI doesn't correlated to a valid controller.
		$this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);

		// Were there any query string segments?  If so, we'll validate them and bail out since we're done.
		if (count($segments) > 0)
		{
			return $this->_validate_request($segments);
		}

		// Fetch the complete URI string
		$this->uri->_fetch_uri_string();

		// Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
		if ($this->uri->uri_string == '')
		{
			return $this->_set_default_controller();
		}

		// Do we need to remove the URL suffix?
		$this->uri->_remove_url_suffix();

		// Compile the segments into an array
		$this->uri->_explode_segments();

		// Parse any custom routing that may exist
		$this->_parse_routes();

		// Re-index the segment array so that it starts with 1 rather than 0
		$this->uri->_reindex_segments();
	}

	// --------------------------------------------------------------------

	/**
	 * Set the default controller
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_default_controller()
	{
		if ($this->default_controller === FALSE)
		{
			show_error("Unable to determine what should be displayed. A default route has not been specified in the routing file.");
		}

		// Is the method being specified?
		if (strpos($this->default_controller, '/') !== FALSE)
		{
			$x = explode('/', $this->default_controller);

			$this->set_class($x[0]);
			$this->set_method($x[1]);
			$this->_set_request($x);
		}
		else
		{
			$this->set_class($this->default_controller);
			$this->set_method('index');
			$this->_set_request(array($this->default_controller, 'index'));
		}

		// re-index the routed segments array so it starts with 1 rather than 0
		$this->uri->_reindex_segments();

		log_message('debug', "No URI present. Default controller set.");
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Route
	 *
	 * This function takes an array of URI segments as
	 * input, and sets the current class/method
	 *
	 * @access	private
	 * @param	array
	 * @param	bool
	 * @return	void
	 */
	function _set_request($segments = array())
	{
		$segments = $this->_validate_request($segments);

		if (count($segments) == 0)
		{
			return $this->_set_default_controller();
		}

		$this->set_class($segments[0]);

		if (isset($segments[1]))
		{
			// A standard method request
			$this->set_method($segments[1]);
		}
		else
		{
			// This lets the "routed" segment array identify that the default
			// index method is being used.
			$segments[1] = 'index';
		}

		// Update our "routed" segment array to contain the segments.
		// Note: If there is no custom routing, this array will be
		// identical to $this->uri->segments
		$this->uri->rsegments = $segments;
	}

	// --------------------------------------------------------------------

	/**
	 * Validates the supplied segments.  Attempts to determine the path to
	 * the controller.
	 *
	 * @access	private
	 * @param	array
	 * @param	boolean		$show_404	Set to FALSE to bypass the override
	 * @return	array
	 */
	function _validate_request($segments, $override = TRUE)
	{
		if (count($segments) == 0)
		{
			return $segments;
		}

		$last = end($segments);
		reset($segments);

		$c = 0;

		// First check for a namespaced situation
		$saved_segments = $segments;
		$directory = APPPATH.'../EllisLab/ExpressionEngine/Controller/';
		$namespace = '';
		if (strtolower($segments[0]) == 'cp')
		{
			array_shift($segments); // This will not factor into the path for namespaced stuff
			$c++;
		}

		while ($c < count($saved_segments))
		{
			$segment = str_replace('-', '_', $segments[0]);
			$words = explode('_', $segment);
			$words = array_map('ucfirst', $words);
			$segment = implode('', $words);

			// Do we have a directory instead of a controller file?
			if ( ! file_exists($directory . $segment . '.php') && is_dir($directory . $segment))
			{
				$directory .= $segment . '/';
				$namespace .= '\\' . $segment;
				array_shift($segments);
				$c++;
				continue;
			}

			// For organization purposes everything is in a subdirectory inside
			// .../Controllers/. Top level controllers may be structured thus:
			// .../Controllers/FooBar/FooBar.php
			// We now check for that eventuality. This is important because
			// the returned array assumes that the string at index 0 is the
			// controller class.
			if ( ! file_exists($directory . $segment . '.php'))
			{
				if ($c > 0)
				{
					$segment = str_replace('-', '_', $saved_segments[$c - 1]);
					$words = explode('_', $segment);
					$words = array_map('ucfirst', $words);
					$segment = implode('', $words);

					if (file_exists($directory . $segment . '.php'))
					{
						array_unshift($segments, $saved_segments[$c - 1]);
					}
				}
			}

			break;
		}

		if ($namespace != '')
		{
			$this->set_directory($directory);
			$this->namespace_prefix = '\EllisLab\ExpressionEngine\Controller' . $namespace;

			// If the final segment is a directory check for a file matching the
			// directory's name inside the directory. Use its index method.
			if (empty($segments))
			{
				$segment = str_replace('-', '_', $last);
				$words = explode('_', $segment);
				$words = array_map('ucfirst', $words);
				$segment = implode('', $words);

				if (file_exists($directory . $segment . '.php'))
				{
					return array($last, 'index');
				}
			}
			return $segments;
		}

		$segments = $saved_segments;
		$c = 0;

		// Loop through our segments and return as soon as a controller
		// is found or when such a directory doesn't exist
		while ($c < count($saved_segments))
		{
			$test = $this->directory.str_replace('-', '_', $segments[0]);

			// First lowercase
			if ( ! file_exists(APPPATH.'controllers/'.$test.'.php') && is_dir(APPPATH.'controllers/'.$this->directory.$segments[0]))
			{
				$this->set_directory(array_shift($segments), TRUE);
				$c++;
				continue;
			}

			return $segments;
		}

		// If the final segment is a directory check for a file matching the
		// directory's name inside the directory. Use its index method.
		if (empty($segments))
		{
			if (file_exists(APPPATH.'controllers/'.$this->directory.$last.'.php'))
			{
				return array($last, 'index');
			}
		}

		// If we've gotten this far it means that the URI does not correlate to a valid
		// controller class.  We will now see if there is an override
		if ($override === TRUE && isset($this->routes['404_override']) && $this->routes['404_override'] != '')
		{
			if (strpos($this->routes['404_override'], '/') !== FALSE)
			{
				$x = $this->_validate_request(explode('/', $this->routes['404_override']), FALSE);
				$x[1] = (empty($x[1])) ? 'index' : $x[1];

				$this->set_class($x[0]);
				$this->set_method($x[1]);

				return $x;
			}
		}

		// Nothing else to do at this point but show a 404
		show_error("The requested URL could not be found.", 404);

	}

	// --------------------------------------------------------------------

	/**
	 *  Parse Routes
	 *
	 * This function matches any routes that may exist in
	 * the config/routes.php file against the URI to
	 * determine if the class/method need to be remapped.
	 *
	 * @access	private
	 * @return	void
	 */
	function _parse_routes()
	{
		// Turn the segment array into a URI string
		$uri = implode('/', $this->uri->segments);

		// Is there a literal match?  If so we're done
		if (isset($this->routes[$uri]))
		{
			return $this->_set_request(explode('/', $this->routes[$uri]));
		}

		// Loop through the route array looking for wild-cards
		foreach ($this->routes as $key => $val)
		{
			// Convert wild-cards to RegEx
			$key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));

			// Does the RegEx match?
			if (preg_match('#^'.$key.'$#', $uri))
			{
				// Do we have a back-reference?
				if (strpos($val, '$') !== FALSE AND strpos($key, '(') !== FALSE)
				{
					$val = preg_replace('#^'.$key.'$#', $val, $uri);
				}

				return $this->_set_request(explode('/', $val));
			}
		}

		// If we got this far it means we didn't encounter a
		// matching route so we'll set the site default route
		$this->_set_request($this->uri->segments);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the class name
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_class($class)
	{
		$this->class = str_replace(array('/', '.'), '', $class);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the current class
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_class($prepend_namespace = FALSE)
	{
		$class = str_replace('-', '_', $this->class);

		// If we are in a namespaced controller the class is PascalCased
		if ($prepend_namespace && ! empty($this->namespace_prefix))
		{
			$words = explode('_', $class);
			$words = array_map('ucfirst', $words);
			$class = implode('', $words);
			return $this->namespace_prefix . '\\' . $class;
		}

		return $class;
	}

	// --------------------------------------------------------------------

	/**
	 *  Set the method name
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_method($method)
	{
		$this->method = $method;
	}

	// --------------------------------------------------------------------

	/**
	 *  Fetch the current method
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_method()
	{
		if ($this->method == $this->fetch_class())
		{
			return 'index';
		}

		$method = str_replace('-', '_', $this->method);

		// If we are in a namespaced controller the method is camelCased
		if ( ! empty($this->namespace_prefix))
		{
			$words = explode('_', $method);
			$method = strtolower(array_shift($words));
			$words = array_map('ucfirst', $words);
			$method .= implode('', $words);
		}

		return $method;
	}

	// --------------------------------------------------------------------

	/**
	 *  Set the directory name
	 *
	 * @param	string	$dir	Directory name
	 * @param	bool	$appent Whether we're appending rather then setting the full value
	 * @return	void
	 */
	public function set_directory($dir, $append = FALSE)
	{
		if ($append !== TRUE OR empty($this->directory))
		{
			$this->directory = str_replace('.', '', trim($dir, '/')).'/';
		}
		else
		{
			$this->directory .= str_replace('.', '', trim($dir, '/')).'/';
		}
	}

	// --------------------------------------------------------------------

	/**
	 *  Fetch the sub-directory (if any) that contains the requested controller class
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_directory()
	{
		return $this->directory;
	}

	// --------------------------------------------------------------------

	/**
	 *  Set the controller overrides
	 *
	 * @access	public
	 * @param	array
	 * @return	null
	 */
	function _set_overrides($routing)
	{
		if ( ! is_array($routing))
		{
			return;
		}

		if (isset($routing['directory']))
		{
			$this->set_directory($routing['directory']);
		}

		if (isset($routing['controller']) AND $routing['controller'] != '')
		{
			$this->set_class($routing['controller']);
		}

		if (isset($routing['function']))
		{
			$routing['function'] = ($routing['function'] == '') ? 'index' : $routing['function'];
			$this->set_method($routing['function']);
		}
	}


}
// END Router Class

// EOF
