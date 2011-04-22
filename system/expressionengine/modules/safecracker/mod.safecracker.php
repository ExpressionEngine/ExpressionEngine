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
 * ExpressionEngine SafeCracker Module File 
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Safecracker
{
	public $return_data = '';

	/**
	 * Safecracker
	 * 
	 * @return	void
	 */
	public function Safecracker()
	{
		$this->EE = get_instance();
		
		$this->EE->load->library('safecracker_lib');
		
		//proceed if called from a template
		if ( ! empty($this->EE->TMPL))
		{
			$this->return_data = $this->EE->safecracker->entry_form();
		}
	}

	// --------------------------------------------------------------------
    
	/**
	 * submit_entry
	 * 
	 * @return	void
	 */
	public function submit_entry()
	{
		//exit if not called as an action
		if ( ! empty($this->EE->TMPL) || ! $this->EE->input->get_post('ACT'))
		{
			return '';
		}
		
		$this->EE->safecracker->submit_entry();
	}

	// --------------------------------------------------------------------	
    
	/**
	 * combo_loader
	 * 
	 * @return	void
	 */
	public function combo_loader()
	{
		$this->EE->load->library('SC_Javascript', array('instance' => $this->EE), 'sc_javascript');
		return $this->EE->sc_javascript->combo_load();
	}
}

/* End of file mod.safecracker.php */
/* Location: ./system/expressionengine/modules/modules/safecracker/mod.safecracker.php */