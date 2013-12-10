<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * Member Management Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Member_subscriptions extends Member {
	
	
	/**
	 * Subscriptions Edit Form
	 */
	public function edit_subscriptions()
	{
		ee()->load->library('members');
		
		$swap = array(
				'form_declaration'	=> ee()->functions->form_declaration(
					array('action' => $this->_member_path('update_subscriptions')))
				);
		
		// Set some base values
		
		$result_data			= array();
		$pageurl 				= $this->_member_path('edit_subscriptions');
		$perpage				= 50;
		$rownum  				= $this->cur_id;
		$page_links				= '';
		$total_count			= 0;
		
		$rownum = ($rownum != '') ? substr($rownum, 1) : 0;
		
		$rownum = ($rownum == '' OR ($perpage > 1 AND $rownum == 1)) ? 0 : $rownum;
		
		// Set update path
		$swap['path:update_subscriptions'] = $this->_member_path('update_subscriptions');
		
		$subscription_data = ee()->members->get_member_subscriptions(ee()->session->userdata('member_id'), $rownum, $perpage);		

		// No results?  Bah, how boring...
		$total_rows = $subscription_data['total_results'];	
		$result_data = $subscription_data['result_array'];	

		if ($total_rows == 0)
		{
			$swap['subscription_results'] = $this->_var_swap($this->_load_element('no_subscriptions_message'), array('lang:no_subscriptions'=> ee()->lang->line('no_subscriptions')));
											
			return $this->_var_swap($this->_load_element('subscriptions_form'), $swap);
		}
		
		// Do we need pagination?
		if ($rownum > $total_rows)
		{
			$rownum = 0;
		}
					
		$t_current_page = floor(($rownum / $perpage) + 1);
		$total_pages	= intval(floor($total_rows / $perpage));
		
		if ($total_rows % $perpage)
		{
			$total_pages++;			
		}
		
		if ($total_rows > $perpage)
		{
			ee()->load->library('pagination');
			
			$config['base_url']		= $pageurl;
			$config['prefix']		= 'R';
			$config['total_rows'] 	= $total_rows;
			$config['per_page']		= $perpage;
			$config['cur_page']		= $rownum;
			$config['first_link'] 	= ee()->lang->line('pag_first_link');
			$config['last_link'] 	= ee()->lang->line('pag_last_link');

			// Allows $config['cur_page'] to override
			$config['uri_segment'] = 0;

			ee()->pagination->initialize($config);
			$page_links = ee()->pagination->create_links();			
		}

		// Build the result table...
		$out = $this->_var_swap(
			$this->_load_element('subscription_result_heading'),
			array(
					'lang:title'		=>	ee()->lang->line('title'),
					'lang:type'		 =>	ee()->lang->line('type'),
					'lang:unsubscribe'  =>	ee()->lang->line('unsubscribe')
				 )
		);


		$i = 0;
		foreach ($result_data as $val)
		{
			$rowtemp = $this->_load_element('subscription_result_rows');
						
			$rowtemp = str_replace('{class}',	($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', $rowtemp);
			
			$rowtemp = str_replace('{path}',	$val['path'],	$rowtemp);
			$rowtemp = str_replace('{title}',	$val['title'],	$rowtemp);
			$rowtemp = str_replace('{id}',	  $val['id'],		$rowtemp);
			$rowtemp = str_replace('{type}',	$val['type'],	$rowtemp);

			$out .= $rowtemp;
		}
		
		$out .= $this->_var_swap(
			$this->_load_element('subscription_pagination'),
			array(
				'pagination' => $page_links,
				'lang:unsubscribe' => ee()->lang->line('unsubscribe'),
				'class' => ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo'
			)
		);

	
		$swap['subscription_results'] = $out;
				
		return $this->_var_swap(
			$this->_load_element('subscriptions_form'), $swap
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update Subscriptions
	 */
	function update_subscriptions()
	{
		if ( ! ee()->input->post('toggle'))
		{
			ee()->functions->redirect($this->_member_path('edit_subscriptions'));
			exit;	
		}
				
		ee()->load->library('subscription');

		foreach ($_POST['toggle'] as $key => $val)
		{		
			switch (substr($val, 0, 1))
			{
				case "b"	: ee()->subscription->init('comment', array('entry_id' => substr($val, 1)), TRUE);
							  ee()->subscription->unsubscribe(ee()->session->userdata('member_id'));
					break;
				case "f"	: ee()->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '".substr($val, 1)."' AND member_id = '".ee()->session->userdata['member_id']."'");
					break;
			}
		}

		// Success message	
		return $this->_var_swap(
			$this->_load_element('success'),
			array(
				'lang:heading'		=>	ee()->lang->line('subscriptions'),
				'lang:message'		=>	ee()->lang->line('subscriptions_removed')
			 )
		);
	}
}
// END CLASS

/* End of file mod.member_subscriptions.php */
/* Location: ./system/expressionengine/modules/member/mod.member_subscriptions.php */