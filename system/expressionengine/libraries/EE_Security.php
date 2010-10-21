<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Security Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Security extends CI_Security {


	/**
	 * Constructor
	 */	
	function EE_Security()
	{
		parent::CI_Security();

		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}	

	// --------------------------------------------------------------------

	/**
	 * Secure Forms Check
	 *
	 * @access	public
	 * @param 	string
	 * @param 	string
	 * @return	bool
	 */
	function secure_forms_check($xid)
	{	
		if ($this->EE->config->item('secure_forms') != 'y')
		{
			return TRUE;
		}
		
		$this->EE->db->where('hash', $xid);
		$this->EE->db->where('ip_address', $this->EE->input->ip_address());
		$this->EE->db->where('date > UNIX_TIMESTAMP()-7200');
		$this->EE->db->from('security_hashes');
		$total =  $this->EE->db->count_all_results();
		
		if ($total  == 0)
		{
			return FALSE;
		}

		$this->EE->db->where("(hash='".$this->EE->db->escape_str($xid)."' AND ip_address = '".$this->EE->input->ip_address()."')", NULL, FALSE);
		$this->EE->db->or_where('date < UNIX_TIMESTAMP()-7200');
		$this->EE->db->delete('security_hashes');
		
		return TRUE;
	}
}
// END CLASS

/* End of file EE_Security.php */
/* Location: ./system/expressionengine/libraries/EE_Security.php */