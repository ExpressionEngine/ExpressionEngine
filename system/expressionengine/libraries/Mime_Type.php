<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\Mime;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Mime Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Mime_Type {

	protected $mime_library;

	public function __construct()
	{
		$this->mime_library = new Mime();

		// Load the whitelisted mimes from disk
		$mime_file = APPPATH.'config/mimes.php';
		if (file_exists($mime_file) && is_readable($mime_file))
		{
			include($mime_file);
			$this->mime_library->addMimeTypes($whitelist);
			unset($whitelist);
		}
		else
		{
			show_error(sprintf(lang('missing_mime_config'), $mime_file));
		}

		// Add any mime types from the config
		$extra_mimes = ee()->config->item('mime_whitelist_additions');
		if ($extra_mimes !== FALSE)
		{
			if (is_array($extra_mimes))
			{
				$this->mime_library->addMimeTypes($extra_mimes);
			}
			else
			{
				$this->mime_library->addMimeTypes(explode('|', $extra_mimes));
			}
		}
	}

	public function ofFile($path)
	{
		try
		{
			return $this->mime_library->ofFile($path);
		}
		catch (Exception $e)
		{
			show_error(sprintf(lang('file_not_found'), $path));
		}
	}

	public function fileIsImage($path)
	{
		return $this->mime_library->fileIsImage($path);
	}

	public function isImage($mime)
	{
		try
		{
			return $this->mime_library->fileIsImage($path);
		}
		catch (Exception $e)
		{
			show_error(sprintf(lang('file_not_found'), $path));
		}
	}

	public function fileIsSafeForUpload($path)
	{
		return $this->mime_library->fileIsSafeForUpload($path);

	}

	public function isSafeForUpload($mime)
	{
		try
		{
			return $this->mime_library->isSafeForUpload($path);
		}
		catch (Exception $e)
		{
			show_error(sprintf(lang('file_not_found'), $path));
		}
	}


}
// EOF