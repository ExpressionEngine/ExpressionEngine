<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Security extends CI_Security {

	private $_xid_ttl = 7200;

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
		$check = $this->check_xid($xid);

		if (REQ != 'CP' OR ! AJAX_REQUEST)
		{
			$this->delete_xid($xid);
		}

		return $check;
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

		$total = $EE->db->where(array(
				'hash' 			=> $xid,
				'session_id' 	=> $EE->session->userdata('session_id'),
				'date >' 		=> $EE->localize->now - $this->_xid_ttl
			))
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
	 * Generate Security Hash
	 *
	 * @return String XID generated
	 */
	public function generate_xid($count = 1, $array = FALSE)
	{
		$EE =& get_instance();

		$hashes = array();
		$inserts = array();

		for ($i = 0; $i < $count; $i++)
		{
			$hash = $EE->functions->random('encrypt');
			$inserts[] = array(
				'date' 			=> $EE->localize->now,
				'session_id'	=> $EE->session->userdata('session_id'),
				'hash' 			=> $hash
			);
			$hashes[] = $hash;	
		}
		
		$EE->db->insert_batch('security_hashes', $inserts);

		return (count($hashes) > 1 OR $array) ? $hashes : $hashes[0];
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

		$EE->db->where('hash', $xid)
			->or_where('date <', $EE->localize->now - $this->_xid_ttl)
			->delete('security_hashes');
		
		return;		
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes out of date XIDs
	 */
	public function garbage_collect_xids()
	{
		$EE =& get_instance();
		$EE->db->where('date <', $EE->localize->now - $this->_xid_ttl)
			->delete('security_hashes');
	}

}
// END CLASS

/* End of file EE_Security.php */
/* Location: ./system/expressionengine/libraries/EE_Security.php */
