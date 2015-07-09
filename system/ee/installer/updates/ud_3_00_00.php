<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
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
				'_update_templates_save_as_files',
				'_update_layout_publish_table',
				'_update_entry_edit_date_format',
				'_rename_default_status_groups',
				'_centralize_captcha_settings',
				'_update_members_table',
				'_update_html_buttons',
				'_update_files_table',
				'_update_upload_prefs_table',
				'_update_upload_directories',
				'_drop_field_formatting_table'
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
	 *
	 * @return void
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

	// -------------------------------------------------------------------

	/**
	 * We are removing the per-template "save to file" option. Instead it is
	 * an all or nothing proposition based on the global preferences. So we are
	 * removing the column from the database and resyncing the templates.
	 *
	 * @return void
	 */
	private function _update_templates_save_as_files()
	{
		ee()->smartforge->drop_column('templates', 'save_template_file');

		$installer_config = ee()->config;

		require_once(APPPATH . 'libraries/Extensions.php');
		ee()->set('extensions', new Installer_Extensions());
		ee()->load->model('template_model');

		$sites = ee()->db->select('site_id')
			->get('sites')
			->result_array();

		// Loop through the sites and save to file any templates that are only
		// in the database
		foreach ($sites as $site)
		{
			ee()->remove('config');
			ee()->set('config', new MSM_Config());

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

		ee()->remove('config');
		ee()->set('config', $installer_config);
	}

	/**
	 * In 3.x Layouts now have names and the data structure for the field layout
	 * has changed.
	 *
	 * @return void
	 */
	private function _update_layout_publish_table()
	{
		if (ee()->db->table_exists('layout_publish_member_groups'))
		{
			return;
		}

		ee()->dbforge->add_field(
			array(
				'layout_id' => array(
					'type'       => 'int',
					'constraint' => 10,
					'null'       => FALSE,
					'unsigned'   => TRUE
				),
				'group_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'null'       => FALSE,
					'unsigned'   => TRUE
				)
			)
		);
		ee()->dbforge->add_key(array('layout_id', 'group_id'), TRUE);
		ee()->smartforge->create_table('layout_publish_member_groups');

		ee()->smartforge->add_column(
			'layout_publish',
			array(
				'layout_name' => array(
					'type'       => 'varchar',
					'constraint' => 50,
					'null'       => FALSE
				),
			)
		);

		$layouts = ee()->db->select('layout_id, member_group, layout_name, field_layout')
			->get('layout_publish')
			->result_array();

		if ( ! empty($layouts))
		{
			foreach ($layouts as $index => $layout)
			{
				ee()->db->insert('layout_publish_member_groups', array(
					'layout_id' => $layout['layout_id'],
					'group_id' => $layout['member_group']
				));

				$layouts[$index]['layout_name'] = 'Layout ' . $layout['layout_id'];

				$old_field_layout = unserialize($layout['field_layout']);
				$new_field_layout = array();

				foreach ($old_field_layout as $tab_id => $old_tab)
				{
					$tab = array(
						'id' => $tab_id,
						'name' => $old_tab['_tab_label'],
						'visible' => TRUE,
						'fields' => array()
					);

					unset($old_tab['_tab_label']);

					foreach ($old_tab as $field => $info)
					{
						if (is_numeric($field))
						{
							$field = 'field_id_' . $field;
						}
						elseif ($field == 'category')
						{
							$field = 'categories';
						}
						elseif ($field == 'new_channel')
						{
							$field = 'channel_id';
						}
						elseif ($field == 'author')
						{
							$field = 'author_id';
						}
						elseif ($field == 'options')
						{
							$tab['fields'][] = array(
								'field' => 'sticky',
								'visible' => $info['visible'],
								'collapsed' => $info['collapse']
							);
							$tab['fields'][] = array(
								'field' => 'allow_comments',
								'visible' => $info['visible'],
								'collapsed' => $info['collapse']
							);
							continue;
						}

						$tab['fields'][] = array(
							'field' => $field,
							'visible' => $info['visible'],
							'collapsed' => $info['collapse']
						);
					}

					$new_field_layout[] = $tab;
				}

				$layouts[$index]['field_layout'] = serialize($new_field_layout);
			}

			ee()->db->update_batch('layout_publish', $layouts, 'layout_id');
		}

		ee()->smartforge->drop_column('layout_publish', 'member_group');
	}

	/**
	 * Transitioning away from our old MySQL Timestamp format to a Unix epoch
	 * for the edit_date column of channel_titles
	 *
	 * @return void
	 */
	private function _update_entry_edit_date_format()
	{
		$fields = ee()->db->field_data('channel_titles');
		foreach ($fields as $field)
		{
			if ($field->name == 'edit_date')
			{
				// Prior to 3.0.0 this column is a bigint if it is now an int
				// then this method has already run and we need to return
				if ($field->type == 'int')
				{
					return;
				}

				break;
			}
		}

		ee()->db->query("SET time_zone = '+0:00';");
		ee()->db->query("UPDATE exp_channel_titles SET edit_date=UNIX_TIMESTAMP(edit_date);");
		ee()->db->query("SET time_zone = @@global.time_zone;");

		ee()->smartforge->modify_column('channel_titles', array(
			'edit_date' => array(
				'type' => 'int',
				'constraint' => 10,
			)
		));
	}

	/**
	 * Changes default name for status groups from Statuses to Default
	 *
	 * @return void
	 */
	private function _rename_default_status_groups()
	{
		ee()->db->where('group_name', 'Statuses')
			->set('group_name', 'Default')
			->update('status_groups');
	}

	/**
	 * Combines all CAPTCHA settings into one on/off switch; if a site has
	 * CAPTCHA turned on for any form, we'll turn CAPTCHA on for the whole site
	 *
	 * @return void
	 */
	private function _centralize_captcha_settings()
	{
		// Prevent this from running again
		if ( ! ee()->db->field_exists('comment_use_captcha', 'channels'))
		{
			return;
		}

		// First, let's see which sites have CAPTCHA turned on for Channel Forms
		// or comments, and mark those sites as needing CAPTCHA required
		$site_ids_query = ee()->db->select('channels.site_id')
			->distinct()
			->where('channels.comment_use_captcha', 'y')
			->or_where('channel_form_settings.require_captcha', 'y')
			->join(
				'channel_form_settings',
				'channels.channel_id = channel_form_settings.channel_id',
				'left')
			->get('channels')
			->result();

		$sites_require_captcha = array();

		foreach ($site_ids_query as $site)
		{
			$sites_require_captcha[] = $site->site_id;
		}

		// Get all site IDs; this is for eventually comparing against the site
		// IDs we have collected to see which sites should have CAPTCHA turned
		// OFF, but we also need to loop through each site to see if any other
		// forms have CAPTCHA turned on
		$all_site_ids_query = ee()->db->select('site_id')
			->get('sites')
			->result();

		$all_site_ids = array();
		foreach ($all_site_ids_query as $site)
		{
			$all_site_ids[] = $site->site_id;
		}

		$msm_config = new MSM_Config();

		foreach ($all_site_ids as $site_id)
		{
			// Skip sites we're already requiring CAPTCHA on
			if (in_array($site_id, $sites_require_captcha))
			{
				continue;
			}

			$msm_config->site_prefs('', $site_id);

			if ($msm_config->item('use_membership_captcha') == 'y' OR
				$msm_config->item('email_module_captchas') == 'y')
			{
				$sites_require_captcha[] = $site_id;
			}
		}

		// Diff all site IDs against the ones we're requiring CAPTCHA for
		// to get a list of sites we're NOT requiring CAPTCHA for
		$sites_do_not_require_captcha = array_diff($all_site_ids, $sites_require_captcha);

		// Add the new preferences
		// These sites require CAPTCHA
		if ( ! empty($sites_require_captcha))
		{
			ee()->config->update_site_prefs(array('require_captcha' => 'y'), $sites_require_captcha);
		}

		// These sites do NOT require CAPTCHA
		if ( ! empty($sites_do_not_require_captcha))
		{
			ee()->config->update_site_prefs(array('require_captcha' => 'n'), $sites_do_not_require_captcha);
		}

		// And finally, drop the old columns and remove old config items
		ee()->smartforge->drop_column('channels', 'comment_use_captcha');
		ee()->smartforge->drop_column('channel_form_settings', 'require_captcha');

		$msm_config->remove_config_item(array('use_membership_captcha', 'email_module_captchas'));
	}

	/**
	 * Adds columns to the members table as needed
	 *
	 * @return void
	 */
	private function _update_members_table()
	{
		if ( ! ee()->db->field_exists('bookmarklets', 'members'))
		{
			ee()->smartforge->add_column(
				'members',
				array(
					'bookmarklets' => array(
						'type'    => 'TEXT',
						'null'    => TRUE
					)
				)
			);
		}

		if ( ! ee()->db->field_exists('rte_enabled', 'members'))
		{
			ee()->smartforge->add_column(
				'members',
				array(
					'rte_enabled' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'y'
					)
				)
			);
		}

		if ( ! ee()->db->field_exists('rte_toolset_id', 'members'))
		{
			ee()->smartforge->add_column(
				'members',
				array(
					'rte_toolset_id' => array(
						'type'       => 'INT(10)',
						'null'       => FALSE,
						'default'    => '0'
					)
				)
			);
		}
	}

	/**
	 * Adjusts the CSS class for some standard buttons
	 *
	 * @return void
	 */
	private function _update_html_buttons()
	{
		$data = array(
			'b'          => 'html-bold',
			'i'          => 'html-italic',
			'ul'         => 'html-order-list',
			'ol'         => 'html-order-list',
			'a'          => 'html-link',
			'img'        => 'html-upload',
			'blockquote' => 'html-quote',
		);

		foreach ($data as $tag => $class)
		{
			ee()->db->where('tag_name', $tag)
				->set('classname', $class)
				->update('html_buttons');
		}
	}

	/**
	 * Removes the rel_path column from the exp_files table
	 *
	 * @return void
	 */
	private function _update_files_table()
	{
		ee()->smartforge->drop_column('files', 'rel_path');
	}

	/**
	 * Adds columns to the upload prefs table as needed
	 *
	 * @return void
	 */
	private function _update_upload_prefs_table()
	{
		if ( ! ee()->db->field_exists('module_id', 'upload_prefs'))
		{
			ee()->smartforge->add_column(
				'upload_prefs',
				array(
					'module_id' => array(
						'type'    => 'INT(4)',
						'null'    => TRUE,
					)
				)
			);
		}
	}

	/**
	 * Adds member image directories (avatars, photos, etc...) as upload
	 * directories
	 *
	 * @access private
	 * @return void
	 */
	private function _update_upload_directories()
	{
		$module = ee('Model')->get('Module')->filter('module_name', 'Member')->first();

		// Bail if the member module isn't installed
		if (empty($module))
		{
			return TRUE;
		}

		// Install member upload directories
		$site_id = ee()->config->item('site_id');
		$member_directories = array();

		if (ee()->config->item('enable_avatars') == 'y'
			&& empty(ee('Model')->get('UploadDestination')->filter('name', 'Avatars')->first()))
		{
			$member_directories['Avatars'] = array(
				'server_path' => ee()->config->item('avatar_path'),
				'url' => ee()->config->item('avatar_url'),
				'allowed_types' => 'img',
				'max_width' => ee()->config->item('avatar_max_width'),
				'max_height' => ee()->config->item('avatar_max_height'),
				'max_size' => ee()->config->item('avatar_max_kb'),
			);
		}

		if (ee()->config->item('enable_photos') == 'y'
			&& empty(ee('Model')->get('UploadDestination')->filter('name', 'Member Photos')->first()))
		{
			$member_directories['Member Photos'] = array(
				'server_path' => ee()->config->item('photo_path'),
				'url' => ee()->config->item('photo_url'),
				'allowed_types' => 'img',
				'max_width' => ee()->config->item('photo_max_width'),
				'max_height' => ee()->config->item('photo_max_height'),
				'max_size' => ee()->config->item('photo_max_kb'),
			);
		}

		if (ee()->config->item('allow_signatures') == 'y'
			&& empty(ee('Model')->get('UploadDestination')->filter('name', 'Signature Attachments')->first()))
		{
			$member_directories['Signature Attachments'] = array(
				'server_path' => ee()->config->item('sig_img_path'),
				'url' => ee()->config->item('sig_img_url'),
				'allowed_types' => 'img',
				'max_width' => ee()->config->item('sig_img_max_width'),
				'max_height' => ee()->config->item('sig_img_max_height'),
				'max_size' => ee()->config->item('sig_img_max_kb'),
			);
		}

		if (empty(ee('Model')->get('UploadDestination')->filter('name', 'Signature Attachments')->first()))
		{
			$member_directories['PM Attachments'] = array(
				'server_path' => ee()->config->item('prv_msg_upload_path'),
				'url' => str_replace('avatars', 'pm_attachments', ee()->config->item('avatar_url')),
				'allowed_types' => 'img',
				'max_size' => ee()->config->item('prv_msg_attach_maxsize')
			);
		}

		foreach ($member_directories as $name => $dir)
		{
			$directory = ee('Model')->make('UploadDestination');
			$directory->site_id = $site_id;
			$directory->name = $name;
			$directory->removeNoAccess();
			$directory->setModule($module);

			foreach ($dir as $property => $value)
			{
				$directory->$property = $value;
			}

			$directory->save();

			if (is_readable($dir['server_path']))
			{
				// Insert Files
				$files = scandir($dir['server_path']);

				foreach ($files as $filename)
				{
					$path = $dir['server_path'] . $filename;

					if ($filename != 'index.html' && is_file($path))
					{
						$time = time();
						$file = ee('Model')->make('File');
						$file->site_id = $site_id;
						$file->upload_location_id = $directory->id;
						$file->uploaded_by_member_id = 1;
						$file->modified_by_member_id = 1;
						$file->title = $filename;
						$file->file_name = $filename;
						$file->upload_date = $time;
						$file->modified_date = $time;
						$file->mime_type = mime_content_type($path);
						$file->file_size = filesize($path);
						$file->save();
					}
				}
			}
		}

		return TRUE;
	}

	/**
	 * Plugins that affect text formatting must now denote they do so,
	 * ergo the field_formatting table is no longer needed
	 */
	private function _drop_field_formatting_table()
	{
		ee()->smartforge->drop_table('field_formatting');
	}
}
/* END CLASS */

/* End of file ud_300.php */
/* Location: ./system/expressionengine/installer/updates/ud_300.php */
