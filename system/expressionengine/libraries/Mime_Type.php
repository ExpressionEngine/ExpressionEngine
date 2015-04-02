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

	protected $mime_type;

	public function __construct()
	{
		$this->mime_type = new MimeType();

		// Load the whitelisted mimes from disk
		$mime_file = APPPATH.'config/mimes.php';
		if (file_exists($mime_file) && is_readable($mime_file))
		{
			include($mime_file);
			$this->mime_type->addMimeTypes($whitelist);
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
				$this->mime_type->addMimeTypes($extra_mimes);
			}
			else
			{
				$this->mime_type->addMimeTypes(explode('|', $extra_mimes));
			}
		}
	}

	protected function memberExcludedFromWhitelistRestrictions()
	{
		$excluded_members = ee()->config->item('mime_whitelist_member_exception');
		if ($excluded_members !== FALSE)
		{
			$excluded_members = preg_split('/[\s|,]/', $excluded_members, -1, PREG_SPLIT_NO_EMPTY);
			$excluded_members = is_array($excluded_members) ? $excluded_members : array($excluded_members);

			if (in_array(ee()->session->userdata('member_id'), $excluded_members))
			{
				return TRUE;
			}
		}

		$excluded_member_groups = ee()->config->item('mime_whitelist_member_group_exception');
		if ($excluded_member_groups !== FALSE)
		{
			$excluded_member_groups = preg_split('/[\s|,]/', $excluded_member_groups, -1, PREG_SPLIT_NO_EMPTY);
			$excluded_member_groups = is_array($excluded_member_groups) ? $excluded_member_groups : array($excluded_member_groups);

			if (in_array(ee()->session->userdata('group_id'), $excluded_member_groups))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	public function ofFile($path)
	{
		try
		{
			return $this->mime_type->ofFile($path);
		}
		catch (Exception $e)
		{
			show_error(sprintf(lang('file_not_found'), $path));
		}
	}

	public function fileIsImage($path)
	{
		return $this->mime_type->fileIsImage($path);
	}

	public function isImage($mime)
	{
		try
		{
			return $this->mime_type->fileIsImage($path);
		}
		catch (Exception $e)
		{
			show_error(sprintf(lang('file_not_found'), $path));
		}
	}

	public function fileIsSafeForUpload($path)
	{
		if ($this->memberExcludedFromWhitelistRestrictions())
		{
			return TRUE;
		}

		return $this->mime_type->fileIsSafeForUpload($path);
	}

	public function isSafeForUpload($mime)
	{
		if ($this->memberExcludedFromWhitelistRestrictions())
		{
			return TRUE;
		}

		try
		{
			return $this->mime_type->isSafeForUpload($path);
		}
		catch (Exception $e)
		{
			show_error(sprintf(lang('file_not_found'), $path));
		}
	}

}
// EOF