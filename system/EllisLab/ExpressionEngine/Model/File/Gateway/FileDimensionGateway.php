<?php

namespace EllisLab\ExpressionEngine\Model\File\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

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
 * ExpressionEngine File Dimensions Table
 *
 * @package		ExpressionEngine
 * @subpackage	File\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FileDimensionGateway extends Gateway {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'file_dimensions';

	protected static $_related_gateways = array(

		// Many to one to the upload destination it belongs to
		'upload_location_id' => array(
			'gateway' => 'UploadPrefGateway',
			'key' => 'id'
		)
	);

	protected $id;
	protected $site_id;
	protected $upload_location_id;
	protected $title;
	protected $short_name;
	protected $resize_type;
	protected $width;
	protected $height;
}
