<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license     http://expressionengine.com/user_guide/license.html
 * @link        http://expressionengine.com
 * @since       Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      ExpressionEngine Dev Team
 * @link        http://expressionengine.com
 */
class Updater {

	private $EE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// ------------------------------------------------------------------------

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$this->EE->load->dbforge();
		
		// Kill blogger
		if ($this->EE->db->table_exists('blogger'))
		{
			$this->_transfer_blogger();
			$this->_drop_blogger();
			// remove blogger
		}
		
		// Add batch dir preference to exp_upload_prefs
		$this->_do_upload_pref_update();
		
		// Update category group
		$this->_do_cat_group_update();
		
		// Build file-related tables
		$this->_do_build_file_tables();
		
		return TRUE;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Transfer Blogger configurations to the metaweblog api
	 *
	 * @return void
	 */
	function _transfer_blogger()
	{
		if ( ! $this->EE->db->table_exists('metaweblog_api'))
		{
			require EE_APPPATH.'modules/metaweblog_api/upd.metaweblog_api.php';
			$UPD = new Metaweblog_api_upd();
			$UPD->install();
		}
		
		$qry = $this->EE->db->get('blogger');
		
		foreach ($qry->result() as $row)
		{
			list($channel_id, $custom_field_id) = explode(':', $row->blogger_field_id);
			
			$qry = $this->EE->db->select('field_group')
								->where('channel_id', $channel_id)
								->get('channels');
			
			if ( ! $qry->num_rows())
			{
				// nothing we can do here, that config shouldn't work
				continue;
			}

			$fg_id = $qry->row('field_group');
			
			$data = array(
				'field_group_id' 		=> $fg_id,
				'content_field_id' 		=> $custom_field_id,
				'metaweblog_pref_name' 	=> $row->blogger_pref_name.'_blogger',
				'metaweblog_parse_type'	=> $row->blogger_parse_type,
			);
			
			$this->EE->db->insert('metaweblog_api', $data);
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Drop Blogger Data
	 *
	 * @return void
	 */
	function _drop_blogger()
	{
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Blogger_api'));
		
		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');
		
		$this->EE->db->where('module_name', 'Blogger_api');
		$this->EE->db->delete('modules');
		
		$this->EE->db->where('class', 'Blogger_api');
		$this->EE->db->delete('actions');
		
		$this->EE->dbforge->drop_table('blogger');
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Upload pref table update
	 *
	 * This method adds the batch_location column to the table
	 *
	 * @return void
	 */
	private function _do_upload_pref_update()
	{
		$fields = array(
					'batch_location' 	=> array(
								'type'			=> 'VARCHAR',
								'constraint'	=> 255,
								),
					'cat_group'			=> array(
								'type'			=> 'VARCHAR',
								'constraint'	=> 255
					));

		$this->EE->dbforge->add_column('upload_prefs', $fields);
		
		$fields = array(
					'server_path'	=> array(
								'name'			=> 'server_path',
								'type'			=> 'VARCHAR',
								'constraint'	=> 255
					),
		);
		
		$this->EE->dbforge->modify_column('upload_prefs', $fields);
	}

	// ------------------------------------------------------------------------

	/**
	 *
	 *
	 *
	 */
	private function _do_build_file_tables()
	{
		
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Update exp_category_groups
	 *
	 * Add a column for excluding a group from files or channel group assignment
	 *
	 * @return void
	 */
	private function _do_cat_group_update()
	{
		$fields = array(
					'exclude_group' 	=> array(
								'type'			=> 'TINYINT',
								'constraint'	=> 1,
								'null'			=> FALSE,
								'default'		=> 0
								));

		$this->EE->dbforge->add_column('category_groups', $fields);		
	}

	// ------------------------------------------------------------------------	
	
}
/* END CLASS */

/* End of file ud_220.php */
/* Location: ./system/expressionengine/installer/updates/ud_220.php */