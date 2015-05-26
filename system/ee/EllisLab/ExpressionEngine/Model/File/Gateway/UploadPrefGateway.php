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
 * ExpressionEngine Upload Prefs Table
 *
 * @package		ExpressionEngine
 * @subpackage	File\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class UploadPrefGateway extends Gateway {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'upload_prefs';

	protected static $_related_gateways = array(

		// Many to one to the Site.  Standard site relationship, indicating
		// which site this upload destination belongs to.
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		),

		// A many to many relationship to member group.  Member groups attached
		// through this relationship are not allowed access to this upload
		// location.
		'id' => array(
			'NoAccess' => array(
				'gateway' => 'MemberGroupGateway',
				'key' => 'group_id',
				'pivot_table' => 'upload_no_access',
				'pivot_key' => 'upload_id',
				'pivot_foreign_key' => 'member_group'
			),
			'FileDimension' => array(
				'gateway' => 'FileDimensionGateway',
				'key' => 'upload_location_id'
			)
		)
	);

	protected $id;
	protected $site_id;
	protected $name;
	protected $server_path;
	protected $url;
	protected $allowed_types;
	protected $max_size;
	protected $max_height;
	protected $max_width;
	protected $properties;
	protected $pre_format;
	protected $post_format;
	protected $file_properties;
	protected $file_pre_format;
	protected $file_post_format;
	protected $cat_group;
	protected $batch_location;
}
