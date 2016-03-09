<?php

namespace EllisLab\Addons\Forum\Model;

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
 * ExpressionEngine Attachment Model for the Forum
 *
 * A model representing an attachment in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Attachment extends Model {

	protected static $_primary_key = 'attachment_id';
	protected static $_table_name = 'forum_attachments';

	protected static $_typed_columns = array(
		'topic_id'        => 'int',
		'post_id'         => 'int',
		'board_id'        => 'int',
		'member_id'       => 'int',
		'filesize'        => 'int',
		'hits'            => 'int',
		'attachment_date' => 'timestamp',
		'is_temp'         => 'boolString',
		'width'           => 'int',
		'height'          => 'int',
		't_width'         => 'int',
		't_height'        => 'int',
		'is_image'        => 'boolString',
	);

	protected static $_relationships = array(
		'Board' => array(
			'type' => 'belongsTo'
		),
		'Member' => array(
			'type'  => 'belongsTo',
			'model' => 'ee:Member',
			'weak'  => TRUE,
			'inverse' => array(
				'name' => 'Attachment',
				'type' => 'hasMany'
			)
		),
		'Post' => array(
			'type' => 'belongsTo'
		),
		'Topic' => array(
			'type' => 'belongsTo'
		),
	);

	protected static $_validation_rules = array(
		'filename'        => 'required',
		'filehash'        => 'required',
		'extension'       => 'required',
		'attachment_date' => 'required',
		'is_temp'         => 'enum[y,n]',
		'width'           => 'required',
		'height'          => 'required',
		't_width'         => 'required',
		't_height'        => 'required',
		'is_image'        => 'enum[y,n]',
	);

	protected $attachment_id;
	protected $topic_id;
	protected $post_id;
	protected $board_id;
	protected $member_id;
	protected $filename;
	protected $filehash;
	protected $filesize;
	protected $extension;
	protected $hits;
	protected $attachment_date;
	protected $is_temp;
	protected $width;
	protected $height;
	protected $t_width;
	protected $t_height;
	protected $is_image;

}

// EOF
