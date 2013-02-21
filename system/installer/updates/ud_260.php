<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
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
	
	private $EE;
	var $version_suffix = '';
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$this->EE->load->dbforge();
		
		$this->_add_template_name_to_dev_log();
		$this->_drop_dst();
		$this->_update_timezone_column_lengths();
		
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
		if ( ! $this->EE->db->field_exists('template_id', 'developer_log'))
		{
			$this->EE->dbforge->add_column(
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
	}

	// --------------------------------------------------------------------

	/**
	 * Drop DST columns!
	 */
	private function _drop_dst()
	{
		if ($this->EE->db->field_exists('daylight_savings', 'members'))
		{
			$this->EE->dbforge->drop_column('members', 'daylight_savings');
		}

		if ($this->EE->db->field_exists('dst_enabled', 'channel_titles'))
		{
			$this->EE->dbforge->drop_column('channel_titles', 'dst_enabled');
		}

		if ($this->EE->db->field_exists('dst_enabled', 'channel_entries_autosave'))
		{
			$this->EE->dbforge->drop_column('channel_entries_autosave', 'dst_enabled');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * We need to store PHP timezone identifiers in the database instead of
	 * our shorthand names so that PHP can properly localize dates, so we
	 * need to increase the column lengths anywhere timezones are stored
	 */
	private function _update_timezone_column_lengths()
	{
		$this->EE->dbforge->modify_column(
			'members',
			array(
				'timezone' => array(
					'name' 			=> 'timezone',
					'type' 			=> 'varchar',
					'constraint' 	=> 50
				)
			)
		);

		// Get all date fields, we'll need to update their timezone column
		// lengths in the channel_data table
		$date_fields = $this->EE->db
			->select('field_id')
			->get_where(
				'channel_fields',
				array('field_type' => 'date')
			)->result_array();

		foreach ($date_fields as $field)
		{
			$field_name = 'field_dt_'.$field['field_id'];

			if ($this->EE->db->field_exists($field_name, 'channel_data'))
			{
				$this->EE->dbforge->modify_column(
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
	}
}	
/* END CLASS */

/* End of file ud_260.php */
/* Location: ./system/expressionengine/installer/updates/ud_260.php */
