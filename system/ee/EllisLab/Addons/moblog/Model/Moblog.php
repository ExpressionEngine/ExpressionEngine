<?php

namespace EllisLab\Addons\Moblog\Model;

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
 * ExpressionEngine Moblog Model
 *
 * @package		ExpressionEngine
 * @subpackage	Moblog Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Moblog extends Model {

	protected static $_primary_key = 'moblog_id';
	protected static $_table_name = 'moblogs';

	protected static $_typed_columns = array(
		'moblog_email_login'    => 'base64',
		'moblog_email_password' => 'base64',
		'moblog_valid_from'     => 'commaDelimited',
		'moblog_categories'     => 'pipeDelimited'
	);

	protected static $_validation_rules = array(
		'moblog_full_name'	      => 'required|unique',
		'moblog_short_name'       => 'required|unique',
		'moblog_auth_required'    => 'required|enum[y,n]',
		'moblog_auth_delete'      => 'required|enum[y,n]',
		'moblog_email_type'       => 'required|enum[pop3]', # Only POP3 supported at the moment
		'moblog_email_address'    => 'required|email',
		'moblog_email_server'     => 'required',
		'moblog_email_login'      => 'required',
		'moblog_email_password'   => 'required',
		'moblog_time_interval'    => 'required|isNaturalNoZero',
		'moblog_enabled'          => 'required|enum[y,n]',
		'moblog_valid_from'       => 'validateEmails',
		'moblog_allow_overrides'  => 'enum[y,n]',
		'moblog_sticky_entry'     => 'enum[y,n]',
		'moblog_upload_directory' => 'isNaturalNoZero',
		'moblog_image_size'       => 'isNatural',
		'moblog_thumb_size'       => 'isNatural',
	);

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

	/**
	 * Ensures fields with multiple emails contain valid emails
	 */
	public function validateEmails($key, $value, $params, $rule)
	{
		// Not dirty
		if (empty($value))
		{
			return TRUE;
		}

		foreach($value as $email)
		{
			if (trim($email) != '' && (bool) filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE)
			{
				return 'valid_emails';
			}
		}

		return TRUE;
	}
}

// EOF
