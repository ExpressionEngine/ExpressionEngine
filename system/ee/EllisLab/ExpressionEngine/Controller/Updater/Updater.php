<?php

namespace EllisLab\ExpressionEngine\Controller\Updater;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Updater Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater extends CP_Controller {

	protected $filename = 'ExpressionEngine.zip';
	protected $extracted_folder = 'ExpressionEngine';

	/**
	 * Downloads zip
	 */
	public function download()
	{
		// TODO: Check cache permissions and bail out if not set

		// TODO: Delete files from old update attempts

		// TODO: Need a web service to return ZIP data and MD5 header
		//$update_url = ee('Request')->post('url');
		$update_url = 'https://dl.dropboxusercontent.com/u/28047/ExpressionEngine3.1.2.zip';

		$license_number = ee('License')->getEELicense()->getData('license_number');

		$data = ee('Curl')->post(
			$update_url,
			array('license' => $license_number)
		)->exec();

		ee('Filesystem')->write($this->path().$this->filename, $data, TRUE);

		// TODO: Verify zip integrity
		// TODO: How to get MD5 header out of Curl service?

		// TODO: Log success/failure (create Log service?)

		// TODO: Return URL to next step? The idea is these operations will be kicked off via AJAX
	}

	public function unzip()
	{
		ee('Filesystem')->mkDir($this->path().$this->extracted_folder);

		$zip = new \ZipArchive;
		if ($zip->open($this->path().$this->filename) === TRUE)
		{
			$zip->extractTo($this->path().$this->extracted_folder);
			$zip->close();
		}
	}

	public function verifyZipContents()
	{
		// TODO: Need a manifest inside the zip that contains MD5s of every file,
		// then use it to verify each file
	}

	public function moveUpdater()
	{
		// TODO: Move update package into place, verify its integrity, then launch
		// into the updater package; we're finished with this controller
	}

	private function path()
	{
		$cache_path = ee()->config->item('cache_path');

		if (empty($cache_path))
		{
			$cache_path = SYSPATH.'user'.DIRECTORY_SEPARATOR.'cache/';
		}

		$cache_path .= 'ee_update/';

		if ( ! is_dir($cache_path))
		{
			ee('Filesystem')->mkDir($cache_path);
		}

		return $cache_path;
	}
}
// EOF
