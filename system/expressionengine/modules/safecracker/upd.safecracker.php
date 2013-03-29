<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team, 
 * 		- Original Development by Barrett Newton -- http://barrettnewton.com
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		ee()->db->insert(
			'exp_modules',
			array(
				'module_name' => 'Safecracker',
				'module_version' => $this->version, 
				'has_cp_backend' => 'y',
				'has_publish_fields' => 'n'
			)
		);
		
		ee()->db->insert(
			'exp_actions',
			array(
				'class' => 'Safecracker',
				'method' => 'submit_entry',
			)
		);
		
		ee()->db->insert(
			'exp_actions',
			array(
				'class' => 'Safecracker',
				'method' => 'combo_loader',
			)
		);
		

		// Add Extension Hook
		ee()->db->insert('extensions', array(
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
		ee()->lang->loadfile('safecracker');
		
		if (ee()->config->item('allow_extensions') != 'y')
		{
			//ee()->output->show_user_error('general', lang('safecracker_extensions_disabled'));
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
		$query = ee()->db->get_where('modules', array('module_name' => 'Safecracker'));
		
		if ($query->row('module_id'))
		{
			ee()->db->where('module_id', $query->row('module_id'));
			ee()->db->delete('module_member_groups');
		}

		ee()->db->where('module_name', 'Safecracker');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Safecracker');
		ee()->db->delete('actions');
		
		// Disable extension
		ee()->db->delete('extensions', array('class' => 'Safecracker_ext'));

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
			ee()->db->insert(
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
			ee()->db->update('extensions', array('version' => $this->version), array('class' => 'Safecracker_ext'));
		}
		
		return TRUE;
	}
}

/* End of file upd.safecracker.php */
/* Location: ./system/expressionengine/modules/safecracker/upd.safecracker.php */