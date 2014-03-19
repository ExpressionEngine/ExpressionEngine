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
		$Q[] = "CREATE TABLE IF NOT EXISTS exp_captcha (
		 date int(10) unsigned NOT NULL,
		 ip_address varchar(16) default '0' NOT NULL,
		 word varchar(20) NOT NULL,
		 KEY `word` (`word`)
		)";

		// status no access table

		$Q[] = "CREATE TABLE IF NOT EXISTS exp_status_no_access (
		 status_id int(6) unsigned NOT NULL,
		 member_group tinyint(3) unsigned NOT NULL
		)";

		// Field formatting

		$Q[] = "CREATE TABLE IF NOT EXISTS exp_field_formatting (
		 field_id int(10) unsigned NOT NULL,
		 field_fmt varchar(40) NOT NULL,
		 KEY `field_id` (`field_id`)
		)";


		// Define the table changes

		$Q[] = "insert into exp_specialty_templates(template_name, data_title, template_data) values ('decline_member_validation', '".addslashes(trim(decline_member_validation_title()))."', '".addslashes(decline_member_validation())."')";

		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN exclude_from_moderation char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_security_hashes ADD COLUMN ip_address varchar(16) default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN comment_use_captcha char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_upload_prefs ADD COLUMN weblog_id int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_search ADD COLUMN keywords varchar(60) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_show_fmt char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblog_fields CHANGE COLUMN field_fmt field_fmt varchar(40) NOT NULL default 'xhtml'";
		$Q[] = "UPDATE exp_member_groups set exclude_from_moderation = 'y' WHERE group_id = '1'";

		// Run the queries
		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}

		$query = ee()->db->query("SELECT field_id FROM exp_weblog_fields");

		foreach ($query->result_array() as $row)
		{
			ee()->db->query("insert into exp_field_formatting (field_id, field_fmt) values ('".$row['field_id']."', 'none')");
			ee()->db->query("insert into exp_field_formatting (field_id, field_fmt) values ('".$row['field_id']."', 'br')");
			ee()->db->query("insert into exp_field_formatting (field_id, field_fmt) values ('".$row['field_id']."', 'xhtml')");
		}

		$query = ee()->db->query("SELECT * FROM exp_weblog_data");

		foreach ($query->result_array() as $key => $val)
		{
			if (substr($key, 0, 9) == 'field_ft_')
			{
				$id = substr($key, 9);

				ee()->db->query("ALTER TABLE exp_weblog_data CHANGE COLUMN field_ft_".$id." field_ft_".$id." varchar(40) NOT NULL default 'xhtml'");
			}
		}

		/** -----------------------------------------
		/**  Update config file with new prefs
		/** -----------------------------------------*/

		$captcha_url = $this->config['site_url'];

		if (substr($captcha_url, -1) != '/')
		{
			$captcha_url .= '/';
		}

		$captcha_url .= 'images/captchas/';

		$data = array(
						'captcha_path'				=> './images/captchas/',
						'captcha_url'				=> $captcha_url,
						'captcha_font'				=> 'y',
						'use_membership_captcha'	=> 'n',
						'auto_convert_high_ascii'	=> 'n'

					);

		ee()->config->_append_config_1x($data);

		return TRUE;
	}

}
// END CLASS




//---------------------------------------------------
//	Decline Member Validation
//--------------------------------------------------

function decline_member_validation_title()
{
return <<<EOF
Your membership account has been declined
EOF;
}

function decline_member_validation()
{
return <<<EOF
{name},

We're sorry but our staff has decided not to validate your membership.

{site_name}
{site_url}
EOF;
}
/* END */



/* End of file ud_100.php */
/* Location: ./system/expressionengine/installer/updates/ud_100.php */