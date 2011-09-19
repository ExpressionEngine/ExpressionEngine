<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
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

	// Small note, if you feel the urge to add a constructor,
	// do not call get_instance(). The CI Security library
	// is sometimes instantiated before the controller is loaded.
	// i.e. when turning CI's csrf_protection on. Which you shouldn't
	// do in EE anywho. -pk

	// --------------------------------------------------------------------

	/**
	 * Secure Forms Check
	 *
	 * @param 	string
	 * @return	bool
	 */
	public function secure_forms_check($xid)
	{	
		if ( ! $this->check_xid($xid))
		{
			return FALSE;
		}
		
  		$this->delete_xid($xid);

		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Check for Valid Security Hash
	 *
	 * @param 	string
	 * @return	bool
	 */
	public function check_xid($xid)
	{
		$EE =& get_instance();
		
		if ($EE->config->item('secure_forms') != 'y')
		{
			return TRUE;
		}
		
		if ( ! $xid)
		{
			return FALSE;
		}

		$total = $EE->db->where('hash', $xid)
						->where('ip_address', $EE->input->ip_address())
						->where('date > UNIX_TIMESTAMP()-7200')
						->from('security_hashes')
						->count_all_results();
		
		if ($total === 0)
		{
			return FALSE;
		}
		
		return TRUE;		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Delete Security Hash
	 *
	 * @param 	string
	 * @return	void
	 */
	public function delete_xid($xid)
	{
		$EE =& get_instance();
		
		if ($EE->config->item('secure_forms') != 'y' OR $xid === FALSE)
		{
			return;
		}

		$EE->db->where("(hash='".$EE->db->escape_str($xid)."' AND ip_address = '".$EE->input->ip_address()."')", NULL, FALSE)
			   ->or_where('date < UNIX_TIMESTAMP()-7200')
			   ->delete('security_hashes');
		
		return;		
	}

}
// END CLASS

/* End of file EE_Security.php */
/* Location: ./system/expressionengine/libraries/EE_Security.php */
