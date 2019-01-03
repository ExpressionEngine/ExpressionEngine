<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Spam Module update class
 */
class Spam_upd {

	public $version;
	private $name = 'Spam';

	function __construct()
	{
		$addon = ee('Addon')->get('spam');
		$this->version = $addon->getVersion();

		ee()->load->dbforge();
		ee()->load->library('smartforge');
	}

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		$data = array(
			'module_name' => 'Spam' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		);

		ee()->db->insert('modules', $data);

		$fields = array(
			'kernel_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'name'		=> array('type' => 'varchar' , 'constraint' => '32'),
			'count'			=> array('type' => 'int' , 'constraint' => '10')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('kernel_id', TRUE);
		ee()->dbforge->create_table('spam_kernels');

		$fields = array(
			'vocabulary_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'kernel_id'		=> array('type' => 'int', 'constraint' => '10'),
			'term'			=> array('type' => 'text'),
			'count'			=> array('type' => 'int' , 'constraint' => '10')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('vocabulary_id', TRUE);
		ee()->dbforge->create_table('spam_vocabulary');

		$fields = array(
			'parameter_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'kernel_id'		=> array('type' => 'int', 'constraint' => '10'),
			'index'			=> array('type' => 'int', 'constraint' => '10'),
			'term'			=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE),
			'class'			=> array('type' => 'ENUM("spam","ham")'),
			'mean'			=> array('type' => 'double'),
			'variance'		=> array('type' => 'double')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('parameter_id', TRUE);
		ee()->dbforge->create_table('spam_parameters');

		$fields = array(
			'training_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'kernel_id'		=> array('type' => 'int', 'constraint' => '10'),
			'author'		=> array('type' => 'int', 'constraint' => '10'),
			'source'		=> array('type' => 'text'),
			'type'			=> array('type' => 'varchar', 'constraint' => '32'),
			'class'			=> array('type' => 'ENUM("spam","ham")')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('training_id', TRUE);
		ee()->dbforge->create_table('spam_training');

		$fields = array(
			'trap_id'       => array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'site_id'       => array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'default' => 1),
			'trap_date'     => array('type' => 'int', 'constraint' => '10', 'null' => FALSE),
			'author_id'     => array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0),
			'ip_address'    => array('type' => 'varchar', 'constraint' => '45'),
			'content_type'  => array('type' => 'varchar', 'constraint' => '50', 'null' => FALSE),
			'document'      => array('type' => 'text', 'null' => FALSE),
			'entity'        => array('type' => 'mediumtext', 'null' => FALSE),
			'optional_data' => array('type' => 'mediumtext'),
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('trap_id', TRUE);
		ee()->dbforge->create_table('spam_trap');

		// Make sure the default kernel is created
		ee('Model')->make('spam:SpamKernel', array('name' => 'default'))->save();

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Spam'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Spam');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Spam');
		ee()->db->delete('actions');

		ee()->db->where('class', 'Spam_mcp');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('spam_vocabulary');
		ee()->dbforge->drop_table('spam_parameters');
		ee()->dbforge->drop_table('spam_training');
		ee()->dbforge->drop_table('spam_trap');
		ee()->dbforge->drop_table('spam_kernels');

		// remove any installed menu items
		$menu_items = ee('Model')->get('MenuItem')
			->filter('type', 'addon')
			->filter('data', 'Spam_ext')
			->all();

		$menu_items->delete();

		return TRUE;
	}

	function update($current='')
	{
		if (version_compare($current, '2.0.0', '<'))
		{
			$this->do_2_00_00_update();
		}

		return TRUE;
	}

