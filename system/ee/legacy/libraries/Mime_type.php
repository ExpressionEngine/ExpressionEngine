<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use  EllisLab\ExpressionEngine\Library\Mime\MimeType;

/**
 * Core Mime type
 */
class Mime_type {

	protected $mime_type;

	/**
	 * Instantiates a new MimeType object and adds whitelisted MIME types based
	 * on the config/mimes.php file and any MIME types in the
	 * 'mime_whitelist_additions' config override.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->mime_type = new MimeType();

		$whitelist = ee()->config->loadFile('mimes');

		$this->mime_type->addMimeTypes($whitelist);

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

	/**
	 * Checks the config for specific member exceptions or member group
	 * exceptions and compares the current member to those lists.
	 *
	 * @return bool TRUE if excluded; FALSE otherwise
	 */
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

	/**
	 * Determines the MIME type of a file
	 *
	 * @see MimeType::ofFile
	 */
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

	/**
	 * Determines the MIME type of a buffer
	 *
	 * @see MimeType::ofFile
	 */
	public function ofBuffer($buffer)
	{
		return $this->mime_type->ofBuffer($buffer);
	}

	/**
	 * Determines if a file is an image or not.
	 *
	 * @see MimeType::fileIsImage
	 */
	public function fileIsImage($path)
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

	/**
	 * Determines if a MIME type is in our list of valid image MIME types.
	 *
	 * @see MimeType::isImage
	 */
	public function isImage($mime)
	{
		return $this->mime_type->isImage($mime);
	}

	/**
	 * Gets the MIME type of a file and compares it to our whitelist to see if
	 * it is safe for upload.
	 *
	 * @see MimeType::fileIsSafeForUpload
	 */
	public function fileIsSafeForUpload($path)
	{
		if ($this->memberExcludedFromWhitelistRestrictions())
		{
			return TRUE;
		}

		return $this->mime_type->fileIsSafeForUpload($path);
	}

	/**
	 * Checks a given MIME type against our whitelist to see if it is safe for
	 * upload
	 *
	 * @see MimeType::isSafeForUpload
	 */
	public function isSafeForUpload($mime)
	{
		if ($this->memberExcludedFromWhitelistRestrictions())
		{
			return TRUE;
		}

		return $this->mime_type->isSafeForUpload($mime);
	}

	/**
	 * Returns the whitelist of MIME Types
	 *
	 * @return array An array of MIME types that are on the whitelist
	 */
	public function getWhitelist()
	{
		return $this->mime_type->getWhitelist();
	}

}

// EOF
