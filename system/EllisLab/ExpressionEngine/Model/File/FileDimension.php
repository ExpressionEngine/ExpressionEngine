<?php

namespace EllisLab\ExpressionEngine\Model\File;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
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
	protected static $_gateway_names = array('FileDimensionGateway');

	protected $id;
	protected $site_id;
	protected $upload_location_id;
	protected $title;
	protected $short_name;
	protected $resize_type;
	protected $width;
	protected $height;
	protected $watermark_id;
}
