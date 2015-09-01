<?php

namespace EllisLab\ExpressionEngine\Model\File\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\CustomType;
use EllisLab\ExpressionEngine\Library\Data\Collection;

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
 * ExpressionEngine Local Path Column
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class LocalPath extends CustomType {

	protected $files;
	protected $path;

	/**
	* Called when the column is fetched from db
	*/
	public function unserialize($db_data)
	{
		return array('files' => new Collection());
	}

	/**
	* Called before the column is written to the db
	*/
	public function serialize($data)
	{
		return $data;
	}

	public function load($db_data)
	{
		$this->path = $db_data;

		return parent::load($db_data);
	}

	protected function readPath()
	{
		if (is_dir($this->path))
		{
			$files = array();
			$directory = ee('Model')->get('UploadDestination')->fields('id')->filter('server_path', $this->path)->first();
			$mime = new \EllisLab\ExpressionEngine\Library\Mime\MimeType();
			$exclude = array('index.html');
			
			if ($dh = opendir($this->path))
			{
				while (($file = readdir($dh)) !== false)
				{
					$path = $this->path . '/' . $file;

					if ( ! is_dir($path) && ! in_array($file, $exclude))
					{
						$data = array(
							'title' => $file,
							'file_name' => $file,
							'file_size' => filesize($path),
							'mime_type' => $mime->ofFile($path),
							'upload_location_id' => $directory->id
						);

						$files[] = ee('Model')->make('File', $data);
					}
		        }
		        closedir($dh);
		    }

			return new Collection($files);
		}
	}

	public function __get($property)
	{
		if ($property == 'files')
		{
			return $this->readPath();
		}

		return parent::__get($property);
	}

	public function __toString()
	{
		return $this->path;
	}
}
