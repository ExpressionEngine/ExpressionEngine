<?php

namespace EllisLab\ExpressionEngine\Service\Addon;

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
 * ExpressionEngine Addon Class
 *
 * @package		ExpressionEngine
 * @subpackage	Filesystem
 * @category	Library
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Addon {

	protected $provider;
	protected $basepath;
	protected $shortname;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
		$this->shortname = $provider->getPrefix();
	}

	/**
	 * Pass unknown calls to the provider
	 */
	public function __call($fn, $args)
	{
		return call_user_func_array(array($this->provider, $fn), $args);
	}

	/**
	 * Is this addon installed?
	 *
	 * @return bool Is installed?
	 */
	public function isInstalled()
	{
		$types = array('modules', 'fieldtypes', 'extensions');

		ee()->load->library('addons');

		foreach ($types as $type)
		{
			$installed = ee()->addons->get_installed($type);

			if (array_key_exists($this->shortname, $installed))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Get the plugin or module class
	 */
	public function getFrontendClass()
	{
		if ($this->hasModule())
		{
			return $this->getModuleClass();
		}

		return $this->getPluginClass();
	}

	/**
	 * Get the module class
	 */
	public function getModuleClass()
	{
		$this->requireFile('mod');

		$class = ucfirst($this->shortname);

		return $this->getFullyQualified($class);
	}

	/**
	 * Get the plugin class
	 */
	public function getPluginClass()
	{
		$this->requireFile('pi');

		$class = ucfirst($this->shortname);

		return $this->getFullyQualified($class);
	}

	/**
	 * Get the *_upd class
	 */
	public function getInstallerClass()
	{
		$this->requireFile('upd');

		$class = ucfirst($this->shortname).'_upd';

		return $this->getFullyQualified($class);
	}

	/**
	 * Get the *_mcp class
	 */
	public function getControlPanelClass()
	{
		$this->requireFile('mcp');

		$class = ucfirst($this->shortname).'_mcp';

		return $this->getFullyQualified($class);
	}

	/**
	 * Get the extension class
	 */
	public function getExtensionClass()
	{
		$this->requireFile('ext');

		$class = ucfirst($this->shortname).'_ext';

		return $this->getFullyQualified($class);
	}

	/**
	 * Get the fieldtype class
	 */
	public function getFieldtypeClass()
	{
		$this->requireFile('ft');

		$class = ucfirst($this->shortname).'_ft';

		return $this->getFullyQualified($class);
	}

	/**
	 * Has a module or plugin?
	 */
	public function hasFrontend()
	{
		return $this->hasModule() || $this->hasPlugin();
	}

	/**
	 * Has a upd.* file??
	 */
	public function hasInstaller()
	{
		return $this->hasFile('upd');
	}

	/**
	 * Has an mcp.* file?
	 */
	public function hasControlPanel()
	{
		return $this->hasFile('mcp');
	}

	/**
	 * Has a mod.* file?
	 */
	public function hasModule()
	{
		return $this->hasFile('mod');
	}

	/**
	 * Has a pi.* file?
	 */
	public function hasPlugin()
	{
		return $this->hasFile('pi');
	}

	/**
	 * Has an ext.* file?
	 */
	public function hasExtension()
	{
		return $this->hasFile('ext');
	}

	/**
	 * Has a ft.* file?
	 */
	public function hasFieldtype()
	{
		return $this->hasFile('ft');
	}

	/**
	 * Get the addon Provider
	 *
	 * @return EllisLab\ExpressionEngine\Core\Provider
	 */
	 public function getProvider()
	 {
		 return $this->provider;
	 }

	/**
	 * Get the fully qualified class name
	 *
	 * Checks the namespace and if that doesn't exists falls back to the
	 * old name
	 *
	 * @param String $class The classname relative to their namespace
	 * @return The fqcn or $class
	 */
	protected function getFullyQualified($class)
	{
		$ns = trim($this->provider->getNamespace(), '\\');

		$ns_class = "\\{$ns}\\{$class}";

		if (class_exists($ns_class))
		{
			return $ns_class;
		}

		return $class;
	}

	/**
	 * Check if the file with a given prefix exists
	 */
	protected function hasFile($prefix)
	{
		return file_exists($this->getPath()."/{$prefix}.".$this->getPrefix().'.php');
	}

	/**
	 * Call require on a given file
	 */
	protected function requireFile($prefix)
	{
		require_once $this->getPath()."/{$prefix}.".$this->getPrefix().'.php';
	}
}
