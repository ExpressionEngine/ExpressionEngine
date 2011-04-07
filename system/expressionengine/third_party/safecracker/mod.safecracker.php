<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Safecracker
{
	public $return_data = '';

	/**
	 * Safecracker
	 * 
	 * @access	public
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
    
	/**
	 * submit_entry
	 * 
	 * @access	public
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
	
    
	/**
	 * combo_loader
	 * 
	 * @access	public
	 * @return	void
	 */
	public function combo_loader()
	{
		$this->EE->load->library('SC_Javascript', array('instance' => $this->EE), 'sc_javascript');
		return $this->EE->sc_javascript->combo_load();
	}
}

/* End of file mod.safecracker.php */
/* Location: ./system/expressionengine/third_party/modules/safecracker/mod.safecracker.php */