<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\File\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\SerializedType;
use EllisLab\ExpressionEngine\Library\Data\Collection;

/**
 * Local Path Column
 */
class LocalPath extends SerializedType {

	protected $files;
	protected $path;

	/**
	* This is a stub, since we do the actual loading when the property is accessed.
	*/
	public function unserialize($db_data)
	{
		$this->path = $db_data;
		return $this;
	}

	public function store($data)
	{
		return $data;
	}

	/**
	 * readPath will instatiate a collection of file models for every file in
	 * this column's path.
	 *
	 * @return Collection  A Collection of File objects
	 */
	protected function readPath()
	{
		$path = parse_config_variables($this->path);

		if (is_dir($path))
		{
			$files = array();
			$directory = ee('Model')->get('UploadDestination')->fields('id')->filter('server_path', $this->path)->first();
			$mime = new \EllisLab\ExpressionEngine\Library\Mime\MimeType();
			$exclude = array('index.html');

			if ($dh = opendir($path))
			{
				while (($file = readdir($dh)) !== false)
				{
					$path = $path . '/' . $file;

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

	/**
	 * We use a custom getter so we can load our files in when the property is
	 * read instead of when the column is instantiated.
	 *
	 * @param mixed $property
	 * @return mixed
	 */
	public function __get($property)
	{
		if ($property == 'files')
		{
			return $this->readPath();
		}

		if (isset($this->$property))
		{
			return $this->$property;
		}

		user_error("Invalid property: " . __CLASS__ . "->$property");
	}

	/**
	 * Override the string representation so we can still treat the sever_path
	 * as a string when we want to.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->path ?: '';
	}
}

// EOF
