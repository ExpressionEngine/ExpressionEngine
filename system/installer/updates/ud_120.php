<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $versions = array(
							'Blacklist'	=> '2.0',
							'Comment'	=> '1.2',
							'Email'		=> '1.1',
							'Member'	=> '1.2',
							'Moblog'	=> '2.0',
							'Referrer'	=> '1.3',
							'Search'	=> '1.2',
							'Trackback'	=> '1.1',
							'Channel'	=> '1.2'
						);


	function Updater()
	{
		$this->EE =& get_instance();

		// Grab the config file
		if ( ! @include(ee()->config->config_path))
		{
			show_error('Your config'.EXT.' file is unreadable. Please make sure the file exists and that the file permissions to 666 on the following file: expressionengine/config/config.php');
		}

		if (isset($conf))
		{
			$config = $conf;
		}

		// Does the config array exist?
		if ( ! isset($config) OR ! is_array($config))
		{
			show_error('Your config'.EXT.' file does not appear to contain any data.');
		}

		$this->config =& $config;
	}

	function do_update()
	{
		// Safety.  Prevents a problem if the
		// version indicator was not updated
		if (isset($this->config['encryption_type']))
		{
			return TRUE;
		}

		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN trackback_use_captcha char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN comment_notify_authors char(1) NOT NULL default 'n'";
		$Q[] = "INSERT INTO exp_specialty_templates(template_name, data_title, template_data) values ('gallery_comment_notification', 'Someone just responded to your comment', '".addslashes(gallery_comment_notification())."')";
		$Q[] = "INSERT INTO exp_specialty_templates(template_name, data_title, template_data) values ('admin_notify_gallery_comment', 'You have just received a comment', '".addslashes(admin_notify_gallery_comment())."')";


		// Run the queries
		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}


		/** -----------------------------------------
		/**  Update config file with new prefs
		/** -----------------------------------------*/

		$data = array(
						'encryption_type'			=> 'sha1',
						'email_debug'				=> 'n',
						'enable_sql_caching'		=> 'n',
						'use_category_name'			=> 'n',
						'reserved_category_word'	=> 'category',
                    	'auto_assign_cat_parents'	=> 'y'
					);

		ee()->config->_append_config_1x($data);


		// Update Module Version indicators
		$this->upate_module_versions();


		// DONE!!
		return TRUE;
	}


	/** -----------------------------
	/**  Update Module Version indicators
	/** -----------------------------*/

	function upate_module_versions()
	{
		if (count($this->versions) == 0)
			return;

		foreach ($this->versions as $key => $val)
		{
			ee()->db->query("UPDATE exp_modules SET module_version = '{$val}' WHERE module_name = '{$key}'");
		}
	}



}
// END CLASS






//---------------------------------------------------
//	Admin Notification of New Gallery Comment
//--------------------------------------------------

function admin_notify_gallery_comment_title()
{
return <<<EOF
You have just received a comment
EOF;
}

function admin_notify_gallery_comment()
{
return <<<EOF
You have just received a comment for the following photo gallery:
{gallery_name}

The title of the entry is:
{entry_title}

Located at:
{comment_url}

{comment}
EOF;
}
/* END */


function gallery_comment_notification()
{
return <<<EOF
Someone just responded to the photo entry you subscribed to at:
{gallery_name}

You can see the comment at the following URL:
{comment_url}

{comment}

To stop receiving notifications for this comment, click here:
{notification_removal_url}
EOF;
}
/* END */


/* End of file ud_120.php */
/* Location: ./system/expressionengine/installer/updates/ud_120.php */