<?php

namespace EllisLab\ExpressionEngine\Model\File;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
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
}
