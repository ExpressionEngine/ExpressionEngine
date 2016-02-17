<?php

namespace EllisLab\ExpressionEngine\Service\Theme;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.1.3
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Theme Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Theme {

	/**
	 * @var string The path to the 'themes/ee' directory
	 */
	protected $ee_theme_path;

	/**
	 * @var string The URL to the 'themes/ee' directory
	 */
	protected $ee_theme_url;

	/**
	 * @var string The path to the 'themes/user' directory
	 */
	protected $user_theme_path;

	/**
	 * @var string The URL to the 'themes/user' directory
	 */
	protected $user_theme_url;

	/**
	 * Constructor: sets the ee and user theme path and URL properties
	 *
	 * @param string $ee_theme_path The path to the 'themes/ee' directory
	 * @param string $ee_theme_url The URL to the 'themes/ee' directory
	 * @param string $user_theme_path The path to the 'themes/user' directory
	 * @param string $user_theme_url The URL to the 'themes/user' directory
	 */
	public function __construct($ee_theme_path, $ee_theme_url, $user_theme_path, $user_theme_url)
	{
		$this->ee_theme_path = $ee_theme_path;
		$this->ee_theme_url = $ee_theme_url;
		$this->user_theme_path = $user_theme_path;
		$this->user_theme_url = $user_theme_url;
	}

	public function getPath($path)
	{
		if (file_exists($this->user_theme_path . $path))
		{
			return $this->user_theme_path . $path;
		}

		return $this->ee_theme_path . $path;
	}

	public function getUrl($url)
	{
		if (file_exists($this->user_theme_url . $url))
		{
			return $this->user_theme_url . $url;
		}

		return $this->ee_theme_url . $url;
	}

	public function listThemes($kind)
	{
		// EE first so the User based themes can override.
		return array_merge(
			$this->listDirectory($this->ee_theme_path . $kind . '/'),
			$this->listDirectory($this->user_theme_path . $kind . '/')
		);
	}

	protected function listDirectory($path)
	{
		$files = array();

		if ( ! $fp = @opendir($path))
		{
			return $files;
		}

		while (FALSE !== ($folder = readdir($fp)))
		{
			if (@is_dir($path . $folder) && substr($folder, 0, 1) != '.')
			{
				$files[$folder] = ucwords(str_replace("_", " ", $folder));
			}
		}

		closedir($fp);
		ksort($files);

		return $files;
	}

}
// EOF
