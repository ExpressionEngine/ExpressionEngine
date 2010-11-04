<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

// --------------------------------------------------------------------

/**
 * Member Management Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Member_subscriptions extends Member {


	/** ----------------------------------
	/**  Member_settings Profile Constructor
	/** ----------------------------------*/
	function Member_subscriptions()
	{
	}

	
	
	/** ----------------------------------------
	/**  Subscriptions Edit Form
	/** ----------------------------------------*/
	
	function edit_subscriptions()
	{
		$this->EE->load->library('members');
		
		// Set some base values
		
		$result_data			= array();
		$pageurl 				= $this->_member_path('edit_subscriptions');
		$perpage				= 50;
		$rownum  				= $this->cur_id;
		$page_links				= '';
		$total_count			= 0;
		
		if ($rownum != '') 
		{
 			$rownum = substr($rownum, 1);
		}
		else
		{
			$rownum = 0;
		}
		
		$rownum = ($rownum == '' OR ($perpage > 1 AND $rownum == 1)) ? 0 : $rownum;
		
		/** ----------------------------------------
		/**  Set update path
		/** ----------------------------------------*/
		$swap['path:update_subscriptions'] = $this->_member_path('update_subscriptions');
		
		$subscription_data = $this->EE->members->get_member_subscriptions($this->EE->session->userdata('member_id'), $rownum, $perpage);		

		/** ------------------------------------
		/**  No results?  Bah, how boring...
		/** ------------------------------------*/
		$total_rows = $subscription_data['total_results'];	
		$result_data = $subscription_data['result_array'];	

		if ($total_rows == 0)
		{
			$swap['subscription_results'] = $this->_var_swap($this->_load_element('no_subscriptions_message'), array('lang:no_subscriptions'=> $this->EE->lang->line('no_subscriptions')));
											
			return $this->_var_swap($this->_load_element('subscriptions_form'), $swap);
		}
		
				
		/** ---------------------------------
		/**  Do we need pagination?
		/** ---------------------------------*/
		
		if ($rownum > $total_rows)
		{
			$rownum = 0;
		}
					
		$t_current_page = floor(($rownum / $perpage) + 1);
		$total_pages	= intval(floor($total_rows / $perpage));
		
		if ($total_rows % $perpage)
			$total_pages++;
		
		if ($total_rows > $perpage)
		{
			$this->EE->load->library('pagination');
			
			$config['base_url']		= $pageurl;
			$config['prefix']		= 'R';
			$config['total_rows'] 	= $total_rows;
			$config['per_page']		= $perpage;
			$config['cur_page']		= $rownum;
			$config['first_link'] 	= $this->EE->lang->line('pag_first_link');
			$config['last_link'] 	= $this->EE->lang->line('pag_last_link');

			// Allows $config['cur_page'] to override
			$config['uri_segment'] = 0;

			$this->EE->pagination->initialize($config);
			$page_links = $this->EE->pagination->create_links();			
		}


	
		// Build the result table...

		$out = $this->_var_swap($this->_load_element('subscription_result_heading'),
								array(
										'lang:title'		=>	$this->EE->lang->line('title'),
										'lang:type'		 =>	$this->EE->lang->line('type'),
										'lang:unsubscribe'  =>	$this->EE->lang->line('unsubscribe')
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
		
		$out .= $this->_var_swap($this->_load_element('subscription_pagination'),
								 array('pagination' => $page_links,
								 		'lang:unsubscribe' => $this->EE->lang->line('unsubscribe'),
								 		'class' => ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo'));

	
		$swap['subscription_results'] = $out;
				
		return $this->_var_swap(
									$this->_load_element('subscriptions_form'), $swap
								);
	}

	
	
	
	/** ----------------------------------------
	/**  Update Subscriptions
	/** ----------------------------------------*/
	
	function update_subscriptions()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect($this->_member_path('edit_subscriptions'));
			exit;	
		}
				
		$this->EE->load->library('subscription');

		foreach ($_POST['toggle'] as $key => $val)
		{		
			switch (substr($val, 0, 1))
			{
				case "b"	: $this->EE->subscription->init('comment', array('entry_id' => substr($val, 1)), TRUE);
								$this->EE->subscription->unsubscribe($this->EE->session->userdata['member_id']);
					break;
				case "f"	: $this->EE->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '".substr($val, 1)."' AND member_id = '{$this->EE->session->userdata['member_id']}'");
					break;
			}
		}
				
		/** -------------------------------------
		/**  Success message
		/** -------------------------------------*/
	
		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'		=>	$this->EE->lang->line('subscriptions'),
										'lang:message'		=>	$this->EE->lang->line('subscriptions_removed')
									 )
								);
	}

	
}
// END CLASS

/* End of file mod.member_subscriptions.php */
/* Location: ./system/expressionengine/modules/member/mod.member_subscriptions.php */