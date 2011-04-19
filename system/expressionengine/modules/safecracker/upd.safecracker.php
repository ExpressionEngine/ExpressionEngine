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
	public $version = '2.0';
	
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
		$this->validate();
		
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
			$this->EE->output->show_user_error('general', $this->EE->lang->line('safecracker_extensions_disabled'));
		}
		
		if (APP_VER < '2.1.2')// || APP_BUILD < '20100805')
		{
			$this->EE->output->show_user_error('general', $this->EE->lang->line('safecracker_ee_version'));
		}
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
		if ($current == $this->version)
		{
			return FALSE;
		}
		
		if ($current < '1.0.3')
		{
			$this->EE->db->insert(
				'actions',
				array(
					'class' => 'Safecracker',
					'method' => 'combo_loader',
				)
			);
		}
		
		return TRUE;
	}
}

/* End of file upd.safecracker.php */
/* Location: ./system/expressionengine/third_party/modules/safecracker/upd.safecracker.php */