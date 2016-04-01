<?php

namespace EllisLab\ExpressionEngine\Model\Site\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Base64Native;
use EllisLab\ExpressionEngine\Service\Model\Column\CustomType;

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
 * ExpressionEngine Member Preferences
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class MemberPreferences extends CustomType {

	protected $un_min_len;
	protected $pw_min_len;
	protected $allow_member_registration;
	protected $allow_member_localization;
	protected $req_mbr_activation;
	protected $new_member_notification;
	protected $mbr_notification_emails;
	protected $require_terms_of_service;
	protected $default_member_group;
	protected $profile_trigger;
	protected $member_theme;
	protected $enable_avatars;
	protected $allow_avatar_uploads;
	protected $avatar_url;
	protected $avatar_path;
	protected $avatar_max_width;
	protected $avatar_max_height;
	protected $avatar_max_kb;
	protected $enable_photos;
	protected $photo_url;
	protected $photo_path;
	protected $photo_max_width;
	protected $photo_max_height;
	protected $photo_max_kb;
	protected $allow_signatures;
	protected $sig_maxlength;
	protected $sig_allow_img_hotlink;
	protected $sig_allow_img_upload;
	protected $sig_img_url;
	protected $sig_img_path;
	protected $sig_img_max_width;
	protected $sig_img_max_height;
	protected $sig_img_max_kb;
	protected $prv_msg_enabled;
	protected $prv_msg_allow_attachments;
	protected $prv_msg_upload_path;
	protected $prv_msg_max_attachments;
	protected $prv_msg_attach_maxsize;
	protected $prv_msg_attach_total;
	protected $prv_msg_html_format;
	protected $prv_msg_auto_links;
	protected $prv_msg_max_chars;
	protected $memberlist_order_by;
	protected $memberlist_sort_order;
	protected $memberlist_row_limit;
	protected $approved_member_notification;
	protected $declined_member_notification;

	/**
	* Called when the column is fetched from db
	*/
	public function unserialize($db_data)
	{
		return Base64Native::unserialize($db_data);
	}

	/**
	* Called before the column is written to the db
	*/
	public function serialize($data)
	{
		return Base64Native::serialize($data);
	}

}

// EOF
