<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.0
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

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_update_email_cache_table',
				'_update_upload_no_access_table',
				'_insert_comment_settings_into_db',
				'_insert_cookie_settings_into_db',
				'_create_plugins_table',
				'_remove_accessories_table',
				'_update_specialty_templates_table',
				'_remove_watermarks_table',
				'_update_templates_save_as_files'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// -------------------------------------------------------------------------

	/**
	 * Removes 3 columns and adds 1 column to the email_cache table
	 *
	 * @access private
	 * @return void
	 */
	private function _update_email_cache_table()
	{
		ee()->smartforge->drop_column('email_cache', 'mailinglist');
		ee()->smartforge->drop_column('email_cache', 'priority');

		ee()->smartforge->add_column(
			'email_cache',
			array(
				'attachments' => array(
					'type'			=> 'mediumtext',
					'null'			=> TRUE
				)
			)
		);
	}

	// -------------------------------------------------------------------------

	/**
	 * Removes the upload_loc column from the upload_no_access table.
	 *
	 * @return void
	 */
	private function _update_upload_no_access_table()
	{
		ee()->smartforge->drop_column('upload_no_access', 'upload_loc');
	}

	// -------------------------------------------------------------------------

	/**
	 * Previously, Comment module settings were stored in config.php. Since the
	 * Comment module is more integrated like Channel, let's take the settings
	 * out of there and put them in the sites table because it's a better place
	 * for them and they can be separated by site.
	 *
	 * @access private
	 * @return void
	 */
	private function _insert_comment_settings_into_db()
	{
		$comment_edit_time_limit = ee()->config->item('comment_edit_time_limit');

		$settings = array(
			// This is a new config, default it to y if not set
			'enable_comments' => ee()->config->item('enable_comments') ?: 'y',
			// These next two default to n
			'comment_word_censoring' => (ee()->config->item('comment_word_censoring') == 'y') ? 'y' : 'n',
			'comment_moderation_override' => (ee()->config->item('comment_moderation_override') == 'y') ? 'y' : 'n',
			// Default this to 0
			'comment_edit_time_limit' => ($comment_edit_time_limit && ctype_digit($comment_edit_time_limit))
				? $comment_edit_time_limit : 0
		);

		ee()->config->update_site_prefs($settings, 'all');
		ee()->config->_update_config(array(), $settings);
	}

	// -------------------------------------------------------------------------

	/**
	 * cookie_httponly and cookie_secure were only stored in config.php, let's
	 * pluck them out into the database.
	 *
	 * @access private
	 * @return void
	 */
	private function _insert_cookie_settings_into_db()
	{
		$settings = array(
			// Default cookie_httponly to y
			'cookie_httponly' => ee()->config->item('cookie_httponly') ?: 'y',
			// Default cookie_secure to n
			'cookie_secure' => ee()->config->item('cookie_secure') ?: 'n',
		);

		ee()->config->update_site_prefs($settings, 'all');
		ee()->config->_update_config(array(), $settings);
	}

	/**
	 * Creates the new plugins table and adds all the current plugins to the table
	 *
	 * @return void
	 */
	private function _create_plugins_table()
	{
		ee()->dbforge->add_field(
			array(
				'plugin_id' => array(
					'type'			 => 'int',
					'constraint'     => 10,
					'null'			 => FALSE,
					'unsigned'		 => TRUE,
					'auto_increment' => TRUE
				),
				'plugin_name' => array(
					'type'			=> 'varchar',
					'constraint'    => 50,
					'null'			=> FALSE
				),
				'plugin_package' => array(
					'type'			=> 'varchar',
					'constraint'    => 50,
					'null'			=> FALSE
				),
				'plugin_version' => array(
					'type'			=> 'varchar',
					'constraint'    => 12,
					'null'			=> FALSE
				),
				'is_typography_related' => array(
					'type'			=> 'char',
					'constraint'    => 1,
					'default'		=> 'n',
					'null'		    => FALSE
				)
			)
		);
		ee()->dbforge->add_key('plugin_id', TRUE);
		ee()->smartforge->create_table('plugins');

		define('PATH_PI', EE_APPPATH.'plugins/');

		ee()->load->model('addons_model');
		$plugins = ee()->addons_model->get_plugins();

		foreach ($plugins as $plugin => $info)
		{
			$typography = 'n';
			if (array_key_exists('pi_typography', $info) && $info['pi_typography'] == TRUE)
			{
				$typography = 'y';
			}

			ee()->db->insert('plugins', array(
				'plugin_name' => $info['pi_name'],
				'plugin_package' => $plugin,
				'plugin_version' => $info['pi_version'],
				'is_typography_related' => $typography
			));
		}
	}

	/**
	 * Accessories are going away in 3.0. This removes their table.
	 *
	 * @return void
	 */
	private function _remove_accessories_table()
	{
		ee()->smartforge->drop_table('accessories');
		ee()->smartforge->drop_column('member_groups', 'can_access_accessories');
	}

	/**
	 * Adds 4 columns to the specialty_templates table
	 */
	private function _update_specialty_templates_table()
	{
		ee()->smartforge->add_column(
			'specialty_templates',
			array(
				'template_notes'   => array(
					'type'         => 'text',
					'null'         => TRUE
				),
				'template_type'    => array(
					'type'         => 'varchar',
					'constraint'   => 16,
					'null'         => TRUE
				),
				'template_subtype' => array(
					'type'         => 'varchar',
					'constraint'   => 16,
					'null'         => TRUE
				),
				'edit_date'        => array(
					'type'         => 'int',
					'constraint'   => 10,
					'null'         => FALSE,
					'default'      => 0
				),
				'last_author_id'   => array(
					'type'         => 'int',
					'constraint'   => 10,
					'null'         => FALSE,
					'unsigned'     => TRUE,
					'default'      => 0
				),
			)
		);

		$system = array('offline_template', 'message_template');
		$email = array(
			'admin_notify_comment' => 'comments',
			'admin_notify_entry' => 'content',
			'admin_notify_mailinglist' => 'mailing_lists',
			'admin_notify_reg' => 'members',
			'comments_opened_notification' => 'comments',
			'comment_notification' => 'comments',
			'decline_member_validation' => 'members',
			'forgot_password_instructions' => 'members',
			'mailinglist_activation_instructions' => 'mailing_lists',
			'mbr_activation_instructions' => 'members',
			'pm_inbox_full' => 'private_messages',
			'private_message_notification' => 'private_messages',
			'validated_member_notify' => 'members',
			'admin_notify_forum_post' => 'forums',
			'forum_post_notification' => 'forums',
			'forum_moderation_notification' => 'forums',
			'forum_report_notification' => 'forums'
		);

		// Mark the email templates
		$templates = ee()->db->select('template_id, template_name, template_type, template_subtype, edit_date')
			->get('specialty_templates')
			->result_array();

		if ( ! empty($templates))
		{
			foreach ($templates as $index => $template)
			{
				$templates[$index]['edit_date'] = time();

				if (in_array($template['template_name'], $system))
				{
					$templates[$index]['template_type'] = 'system';
				}
				elseif (in_array($template['template_name'], array_keys($email)))
				{
					$templates[$index]['template_type'] = 'email';
					$templates[$index]['template_subtype'] = $email[$template['template_name']];
				}
			}

			ee()->db->update_batch('specialty_templates', $templates, 'template_id');
		}
	}

	/**
	 * File Watermarks are going away in 3.0. This removes their table.
	 *
	 * @return void
	 */
	private function _remove_watermarks_table()
	{
		ee()->smartforge->drop_table('file_watermarks');
		ee()->smartforge->drop_column('file_dimensions', 'watermark_id');
	}

	// -------------------------------------------------------------------

	/**
	 * We are removing the per-template "save to file" option. Instead it is
	 * an all or nothing proposition based on the global preferences. So we are
	 * removing the column from the database and resyncing the templates.
	 */
	private function _update_templates_save_as_files()
	{
		ee()->smartforge->drop_column('templates', 'save_template_file');

		$installer_config = ee()->config;

		require_once(APPPATH . 'libraries/Extensions.php');
		ee()->extensions = new Installer_Extensions();
		ee()->load->model('template_model');

		$sites = ee()->db->select('site_id')
			->get('sites')
			->result_array();

		// Loop through the sites and save to file any templates that are only
		// in the database
		foreach ($sites as $site)
		{
			ee()->config = new MSM_Config();
			ee()->config->site_prefs('', $site['site_id']);

			if (ee()->config->item('save_tmpl_files') == 'y' AND ee()->config->item('tmpl_file_basepath') != '') {
				$templates = ee()->template_model->fetch_last_edit(array('templates.site_id' => $site['site_id']), TRUE);

				foreach($templates as $template)
				{
					if ( ! $template->loaded_from_file)
					{
						ee()->template_model->save_to_file($template);
					}
				}
			}

		}
		ee()->config = $installer_config;
	}

}
/* END CLASS */

/* End of file ud_300.php */
/* Location: ./system/expressionengine/installer/updates/ud_300.php */
