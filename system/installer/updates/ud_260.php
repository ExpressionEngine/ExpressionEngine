<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
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
		$this->_update_session_table();
		$this->_update_actions_table();
		$this->_update_specialty_templates();
		
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
		if ( ! $this->EE->db->field_exists('fingerprint', 'sessions'))
		{
			$this->EE->dbforge->add_column(
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
		
		return TRUE;
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
		$this->EE->db->where('method', 'retrieve_password')
			->update('actions', array('method'=>'send_reset_token'));
		// For this one the method still exists, but is now a form.  It needs
		// to be renamed to the new processing method.
		$this->EE->db->where('method', 'reset_password')
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

		$this->EE->db->where('template_name', 'forgot_password_instructions')
			->update('specialty_templates', $data);
	}
}	
/* END CLASS */

/* End of file ud_260.php */
/* Location: ./system/expressionengine/installer/updates/ud_260.php */
