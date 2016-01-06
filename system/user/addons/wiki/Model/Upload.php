<?php

namespace User\addons\Wiki\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine Wiki Upload Model
 *
 * A model representing an Upload in the Wiki module.
 *
 * @package		ExpressionEngine
 * @subpackage	Wiki Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Upload extends Model {

	protected static $_primary_key = 'wiki_upload_id';
	protected static $_table_name = 'wiki_uploads';
	
	protected static $_relationships = array(
		'Wiki' => array(
			'type' => 'belongsTo'
		),
//      'Author' => array(
//			'type'     => 'belongsTo',
//			'from_key' => 'upload_author',
//			'to_key'   => 'member_id',
//			'model'    => 'ee:Member',
//			'weak'     => TRUE,
//			'inverse' => array(
//				'name' => 'Uploads',
//				'type' => 'hasMany'
 //           )
 //       ),
      'File' => array(
			'type'     => 'belongsTo',
			'from_key' => 'file_name',
			'to_key'   => 'file_name',
			'model'    => 'ee:File',
			'weak'     => FALSE,  // we want to delete them
			'inverse' => array(
				'name' => 'Uploads',
				'type' => 'hasMany'
            )
        )	
	);	

	protected $wiki_upload_id;
	protected $wiki_id;
	protected $file_name;
	protected $file_hash;
	protected $upload_summary;
	protected $upload_author;
	protected $image_width;
	protected $image_height;
	protected $file_type;
	protected $file_size;
	protected $upload_date;
}