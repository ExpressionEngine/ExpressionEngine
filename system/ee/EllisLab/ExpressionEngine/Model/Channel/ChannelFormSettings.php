<?php

namespace EllisLab\ExpressionEngine\Model\Channel;

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
 * ExpressionEngine Channel Form Settings Model
 *
 * @package		ExpressionEngine
 * @subpackage	File
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class ChannelFormSettings extends Model {

	protected static $_primary_key = 'channel_form_settings_id';
	protected static $_table_name = 'channel_form_settings';

	protected static $_relationships = array(
		'Channel' => array(
			'type' => 'belongsTo'
		)
	);

	protected $channel_form_settings_id;
	protected $site_id;
	protected $channel_id;
	protected $default_status;
	protected $allow_guest_posts;
	protected $default_author;
}

// EOF
