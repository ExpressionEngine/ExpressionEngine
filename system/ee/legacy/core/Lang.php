<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Language Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Lang {

	var $language	= array();
	var $is_loaded	= array();


	/**
	 * Add a language file to the main language array
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function loadfile($which = '', $package = '', $show_errors = TRUE)
	{
		if ($which == '')
		{
			return;
		}

		// Sec.ur.ity code.  ::sigh::
		$package = ($package == '')
			? ee()->security->sanitize_filename(str_replace(array('lang.', '.php'), '', $which))
			: ee()->security->sanitize_filename($package);
		$which = str_replace('lang.', '', $which);

		// If we're in the installer, don't load Session library
		$idiom = $this->getIdiom();

		$this->load($which, $idiom, FALSE, TRUE, PATH_THIRD.$package.'/', $show_errors);
	}

	// --------------------------------------------------------------------

	/**
	 * Get the idiom for the current user/situation
	 * @return string The idiom to load
	 */
	protected function getIdiom()
	{
		if (isset(ee()->session))
		{
			return ee()->security->sanitize_filename(ee()->session->get_language());
		}

		return ee()->config->item('deft_lang') ?: 'english';
	}

	// --------------------------------------------------------------------

	/**
	 * Load a language file
	 *
	 * Differs from CI's Lang::load() in that it checks each file for a default
	 * language version as a backup. Not sure this is appropriate for CI at
	 * large.
	 *
	 * @param	mixed	the name of the language file to be loaded. Can be an array
	 * @param	string	the language (english, etc.)
	 * @return	mixed
	 */
	function load($langfile = '', $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '', $show_errors = TRUE)
	{
		// Clean up langfile
		$langfile = str_replace('.php', '', $langfile);

		if ($add_suffix == TRUE)
		{
			$langfile = str_replace('_lang.', '', $langfile).'_lang';
		}

		$langfile .= '.php';

		// Check to see if it's already loaded
		if (in_array($langfile, $this->is_loaded, TRUE))
		{
			return;
		}

		$deft_lang = ee()->config->item('deft_lang') ?: 'english';
		$idiom = $this->getIdiom();

		$paths = array(
			// Check custom languages first
			SYSPATH.'user/language/'.$idiom.'/'.$langfile,
			// Check if the user session language is English
			BASEPATH.'language/'.$idiom.'/'.$langfile,
			// Check their defined default language
			SYSPATH.'user/language/'.$deft_lang.'/'.$langfile,
			// Lastly render the english
			BASEPATH.'language/english/'.$langfile
		);

		// If we're in the installer, add those lang files
		if (defined('EE_APPPATH'))
		{
			array_unshift(
				$paths,
				APPPATH.'language/'.$idiom.'/'.$langfile,
				APPPATH.'language/'.$deft_lang.'/'.$langfile
			);
		}

		// if it's in an alternate location, such as a package, check there first
		if ($alt_path != '')
		{
			// Temporary! Rename your language files!
			$third_party_old = 'lang.'.str_replace('_lang.', '.', $langfile);

			array_unshift($paths, $alt_path.'language/'.$deft_lang.'/'.$third_party_old);
			array_unshift($paths, $alt_path.'language/'.$idiom.'/'.$third_party_old);
			array_unshift($paths, $alt_path.'language/'.$deft_lang.'/'.$langfile);
			array_unshift($paths, $alt_path.'language/'.$idiom.'/'.$langfile);
		}

		// if idiom and deft_lang are the same, don't check those paths twice
		$paths = array_unique($paths);

		$success = FALSE;

		foreach ($paths as $path)
		{
			if (file_exists($path) && include $path)
			{
				$success = TRUE;
				break;
			}
		}

		if ($show_errors && $success !== TRUE)
		{
			show_error('Unable to load the requested language file: language/'.$idiom.'/'.$langfile);
		}

		if ( ! isset($lang))
		{
			log_message('error', 'Language file contains no data: language/'.$idiom.'/'.$langfile);
			return;
		}

		if ($return == TRUE)
		{
			return $lang;
		}

		$this->is_loaded[] = $langfile;
		$this->language = array_merge($this->language, $lang);
		unset($lang);

		log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 *   Fetch a specific line of text
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function line($which = '', $label = '')
	{
		if ($which != '')
		{
			$line = ( ! isset($this->language[$which])) ? $which : $this->language[$which];

			if ($label != '')
			{
				$line = '<label for="'.$label.'">'.$line."</label>";
			}

			return stripslashes($line);
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Get a list of available language packs
	 *
	 * @return array Associative array of language packs, with the keys being
	 * the directory names and the value being the name (ucfirst())
	 */
	public function language_pack_names()
	{
		$source_dir = SYSPATH.'user/language/';

		$dirs = array('english' => 'English');

		if ($fp = @opendir($source_dir))
		{
			while (FALSE !== ($file = readdir($fp)))
			{
				if (is_dir($source_dir.$file) && substr($file, 0, 1) != ".")
				{
					$dirs[$file] = ucfirst($file);
				}
			}
			closedir($fp);
		}

		 return $dirs;
	}
}
// END CLASS

// EOF
