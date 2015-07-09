<?php

namespace EllisLab\ExpressionEngine\Module\Comment\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

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
 * ExpressionEngine Comment Gateway
 *
 * A gateway allowing persistance of comments on entries.
 *
 * @package		ExpressionEngine
 * @subpackage	Comment Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CommentGateway extends Gateway {
	protected static $_primary_key = 'comment_id';
	protected static $_table_name = 'comments';

	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		),
		'entry_id' => array(
			'gateway' => 'ChannelTitleGateway',
			'key' => 'entry_id'
		),
		'channel_id' => array(
			'gateway' => 'Channel',
			'key' => 'channel_id'
		),
		'author_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
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
