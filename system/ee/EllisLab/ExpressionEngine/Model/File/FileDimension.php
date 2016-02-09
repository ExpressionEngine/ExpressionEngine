<?php

namespace EllisLab\ExpressionEngine\Model\File;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine File Dimension Model
 *
 * A model representing one of image manipulations that can be applied on
 * images uploaded to its corresponting upload destination.
 *
 * @package		ExpressionEngine
 * @subpackage	File
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class FileDimension extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'file_dimensions';

	protected static $_typed_columns = array(
		//'width'  => 'int',
		//'height' => 'int'
	);

	protected static $_relationships = array(
		'UploadDestination' => array(
			'type' => 'belongsTo',
			'from_key' => 'upload_location_id'
		),
		'Watermark' => array(
			'type' => 'hasOne',
			'from_key' => 'watermark_id',
			'to_key' => 'wm_id'
		)
	);

	protected static $_validation_rules = array(
		'short_name'  => 'required|xss|alphaDash|uniqueWithinSiblings[UploadDestination,FileDimensions]',
		'resize_type' => 'enum[crop,constrain]',
		'width'       => 'isNatural|validateDimension',
		'height'      => 'isNatural|validateDimension'
	);

	protected $id;
	protected $site_id;
	protected $upload_location_id;
	protected $title;
	protected $short_name;
	protected $resize_type;
	protected $width;
	protected $height;
	protected $watermark_id;

	/**
	 * At least a height OR a width must be specified if there is no watermark selected
	 */
	public function validateDimension($key, $value, $params, $rule)
	{
		if (empty($this->width) && empty($this->height) && empty($this->watermark_id))
		{
			$rule->stop();
			return lang('image_manip_dimension_required');
		}

		return TRUE;
	}

	/**
	 * Calcuates the dimensions of a given file based on the constraints of this
	 * modification
	 *
	 * @param File $file A File entity
	 * @return array An associative array with 'width' and 'height' keys.
	 */
	public function getNewDimensionsOfFile(File $file)
	{
		ee()->load->library('image_lib');
		ee()->image_lib->clear();

		$original_dimensions = explode(" ", $file->file_hw_original);

		$width  = $this->width;
		$height = $this->height;

		$force_master_dim = FALSE;

		// If either h/w unspecified, calculate the other here
		if ($this->width == '' OR $this->width == 0)
		{
			$width = ($original_dimensions[1]/$original_dimensions[0])*$this->height;
			$force_master_dim = 'height';
		}
		elseif ($this->height == '' OR $this->height == 0)
		{
			// Old h/old w * new width
			$height = ($original_dimensions[0]/$original_dimensions[1])*$this->width;
			$force_master_dim = 'width';
		}

		$config = array(
			'source_image'   => $file->getAbsolutePath(),
			'image_library'  => ee()->config->item('image_resize_protocol'),
			'library_path'   => ee()->config->item('image_library_path'),
			'maintain_ratio' => TRUE,
			'width'          => $width,
			'height'         => $height,
			'master_dim'     => $force_master_dim
		);

		ee()->image_lib->initialize($config);

		$dimensions = array(
			'height' => ee()->image_lib->height,
			'width'  => ee()->image_lib->width,
		);

		return $dimensions;
	}
}

// EOF
