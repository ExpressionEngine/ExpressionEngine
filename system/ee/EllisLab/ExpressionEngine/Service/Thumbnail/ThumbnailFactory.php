<?php

namespace EllisLab\ExpressionEngine\Service\Thumbnail;

use EllisLab\ExpressionEngine\Model\File\File;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine ThumbnailFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class ThumbnailFactory {

	public function get(File $file = NULL)
	{
		$thumb = new Thumbnail($file);

		// If the thumbnail is missing, and this is an image file generate
		// the thumbnail now
		if ( ! $thumb->exists()
			&& $file
			&& $file->exists()
			&& $file->isImage())
		{
			$thumb = $this->make($file);
		}

		return $thumb;
	}

	public function make(File $file)
	{
		// We only make thumbnails of images
		if ($file->isImage())
		{
			ee()->load->library('filemanager');
			$dir = $file->UploadDestination;
			$dimensions = $dir->FileDimensions;

			$success = ee()->filemanager->create_thumb(
				$file->getAbsolutePath(),
				array(
					'server_path' => $dir->server_path,
					'file_name' => $file->file_name,
					'dimensions' => $dimensions->asArray()
				),
				TRUE, // Regenerate thumbnails
				FALSE // Regenerate all images
			);
		}

		$thumb = new Thumbnail($file);
		return $thumb;
	}

}

// EOF