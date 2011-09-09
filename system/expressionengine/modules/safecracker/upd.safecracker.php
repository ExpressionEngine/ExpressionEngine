<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team, 
 * 		- Original Development by Barrett Newton -- http://barrettnewton.com
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine SafeCracker Module Update File 
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Safecracker_upd
{
	public $version = '2.1';
	
	/**
	 * Safecracker_upd
	 * 
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------
	
	/**
	 * install
	 * 
	 * @return	void
	 */
	public function install()
	{
		$this->EE->db->insert(
			'exp_modules',
			array(
				'module_name' => 'Safecracker',
				'module_version' => $this->version, 
				'has_cp_backend' => 'y',
				'has_publish_fields' => 'n'
			)
		);
		
		$this->EE->db->insert(
			'exp_actions',
			array(
				'class' => 'Safecracker',
				'method' => 'submit_entry',
			)
		);
		
		$this->EE->db->insert(
			'exp_actions',
			array(
				'class' => 'Safecracker',
				'method' => 'combo_loader',
			)
		);
		

		// Add Extension Hook
		$this->EE->db->insert('extensions', array(
			'class'    => 'Safecracker_ext',
			'hook'     => 'form_declaration_modify_data',
			'method'   => 'form_declaration_modify_data',
			'settings' => '',
			'priority' => 10,
			'version'  => $this->version,
			'enabled'  => 'y'
		));


		return TRUE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * validate
	 * 
	 * @return	void
	 */
	public function validate()
	{
		$this->EE->lang->loadfile('safecracker');
		
		if ($this->EE->config->item('allow_extensions') != 'y')
		{
			//$this->EE->output->show_user_error('general', $this->EE->lang->line('safecracker_extensions_disabled'));
		}
		
		//  Added to core with 2.1.5

	}

	// --------------------------------------------------------------------
	
	/**
	 * uninstall
	 * 
	 * @return	void
	 */
	public function uninstall()
	{
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Safecracker'));
		
		if ($query->row('module_id'))
		{
			$this->EE->db->where('module_id', $query->row('module_id'));
			$this->EE->db->delete('module_member_groups');
		}

		$this->EE->db->where('module_name', 'Safecracker');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Safecracker');
		$this->EE->db->delete('actions');
		
		// Disable extension
		$this->EE->db->delete('extensions', array('class' => 'Safecracker_ext'));

		return TRUE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * update
	 * 
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update($current = '')
	{
		if (version_compare($current, $this->version, '=='))
		{
			return FALSE;
		}
		
		if (version_compare($current, '1.0.3', '<'))
		{
			$this->EE->db->insert(
				'actions',
				array(
					'class' => 'Safecracker',
					'method' => 'combo_loader',
				)
			);
		}
		
		if (version_compare($current, '2.1', '<'))
		{
			// Update extension version number
			$this->EE->db->update('extensions', array('version' => $this->version), array('class' => 'Safecracker_ext'));
		}
		
		return TRUE;
	}
}

/* End of file upd.safecracker.php */
/* Location: ./system/expressionengine/modules/safecracker/upd.safecracker.php */