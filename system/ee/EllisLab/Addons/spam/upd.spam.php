<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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

	// --------------------------------------------------------------------

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
			'site_id'       => array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE),
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

	// --------------------------------------------------------------------

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

		// migrate any comments trapped to the new schema
		$this->updateCommentSpam_2_00_00();

		// drop old columns not used anymore
		ee()->smartforge->drop_column('spam_trap', 'file');
		ee()->smartforge->drop_column('spam_trap', 'class');
		ee()->smartforge->drop_column('spam_trap', 'approve');
		ee()->smartforge->drop_column('spam_trap', 'remove');
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

		ee()->db->update_batch('spam_trap', $update, 'trap_id');

		// cleanup
		if ( ! empty($delete_ids))
		{
			ee()->db->where_in('trap_id', $delete_ids)->delete('spam_trap');
		}
	}
}

// EOF
