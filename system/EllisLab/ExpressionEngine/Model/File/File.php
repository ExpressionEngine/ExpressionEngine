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
 * ExpressionEngine File Model
 *
 * A model representing one of many possible upload destintations to which
 * files may be uploaded through the file manager or from the publish page.
 * Contains settings for this upload destination which describe what type of
 * files may be uploaded to it, as well as essential information, such as the
 * server paths where those files actually end up.
 *
 * @package		ExpressionEngine
 * @subpackage	File
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class File extends Model {

	protected static $_primary_key = 'file_id';
	protected static $_table_name = 'files';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'belongsTo'
		),
		'UploadDestination' => array(
			'type' => 'belongsTo',
			'to_key' => 'id',
			'from_key' => 'upload_location_id',
		),
		'UploadAuthor' => array(
			'type'     => 'BelongsTo',
			'model'    => 'Member',
			'from_key' => 'uploaded_by_member_id'
		),
		'ModifyAuthor' => array(
			'type'     => 'BelongsTo',
			'model'    => 'Member',
			'from_key' => 'modified_by_member_id'
		),
	);

	protected $file_id;
	protected $site_id;
	protected $title;
	protected $upload_location_id;
	protected $rel_path;
	protected $mime_type;
	protected $file_name;
	protected $file_size;
	protected $description;
	protected $credit;
	protected $location;
	protected $uploaded_by_member_id;
	protected $upload_date;
	protected $modified_by_member_id;
	protected $modified_date;
	protected $file_hw_original;

}