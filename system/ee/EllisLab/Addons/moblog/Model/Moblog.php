<?php

namespace EllisLab\Addons\Moblog\Model;

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
 * ExpressionEngine Moblog Model
 *
 * @package		ExpressionEngine
 * @subpackage	Moblog Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Moblog extends Model {

	protected static $_primary_key = 'moblog_id';
	protected static $_table_name = 'moblogs';

	protected $moblog_id;
	protected $moblog_full_name;
	protected $moblog_short_name;
	protected $moblog_enabled;
	protected $moblog_file_archive;
	protected $moblog_time_interval;
	protected $moblog_type;
	protected $moblog_gallery_id;
	protected $moblog_gallery_category;
	protected $moblog_gallery_status;
	protected $moblog_gallery_comments;
	protected $moblog_gallery_author;
	protected $moblog_channel_id;
	protected $moblog_categories;
	protected $moblog_field_id;
	protected $moblog_status;
	protected $moblog_author_id;
	protected $moblog_sticky_entry;
	protected $moblog_allow_overrides;
	protected $moblog_auth_required;
	protected $moblog_auth_delete;
	protected $moblog_upload_directory;
	protected $moblog_template;
	protected $moblog_image_size;
	protected $moblog_thumb_size;
	protected $moblog_email_type;
	protected $moblog_email_address;
	protected $moblog_email_server;
	protected $moblog_email_login;
	protected $moblog_email_password;
	protected $moblog_subject_prefix;
	protected $moblog_valid_from;
	protected $moblog_ignore_text;
}
