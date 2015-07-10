<?php

namespace EllisLab\ExpressionEngine\Module\Comment\Model;

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
 * ExpressionEngine Comment Model
 *
 * A model representing a comment on a Channel entry.
 *
 * @package		ExpressionEngine
 * @subpackage	Comment Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Comment extends Model {
	
	protected static $_primary_key = 'comment_id';
	protected static $_table_name = 'comments';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'BelongsTo'
		),
		'Entry' => array(
			'type' => 'BelongsTo',
			'model' => 'ChannelEntry'
		),
		'Channel' => array(
			'type' => 'BelongsTo'
		),
		'Author' => array(
			'type' => 'BelongsTo',
			'model' => 'Member'
		)
	);

	protected static $_validation_rules = array(
		'site_id'    => 'required|isNatural',
		'entry_id'   => 'required|isNatural',
		'channel_id' => 'required|isNatural',
		'author_id'  => 'required|isNatural',
		'status'     => 'enum[o,c,p,s]',
		'ip_address' => 'ip_address',
		'comment'    => 'required',
	);

	protected $comment_id;
	protected $site_id;
	protected $entry_id;
	protected $channel_id;
	protected $author_id;
	protected $status;
	protected $name;
	protected $email;
	protected $url;
	protected $location;
	protected $ip_address;
	protected $comment_date;
	protected $edit_date;
	protected $comment;

}
