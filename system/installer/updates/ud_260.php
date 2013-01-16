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
		
		$this->_change_member_totals_length();
		$this->_update_session_table();
		$this->_update_actions_table();
		$this->_update_specialty_templates();
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Changes column type for `total_entries` and `total_comments` in the
	 * members table from smallint to mediumint to match the columns in the
	 * channels table and stats table.  */
	private function _change_member_totals_length()
	{
		$this->EE->dbforge->modify_column(
			'members',
			array(
				'total_entries' => array(
					'name' => 'total_entries',
					'type' => 'mediumint(8)'
				),
				'total_comments' => array(
					'name' => 'total_comments',
					'type' => 'mediumint(8)'
				),
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * update Session table
	 *
	 * @return TRUE
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
