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
 * ExpressionEngine Mailinglist Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Mailinglist_upd {

	var $version = '3.2';

	function Mailinglist_upd()
	{
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
		ee()->dbforge->add_field(array(
			'list_id' => array(
				'type'           => 'int',
				'constraint'     => 7,
				'unsigned'       => TRUE,
				'null'           => FALSE,
				'auto_increment' => TRUE
			),
			'list_name' => array(
				'type'       => 'varchar',
				'constraint' => '40',
				'null'       => FALSE
			),
			'list_title' => array(
				'type'       => 'varchar',
				'constraint' => '100',
				'null'       => FALSE
			),
			'list_template' => array(
				'type' => 'text',
				'null' => FALSE
			)
		));
		ee()->dbforge->add_key('list_id', TRUE);
		ee()->dbforge->add_key('list_name');
		ee()->dbforge->create_table('mailing_lists', TRUE);

		ee()->dbforge->add_field(array(
			'user_id' => array(
				'type'           => 'int',
				'constraint'     => 10,
				'unsigned'       => TRUE,
				'null'           => FALSE,
				'auto_increment' => TRUE
			),
			'list_id' => array(
				'type'       => 'int',
				'constraint' => 7,
				'unsigned'   => TRUE,
				'null'       => FALSE,
			),
			'authcode' => array(
				'type'       => 'varchar',
				'constraint' => '10',
				'null'       => FALSE
			),
			'email' => array(
				'type'       => 'varchar',
				'constraint' => '75',
				'null'       => FALSE
			),
			'ip_address' => array(
				'type'       => 'varchar',
				'constraint' => '45',
				'null'       => FALSE
			),
		));
		ee()->dbforge->add_key('user_id', TRUE);
		ee()->dbforge->add_key('list_id');
		ee()->dbforge->create_table('mailing_list', TRUE);

		ee()->dbforge->add_field(array(
			'queue_id' => array(
				'type'           => 'int',
				'constraint'     => 10,
				'unsigned'       => TRUE,
				'null'           => FALSE,
				'auto_increment' => TRUE
			),
			'email' => array(
				'type'       => 'varchar',
				'constraint' => '75',
				'null'       => FALSE
			),
			'list_id' => array(
				'type'       => 'int',
				'constraint' => 7,
				'unsigned'   => TRUE,
				'null'       => FALSE,
				'default'    => 0
			),
			'authcode' => array(
				'type'       => 'varchar',
				'constraint' => '10',
				'null'       => FALSE
			),
			'date' => array(
				'type'       => 'int',
				'constraint' => '10',
				'null'       => FALSE
			),
		));
		ee()->dbforge->add_key('queue_id', TRUE);
		ee()->dbforge->create_table('mailing_list_queue', TRUE);

		if ( ! function_exists('mailinglist_template'))
		{
			if ( ! file_exists(APPPATH.'language/'.ee()->config->item('deft_lang').'/email_data.php'))
			{
				return FALSE;
			}

			require APPPATH.'language/'.ee()->config->item('deft_lang').'/email_data.php';
		}

		ee()->db->insert('mailing_lists', array(
			'list_name'     => 'default',
			'list_title'    => 'Default Mailing List',
			'list_template' => addslashes(mailinglist_template())
		));

		ee()->db->insert('modules', array(
			'module_name'    => 'Mailinglist',
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		));

		ee()->db->insert('actions', array(
			'class'  => 'Mailinglist',
			'method' => 'insert_new_email'
		));

		ee()->db->insert('actions', array(
			'class'  => 'Mailinglist',
			'method' => 'authorize_email'
		));

		ee()->db->insert('actions', array(
			'class'  => 'Mailinglist',
			'method' => 'unsubscribe'
		));

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
		$query = ee()->db->get_where('modules', array('module_name' => 'Mailinglist'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Mailinglist');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Mailinglist');
		ee()->db->delete('actions');

		ee()->db->where('class', 'Mailinglist_mcp');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('mailing_lists');
		ee()->dbforge->drop_table('mailing_list');
		ee()->dbforge->drop_table('mailing_list_queue');

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current='')
	{
		if (version_compare($current, '3.0', '<'))
		{
			ee()->smartforge->modify_column('mailing_list', array(
				'user_id' => array(
					'name'           => 'user_id',
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'auto_increment' => TRUE
				)
			));
			ee()->smartforge->add_key('mailing_list', 'user_id', 'PRIMARY');

			// Add queue_id
			ee()->smartforge->add_column('mailing_list_queue', array(
				'queue_id' => array(
					'type'       => 'int',
					'constraint' => '10',
					'unsigned'   => TRUE
				)
			));
			ee()->smartforge->add_key('mailing_list_queue', 'queue_id', 'PRIMARY');

			// Add ip_address
			ee()->smartforge->add_column('mailing_list', array(
				'ip_address' => array(
					'type'       => 'varchar',
					'constraint' => '45'
				)
			));
		}

		if (version_compare($current, '3.1', '<'))
		{
			// Update ip_address column
			ee()->smartforge->modify_column(
				'mailing_list',
				array(
					'ip_address' => array(
						'name'       => 'ip_address',
						'type'       => 'varchar',
						'constraint' => '45'
					)
				)
			);
		}

		if (version_compare($current, '3.2', '<'))
		{
			foreach (array('mailing_list', 'mailing_list_queue') as $table)
			{
				ee()->smartforge->modify_column(
					$table,
					array(
						'email' => array(
							'name'       => 'email',
							'type'       => 'varchar',
							'constraint' => '75',
							'null'       => FALSE
						)
					)
				);
			}
		}

		return TRUE;
	}
}
// END CLASS

/* End of file upd.mailinglist.php */
/* Location: ./system/expressionengine/modules/mailinglist/upd.mailinglist.php */
