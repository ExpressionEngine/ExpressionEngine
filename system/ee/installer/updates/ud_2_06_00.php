<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.6
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
 * @link		http://expressionengine.com
 */
class Updater {

	public $version_suffix = '';

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	// --------------------------------------------------------------------

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
				'_add_template_name_to_dev_log',
				'_drop_dst',
				'_update_timezone_column_lengths',
				'_update_session_table',
				'_update_actions_table',
				'_update_specialty_templates',
				'_update_relationship_fieldtype',
				'_update_relationship_table',
				'_update_relationship_data',
				'_update_relationship_tags',
				'_schema_cleanup'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * update Session table
	 *
	 * @return TRUE
	 */
	private function _add_template_name_to_dev_log()
	{
		ee()->smartforge->add_column(
			'developer_log',
			array(
				'template_id' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				),
				'template_name' => array(
					'type'			=> 'varchar',
					'constraint'	=> 100
				),
				'template_group' => array(
					'type'			=> 'varchar',
					'constraint'	=> 100
				),
				'addon_module' => array(
					'type'			=> 'varchar',
					'constraint'	=> 100
				),
				'addon_method' => array(
					'type'			=> 'varchar',
					'constraint'	=> 100
				),
				'snippets' => array(
					'type'			=> 'text'
				)
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Drop DST columns!
	 */
	private function _drop_dst()
	{
			ee()->smartforge->drop_column('members', 'daylight_savings');

			ee()->smartforge->drop_column('channel_titles', 'dst_enabled');

			ee()->smartforge->drop_column('channel_entries_autosave', 'dst_enabled');
	}

	// --------------------------------------------------------------------

	/**
	 * We need to store PHP timezone identifiers in the database instead of
	 * our shorthand names so that PHP can properly localize dates, so we
	 * need to increase the column lengths anywhere timezones are stored
	 */
	private function _update_timezone_column_lengths()
	{
		ee()->smartforge->modify_column(
			'members',
			array(
				'timezone' => array(
					'name' 			=> 'timezone',
					'type' 			=> 'varchar',
					'constraint' 	=> 50,
					'null'			=> FALSE
				)
			)
		);

		// Get all date fields, we'll need to update their timezone column
		// lengths in the channel_data table
		$date_fields = ee()->db
			->select('field_id')
			->get_where(
				'channel_fields',
				array('field_type' => 'date')
			)->result_array();

		foreach ($date_fields as $field)
		{
			$field_name = 'field_dt_'.$field['field_id'];

			ee()->smartforge->modify_column(
				'channel_data',
				array(
					$field_name => array(
						'name' 			=> $field_name,
						'type' 			=> 'varchar',
						'constraint' 	=> 50
					)
				)
			);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update Session table
	 *
	 * We duplicate this from the 2.5.4 update because the changes weren't
	 * made to the schema file and therefore aren't present for new installs
	 * of 2.5.4 or 2.5.5
	 */
	private function _update_session_table()
	{
		ee()->smartforge->add_column(
			'sessions',
			array(
				'fingerprint' => array(
					'type'			=> 'varchar',
					'constraint'	=> 40
				),
				'sess_start' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			),
			'user_agent'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Update the Actions Table
	 *
	 * Required for the changes to the reset password flow.  Removed
	 * one old action and added two new ones.
	 */
	private function _update_actions_table()
	{
		// Update two old actions that we no longer need to be actions
		// with the names of the new methods.

		// For this one, the method was renamed.  It still mostly does
		// the same thing and needs to be an action.
		ee()->db->where('method', 'retrieve_password')
			->where('class', 'Member')
			->update('actions', array('method'=>'send_reset_token'));
		// For this one the method still exists, but is now a form.  It needs
		// to be renamed to the new processing method.
		ee()->db->where('method', 'reset_password')
			->where('class', 'Member')
			->update('actions', array('method'=>'process_reset_password'));
	}

	// -------------------------------------------------------------------

	/**
	 * Update Specialty Templates
	 *
	 * Required for the changes to the reset password flow.  We needed to
	 * slightly change the language of the related e-mail template to fit
	 * the new flow.
	 */
	private function _update_specialty_templates()
	{
		$data = array(
			'template_data'=>'{name},

To reset your password, please go to the following page:

{reset_url}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}');

		ee()->db->where('template_name', 'forgot_password_instructions')
			->update('specialty_templates', $data);

	}

	// -------------------------------------------------------------------

	/**
	 * Update the Fieldtype and Channel Fields Tables for Relationships
 	 *
	 * Updates the fieldtypes and channel_fields tables to
	 * use the new relationships field, instead of the old one.
	 * Does its best to hang on to the settings of the old field.
	 *
	 * @return	void
	 */
	private function _update_relationship_fieldtype()
	{
		// UPDATE TABLE `exp_fieldtypes` SET name='relationships' WHERE name='rel';
		ee()->db->where('name', 'rel');
		ee()->db->update('fieldtypes', array('name'=>'relationship'));

		// UPDATE TABLE `exp_channel_fields` set field_type='relationships' where field_type='rel';
		ee()->db->where('field_type', 'rel');
		ee()->db->update('channel_fields', array('field_type'=>'relationship'));

		ee()->db->where('field_type', 'relationship');
		$channel_fields = ee()->db->get('channel_fields');
		foreach ($channel_fields->result_array() as $channel_field)
		{
			$settings = array(
				'channels'		=> array($channel_field['field_related_id']),
				'expired'		=> 0,
				'future'		=> 0,
				'categories'	=> array(),
				'authors'		=> array(),
				'statuses'		=> array(),
				'limit'			=> $channel_field['field_related_max'],
				'order_field'	=> $channel_field['field_related_orderby'],
				'order_dir'		=> $channel_field['field_related_sort'],
				'allow_multiple'	=> 0
			);

			ee()->db->where('field_id', $channel_field['field_id']);
			ee()->db->update(
				'channel_fields',
				array('field_settings'=>base64_encode(serialize($settings))));

		}

	}

	// -------------------------------------------------------------------

	/**
 	 * Update the Relationships Table
	 *
	 * Update the relationships table for the new Relationships.  Pull
	 * data from Channel_data, and while we're at it, clean out the
	 * old relationships data that we no longer use.
	 *
	 * @return	void
	 */
	private function _update_relationship_table()
	{

		// ALTER TABLE `exp_relationships` CHANGE COLUMN `rel_id` `relationship_id` int(10) unsigned NOT NULL DEFAULT 0;
		ee()->smartforge->modify_column(
			'relationships',
			array(
				'rel_id' => array(
					'name'			=> 'relationship_id',
					'type'			=> 'int',
					'constraint'	=> 6,
					'unsigned'		=> TRUE,
					'auto_increment'=> TRUE
				)
			)
		);

		// ALTER TABLE `exp_relationships` CHANGE COLUMN `rel_parent_id` `parent_id` int(10) unsigned NOT NULL DEFAULT 0;
		ee()->smartforge->modify_column(
			'relationships',
			array(
				'rel_parent_id' => array(
					'name'			=> 'parent_id',
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			)
		);

		// ALTER TABLE `exp_relationships` CHANGE COLUMN `rel_child_id` `child_id` int(10) unsigned NOT NULL DEFAULT 0;
		ee()->smartforge->modify_column(
			'relationships',
			array(
				'rel_child_id' => array(
					'name'			=> 'child_id',
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			)
		);

		// ALTER TABLE `exp_relationships` DROP COLUMN `rel_type`;
		ee()->smartforge->drop_column('relationships', 'rel_type');

		// ALTER TABLE `exp_relationships` DROP COLUMN `rel_data`;
		ee()->smartforge->drop_column('relationships', 'rel_data');

		// ALTER TABLE `exp_relationships` DROP COLUMN `reverse_rel_data`;
		ee()->smartforge->drop_column('relationships', 'reverse_rel_data');

		// ALTER TABLE `exp_relationships` ADD COLUMN field_id int unsigned;
		// ALTER TABLE exp_relationships ADD COLUMN `order` int unsigned;
		ee()->smartforge->add_column(
			'relationships',
			array(
				'field_id' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				),
				'order' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			),
			'child_id'
		);

		// alter table exp_relationships ADD KEY `field_id` (`field_id`);
		ee()->smartforge->add_key('relationships', 'field_id');

		// Wipe out the old, unsed relationship data.
		foreach (array( 'field_related_to', 'field_related_id', 'field_related_max',
			'field_related_orderby', 'field_related_sort') as $column)
		{
			ee()->smartforge->drop_column('channel_fields', $column);
		}
	}

	// -------------------------------------------------------------------

	/**
	 *
	 */
	private function _update_relationship_data()
	{
		ee()->db->where('field_type', 'relationship');
		$fields = ee()->db->get('channel_fields');

		foreach ($fields->result_array() as $field)
		{
			$this->_update_single_relationship_field($field);
		}
	}

	/**
	 *
	 */
	private function _update_single_relationship_field(array $field)
	{
		$relationships = ee()->db->dbprefix('relationships');
		$channel_data = ee()->db->dbprefix('channel_data');

		$sql = 'UPDATE ' . $relationships . '
			JOIN ' . $channel_data . '
			ON (' . $relationships . '.relationship_id = ' . $channel_data . '.field_id_' . $field['field_id'] . ')
			SET ' . $relationships . '.field_id = ' . $field['field_id'];
		ee()->db->query($sql);


		ee()->db->update('channel_data', array('field_id_' . $field['field_id']=> NULL));
	}

	// -------------------------------------------------------------------

	/**
	 * Update all Relationship Tags in All Templates
	 *
	 * Examine the templates saved in the database and in file.  Search for all
	 * instances of 'related_entries' and 'reverse_related_entries' replacing
	 * them with the appropriate new Relationships tag.  'related_entries' tags
	 * are replaced by the named field pair and 'reverse_related_entries' are
	 * replaced by a 'parents' tag.
	 *
	 * @return void
	 */
	private function _update_relationship_tags()
	{
		ee()->remove('template');
		require_once(APPPATH . 'libraries/Template.php');
		ee()->set('template', new Installer_Template());

		// Since we don't have consistent destructors,
		// we'll keep this here.
		$installer_config = ee()->config;
		ee()->remove('config');
		ee()->set('config', new MSM_Config());

		// We need to figure out which template to load.
		// Need to check the edit date.
		$templates = ee()->template_model->fetch_last_edit(array(), TRUE);

		// related_entries
		// Foreach template
		foreach($templates as $template)
		{
			// If there aren't any related entries tags, then we don't need to continue.
			if (strpos($template->template_data, 'related_entries') === FALSE
				&& strpos($template->template_data, 'reverse_related_entries') === FALSE)
			{
				continue;
			}

			// Find the {related_entries} and {reverse_related_entries} tags
			// (match pairs and wrapped tags)
			$template->template_data = ee()->template->replace_related_entries_tags($template->template_data);

			// save the template
			// if saving to file, save the file
			if ($template->loaded_from_file)
			{
				ee()->template_model->save_to_file($template);
			}
			else
			{
				ee()->template_model->save_to_database($template);
			}
		}

		ee()->remove('config');
		ee()->set('config', $installer_config);
	}


	// --------------------------------------------------------------------------

	/**
	 *
	 * Cleaning up some discrepancies between a fresh installation and an
	 * upgraded installation.
	 *
	 */
	private function _schema_cleanup()
	{
		$fields = array(
			'member_groups'	=> array('type' => 'varchar',	'constraint' => 255,	'null' => FALSE,	'default'=> 'all')
		);

		ee()->smartforge->modify_column('accessories', $fields);


		$fields = array(
				'channel_description'		=> array('type' => 'varchar',	'constraint' => 255,	'null' => TRUE),
				'channel_auto_link_urls'	=> array('type' => 'char',		'constraint' => 1,		'null' => FALSE,	'default' => 'n'),
				'default_entry_title'		=> array('type' => 'varchar',	'constraint' => 100,	'null' => TRUE),
				'url_title_prefix'			=> array('type' => 'varchar',	'constraint' => 80,		'null' => TRUE),
		);

		ee()->smartforge->modify_column('channels', $fields);


		$fields = array(
				'recent_comment_date'		=> array('type' => 'int',		'constraint' => 10,		'null' => TRUE),
		);

		ee()->smartforge->modify_column('channel_entries_autosave', $fields);


		$fields = array(
			'timestamp'	=> array('type' => 'int',	'constraint' => 10,	'unsigned' => TRUE,	'null' => FALSE),
			'viewed'	=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')
		);

		ee()->smartforge->modify_column('developer_log', $fields);


		$fields = array(
			'wm_hor_offset'	=> array('type' => 'int',	'constraint' => 4,	'unsigned' => TRUE),
			'wm_vrt_offset'	=> array('type' => 'int',	'constraint' => 4,	'unsigned' => TRUE)
		);

		ee()->smartforge->modify_column('file_watermarks', $fields);


		$fields = array(
			'file_hw_original' => array('type' => 'varchar',	'constraint' => 20, 'null' => FALSE, 'default' => '')
		);

		ee()->smartforge->modify_column('files', $fields);


		$fields = array(
			'can_admin_accessories' => array('type' => 'char',	'constraint' => 1, 'null' => FALSE, 'default' => 'n')
		);

		ee()->smartforge->modify_column('member_groups', $fields);


		$fields = array(
			'user_agent'	=> array('type' => 'VARCHAR',	'constraint' => 120,	'null' => FALSE)
		);

		ee()->smartforge->modify_column('password_lockout', $fields);

		$fields = array(
			'password'			=> array('type' => 'VARCHAR',	'constraint' => 128,	'null' => FALSE),
			'total_entries'		=> array('type' => 'mediumint',	'constraint' => 8,		'unsigned' => TRUE,	'null' => FALSE, 'default' => 0),
			'total_comments'	=> array('type' => 'mediumint',	'constraint' => 8,		'unsigned' => TRUE,	'null' => FALSE, 'default' => 0),
		);

		ee()->smartforge->modify_column('members', $fields);


		$fields = array(
			'session_id'	=> array('type' => 'VARCHAR',	'constraint' => 40,	'null' => FALSE,	'default' => 0)
		);

		ee()->smartforge->modify_column('security_hashes', $fields);


		ee()->db->truncate('sessions');

		$fields = array(
			'user_agent'	=> array('type' => 'VARCHAR',	'constraint' => 120,	'null' => FALSE),
			'fingerprint'	=> array('type' => 'VARCHAR',	'constraint' => 40,		'null' => FALSE),
		);

		ee()->smartforge->modify_column('sessions', $fields);


		$fields = array(
			'site_system_preferences'	=> array('type' => 'mediumtext',	'null' => FALSE),
		);

		ee()->smartforge->modify_column('sites', $fields);


		$fields = array(
			'last_author_id'	=> array('type' => 'int',	'constraint' => 10,	'unsigned' => TRUE,	'null' => FALSE, 'default' => 0),
		);

		ee()->smartforge->modify_column('templates', $fields);


		$fields = array(
			'server_path'	=> array('type' => 'varchar',	'constraint' => 255,	'null' => FALSE, 'default' => ''),
		);

		ee()->smartforge->modify_column('upload_prefs', $fields);


		ee()->smartforge->add_key('template_groups', 'group_name', 'group_name_idx');
		ee()->smartforge->add_key('template_groups', 'group_order', 'group_order_idx');


		$drop_column = array(
			'category_groups'			=> 'is_user_blog',
			'channel_titles'			=> 'pentry_id',
			'channel_entries_autosave'	=> 'pentry_id',
			'forum_topics'				=> 'pentry_id',
			'upload_prefs'				=> 'is_user_blog',
		);

		foreach ($drop_column as $table => $column)
		{
			ee()->smartforge->drop_column($table, $column);
		}
	}

}
/* END CLASS */

// EOF
