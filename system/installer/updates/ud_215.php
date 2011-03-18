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

	/**
	 * Build the files tables:
	 * 	- watermark
	 * 	- dimensions
	 * 	- categories
	 * 	- files
	 */
	private function _do_build_file_tables()
	{
		$watermark_fields = array(
			'wm_id' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'wm_name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 80
			),
			'wm_type' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'n'
			),
			'wm_image_path' => array(
				'type'				=> 'varchar',
				'constraint'		=> 100
			),
			'wm_test_image_path' => array(
				'type'				=> 'varchar',
				'constraint'		=> 100
			),
			'wm_use_font' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'y'
			),
			'wm_font' => array(
				'type'				=> 'varchar',
				'constraint'		=> 30
			),
			'wm_font_size' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'wm_text' => array(
				'type'				=> 'varchar',
				'constraint'		=> 100
			),
			'wm_vrt_alignment' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'T'
			),
			'wm_hor_alignment' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'L'
			),
			'wm_padding' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'wm_opacity' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'wm_x_offset' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE
			),
			'wm_y_offset' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE
			),
			'wm_x_transp' => array(
				'type'				=> 'int',
				'constraint'		=> 4
			),
			'wm_y_transp' => array(
				'type'				=> 'int',
				'constraint'		=> 4
			),
			'wm_text_color' => array(
				'type'				=> 'varchar',
				'constraint'		=> 7
			),
			'wm_use_drop_shadow' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'y'
			),
			'wm_shadow_distance' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'wm_shadow_color' => array(
				'type'				=> 'varchar',
				'constraint'		=> 7
			)
		);

		$this->EE->dbforge->add_field($watermark_fields);
		$this->EE->dbforge->add_key('wm_id', TRUE);
		$this->EE->dbforge->create_table('file_watermarks');

		$dimension_fields = array(
			'id' => array(
				'type' => 'int',
				'constraint' => 10,
				'unsigned' => TRUE,
				'auto_increment' => TRUE
			),
			'upload_location_id' => array(
				'type' => 'int',
				'constraint' => 4,
				'unsigned' => TRUE
			),
			'title' => array(
				'type' => 'varchar',
				'constraint' => 255,
				'default' => ''
			),
			'short_name' => array(
				'type' => 'varchar',
				'constraint' => 255,
				'default' => ''
			),
			'resize_type' => array(
				'type' => 'varchar',
				'constraint' => 50,
				'default' => ''
			),
			'width' => array(
				'type' => 'int',
				'constraint' => 10,
				'default' => 0
			),
			'height' => array(
				'type' => 'int',
				'constraint' => 10,
				'default' => 0
			),
			'watermark_id' => array(
				'type' => 'int',
				'constraint' => 4,
				'unsigned' => TRUE
			)
		);
		
		$this->EE->dbforge->add_field($dimension_fields);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->add_key('upload_location_id');
		$this->EE->dbforge->create_table('file_dimensions');

		$categories_fields = array(
			'file_id' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE
			),
			'cat_id' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE
			),
			'sort' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE,
				'default'		=> 0
			),
			'is_cover' => array(
				'type'			=> 'char',
				'constraint'	=> 1,
				'default'		=> 'n'
			)
		);
		
		$this->EE->dbforge->add_field($categories_fields);
		$this->EE->dbforge->add_key(array('file_id', 'cat_id'));
		$this->EE->dbforge->create_table('file_categories');
		
		$files_fields = array(
			'file_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'site_id' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE,
				'default'			=> 1
			),
			'title' => array(
				'type'				=> 'varchar',
				'constraint'		=> 255
			),
			'upload_location_id' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'rel_path' => array(
				'type'				=> 'varchar',
				'constraint'		=> 255
			),
			'status' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'o'
			),
			'mime_type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 255
			),
			'file_name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 255
			),
			'file_size' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'default'			=> 0
			),
			'field_1' => array(
				'type'				=> 'text'
			),
			'field_1_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_2' => array(
				'type'				=> 'text'
			),
			'field_2_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_3' => array(
				'type'				=> 'text'
			),
			'field_3_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_4' => array(
				'type'				=> 'text'
			),
			'field_4_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_5' => array(
				'type'				=> 'text'
			),
			'field_5_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_6' => array(
				'type'				=> 'text'
			),
			'field_6_fmt' => array(
				'type'				=> 'tinytext'
			),
			'metadata' => array(
				'type'				=> 'mediumtext',
				'null'				=> TRUE
			),
			'uploaded_by_member_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'upload_date' => array(
				'type'				=> 'int',
				'constraint'		=> 10
			),
			'modified_by_member_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'modified_date' => array(
				'type' 				=> 'int',
				'constraint'		=> 10
			)
		);
		
		$this->EE->dbforge->add_field($files_fields);
		$this->EE->dbforge->add_key('file_id', TRUE);
		$this->EE->dbforge->add_key(array('upload_location_id', 'site_id'));
		$this->EE->dbforge->create_table('files');
	}
}
/* END CLASS */

/* End of file ud_215.php */
/* Location: ./system/expressionengine/installer/updates/ud_215.php */