	/**
	 * Do the 2.0.0 update for this module
	 * @return void
	 */
	private function do_2_00_00_update()
	{
		ee()->smartforge->add_column(
			'spam_trap',
			array(
				'content_type' => array(
					'type' => 'varchar',
					'constraint' => '50',
					'null' => FALSE,
				),
				'site_id' => array(
					'type' => 'int',
					'constraint' => 10,
					'unsigned' => TRUE,
					'null' => FALSE,
					'default' => 1,
				),
			),
			'trap_id'
		);

		ee()->smartforge->add_column(
			'spam_trap',
			array(
				'optional_data' => array(
					'type' => 'mediumtext',
					'null' => TRUE,
				)
			)
		);

		// rename date to trap_date
		// rename author to author_id
		// rename data to entity and increase its size
		ee()->smartforge->modify_column(
			'spam_trap',
			array(
				'date' => array('name' => 'trap_date', 'type' => 'int', 'constraint' => '10', 'null' => FALSE),
				'author' => array('name' => 'author_id', 'type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0),
				'data' => array('name' => 'entity', 'type' => 'mediumtext', 'null' => FALSE),
			)
		);

		// in case this runs multiple times, with partial success
		if (ee()->db->field_exists('file', 'spam_trap'))
		{
			// migrate any comments trapped to the new schema
			$this->updateCommentSpam_2_00_00();

			// migrate any Channel Entries trapped to the new schema
			$this->updateChannelSpam_2_00_00();

			// migrate any legacy Forum
			$this->updateForumSpam_2_00_00();
		}

		// kill the rest, orphaned unusable data. `content_type` won't have values for old items that aren't converted
		ee()->db->where('content_type', '');
		ee()->db->delete('spam_trap');

		// drop old columns not used anymore
		ee()->smartforge->drop_column('spam_trap', 'file');
		ee()->smartforge->drop_column('spam_trap', 'class');
		ee()->smartforge->drop_column('spam_trap', 'approve');
		ee()->smartforge->drop_column('spam_trap', 'remove');

		// add menu extension
		$data = array(
			'class'		=> 'Spam_ext',
			'method'	=> 'addSpamMenu',
			'hook'		=> 'cp_custom_menu',
			'settings'	=> '',
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		ee()->db->insert('extensions', $data);
	}

	/**
	 * Update Comments in the spam trap
	 * Part of this module's 2.0.0 update
	 * @return void
	 */
	private function updateCommentSpam_2_00_00()
	{
		// the model's file is already on the latest version so we need to use the DB directly
		// to access old properties like 'class'
		$trapped_comments = ee()->db->select('trap_id, entity')
			->where('class', 'Comment')
			->get('spam_trap');

		if ($trapped_comments->num_rows() == 0)
		{
			return;
		}

		$comment_ids = array();

		foreach ($trapped_comments->result() as $trapped)
		{
			$comment_meta = unserialize($trapped->entity);
			$comment_ids[] = $comment_meta[0];
		}

		// get the comments so we can save a serialized entity model to the spam trap
		$spam_comments = ee('Model')->get('Comment')
			->filter('comment_id', 'IN', $comment_ids)
			->all();

		if (empty($spam_comments))
		{
			// orphaned garbage in the spam trap prolly
			ee()->db->where('class', 'Comment')->delete('spam_trap');
			return;
		}

		$spam_comments = $spam_comments->indexBy('comment_id');
		$delete_ids = array();
		$update = array();

		// create the update data;
		foreach ($trapped_comments->result() as $trapped)
		{
			$comment = unserialize($trapped->entity);

			// comment doesn't exist? Save it for cleanup
			if ( ! isset($spam_comments[$comment[0]]))
			{
				$delete_ids[] = $trapped->trap_id;
				continue;
			}

			$update[] = array(
				'trap_id' => $trapped->trap_id,
				'content_type' => 'comment',
				'entity' => serialize($spam_comments[$comment[0]]),
			);
		}

		if ( ! empty($update))
		{
			ee()->db->update_batch('spam_trap', $update, 'trap_id');
		}

		// cleanup
		if ( ! empty($delete_ids))
		{
			ee()->db->where_in('trap_id', $delete_ids)->delete('spam_trap');
		}
	}

	/**
	 * Update Channel entries in the spam trap
	 * Part of this module's 2.0.0 update
	 * @return void
	 */
	private function updateChannelSpam_2_00_00()
	{
		// the model's file is already on the latest version so we need to use the DB directly
		// to access old properties like 'class'
		$trapped_entries = ee()->db->select('trap_id, trap_date, author_id, ip_address, entity, document')
			->where('class', 'api_channel_form_channel_entries')
			->get('spam_trap');

		if ($trapped_entries->num_rows() == 0)
		{
			return;
		}

		$delete_ids = array();

		// just in case this update is ran in the context of the control panel
		// ChannelEntry model looks at the session for validating channel ID assignment
		$orig_group_id = ee()->session->userdata('group_id');
		$orig_site_id = ee()->config->item('site_id');

		// set super admins to all channels
		$assigned_channels[1] = ee('Model')->get('Channel')->fields('channel_id')->all()->pluck('channel_id');

		// fetch all the others
		$query = ee()->db->get('channel_member_groups');
		foreach($query->result() as $row)
		{
			$assigned_channels[$row->group_id][] = $row->channel_id;
		}

		foreach ($trapped_entries->result() as $trapped)
		{
			// we're going to delete all of these old traps, regardless of what we do with them
			$delete_ids[] = $trapped->trap_id;

			$entry_data = unserialize($trapped->entity);

			// array(
			// 		postdata,
			// 		channel_id or NULL,
			// 		entry_id, or not set for new entries
			// )
			// If it's an existing entry, we don't have a way to deal with it,
			// HAMing an edit could revert changes made by previous non-spam edits.
			if (isset($entry_data[2]))
			{
				continue;
			}

			$postdata = $entry_data[0];
			$channel_id = $entry_data[1];

			$channel = ee('Model')->get('Channel')
				->with('ChannelFormSettings')
				->filter('channel_id', $channel_id)
				->first();

			if ( ! $channel)
			{
				continue;
			}

			$entry = ee('Model')->make('ChannelEntry');
			$entry->Channel = $channel;
			$entry->ip_address = $trapped->ip_address;

			// Assign defaults based on the channel
			$entry->title = $channel->default_entry_title;
			$entry->versioning_enabled = $channel->enable_versioning;
			$entry->status = $channel->deft_status;
			$entry->author_id = $trapped->author_id;
			$entry->edit_date = ee()->localize->now;

			// guest entries may have been allowed at the time, but not any longer
			if ($entry->author_id == 0 && $channel->ChannelFormSettings->allow_guest_posts != 'y')
			{
				$delete_ids[] = $trapped->trap_id;
				continue;
			}

			if ( ! empty($channel->deft_category))
			{
				$cat = ee('Model')->get('Category', $channel->deft_category)->first();

				if ($cat)
				{
					// set directly so other categories don't get lazy loaded
					// along with our default
					$entry->Categories = $cat;
				}
			}

			// Assign defaults based on the ChannelFormSettings
			if ($channel->ChannelFormSettings)
			{
				$entry->status = ($channel->ChannelFormSettings->default_status) ?: $channel->deft_status;

				// only override if user was not logged in, and guest entries are allowed
				if ($entry->author_id == 0 && $channel->ChannelFormSettings->allow_guest_posts == 'y')
				{
					$entry->author_id = $channel->ChannelFormSettings->default_author;
				}
			}

			// fake out the group ID so the entry will validate properly
			ee()->session->userdata['group_id'] = $entry->Author->group_id;

			// just in case this member group doesn't exist or have channel assignments anymore
			if ( ! isset($assigned_channels[$entry->Author->group_id]))
			{
				continue;
			}
			ee()->session->userdata['assigned_channels'] = $assigned_channels[$entry->Author->group_id];
			ee()->config->set_item('site_id', $entry->Channel->site_id);

			$entry->set($postdata);
			if ( ! isset($postdata['category']) OR empty($postdata['category']))
			{
				$entry->Categories = NULL;
			}

			$result = $entry->validate();

			if ( ! $result->isValid())
			{
				continue;
			}

			// now that that's all out of the way, save it to the trap
			$data = array(
				'content_type'  => 'channel',
				'author_id'     => $entry->author_id,
				'trap_date'     => $trapped->trap_date,
				'ip_address'    => $trapped->ip_address,
				'entity'        => $entry,
				'document'      => $trapped->document,
				'optional_data' => $postdata,
			);

			$trap = ee('Model')->make('spam:SpamTrap', $data);
			$trap->save();
		}

		// set the member group and site back
		ee()->session->userdata['group_id'] = $orig_group_id;
		ee()->config->set_item('site_id', $orig_site_id);

		// cleanup
		if ( ! empty($delete_ids))
		{
			ee()->db->where_in('trap_id', $delete_ids)->delete('spam_trap');
		}
	}

	/**
	 * Update Forum posts in the spam trap
	 * Part of this module's 2.0.0 update
	 * @return void
	 */
	private function updateForumSpam_2_00_00()
	{
		// Legacy forum stores raw SQL. Get rid of all UPDATE queries, since we have no
		// way to determine what order they should run in, and UPDATEs (edits) to topics or posts
		// would be destructive and random
		ee()->db->where('class', 'Forum Post');
		ee()->db->like('entity', 'UPDATE `exp_forum_posts`');
		ee()->db->or_like('entity', 'UPDATE `exp_forum_topics`');
		ee()->db->delete('spam_trap');

		$delete_ids = array();
		$trapped_posts = ee()->db->where('class', 'Forum Post')
			->get('spam_trap');

		foreach ($trapped_posts->result() as $trapped)
		{
			$delete_ids[] = $trapped->trap_id;

			$data = unserialize($trapped->entity);
			$sql = $data[0];

			// now that that's all out of the way, save it to the trap
			// optional data will be empty, we didn't collect it before
			$data = array(
				'content_type'  => 'forum',
				'author_id'     => $trapped->author_id,
				'trap_date'     => $trapped->trap_date,
				'ip_address'    => $trapped->ip_address,
				'entity'        => $sql,
				'document'      => $trapped->document,
				'optional_data' => array('postdata' => array(), 'redirect' => ''),
			);

			$trap = ee('Model')->make('spam:SpamTrap', $data);
			$trap->save();
		}
	}
}

// EOF
