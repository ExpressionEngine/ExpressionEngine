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
 * ExpressionEngine SafeCracker Module Control Panel 
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Safecracker_mcp
{
	/**
	 * Safecracker_mcp
	 * 
	 * @return	void
	 */
	public function __construct() 
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------
	
	/**
	 * index
	 * 
	 * @return	void
	 */
	public function index()
	{
		if (ee()->input->get('member_list'))
		{
			return $this->member_list();
		}
		
		ee()->functions->redirect(BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=safecracker');
	}

	// --------------------------------------------------------------------
	
	/**
	 * member_list
	 * 
	 * @return	void
	 */
	public function member_list()
	{
		if ( ! ee()->session->userdata('member_id') || empty(ee()->session->access_cp))
		{
			exit;
		}
		
		ee()->output->enable_profiler(FALSE);
		ee()->load->model('member_model');
		ee()->load->library('javascript');
		ee()->lang->loadfile('safecracker');
		
		$members = array('' => lang('safecracker_select_member'));
		
		$group_id = (ee()->input->get_post('group_id')) ? ee()->input->get_post('group_id', TRUE) : '';
		$offset = (ee()->input->get_post('offset')) ? ee()->input->get_post('offset', TRUE) : '';
		$search_value = (ee()->input->get_post('search_value')) ? ee()->input->get_post('search_value', TRUE) : '';
		
		$query = ee()->member_model->get_members(
			$group_id,
			101,
			$offset,
			$search_value
		);
		
		if ($query)
		{
			$result = $query->result();
			
			$more = FALSE;
			
			if ($query->num_rows() > 100)
			{
				$more = TRUE;
				array_pop($result);
			}
			
			foreach ($result as $row)
			{
				$members[$row->member_id] = $row->username;
			}
			
			if ($more)
			{
				$members['{NEXT}'] = lang('safecracker_more_members');
			}
		}

		return ee()->output->send_ajax_response($members);
	}
}

/* End of file mcp.safecracker.php */
/* Location: ./system/expressionengine/modules/modules/safecracker/mcp.safecracker.php */