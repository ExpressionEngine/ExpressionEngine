<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
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
				array('action' => $this->_member_path('update_subscriptions'))
			)
		);

		// Set some base values
		$result_data	= array();
		$pageurl		= $this->_member_path('edit_subscriptions');
		$perpage		= 50;
		$page_links		= '';
		$total_count	= 0;

		$temp = $this->_var_swap(
			$this->_load_element('subscription_pagination'),
			array(
				'lang:unsubscribe' => lang('unsubscribe'),
				'class' => ($perpage % 2) ? 'tableCellOne' : 'tableCellTwo'
			)
		);

		// Setup base pagiation
		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		$pagination->position = 'inline';
		$pagination->prefix = 'R';
		$temp = $pagination->prepare($temp);

		$subscription_data = ee()->members->get_member_subscriptions(ee()->session->userdata('member_id'));
		$total_rows = $subscription_data['total_results'];
		$result_data = $subscription_data['result_array'];
		$pagination->build($total_rows, $perpage);

		// Get only what we need
		$result_data = array_slice($subscription_data['result_array'], $pagination->offset, $perpage);
		$total_rows = count($result_data);

		if ($total_rows == 0)
		{
			$swap['subscription_results'] = $this->_var_swap($this->_load_element('no_subscriptions_message'), array('lang:no_subscriptions'=> lang('no_subscriptions')));

			return $this->_var_swap($this->_load_element('subscriptions_form'), $swap);
		}

		// Set update path
		$swap['path:update_subscriptions'] = $this->_member_path('update_subscriptions');

		// Build the result table...
		$out = $this->_var_swap(
			$this->_load_element('subscription_result_heading'),
			array(
				'lang:title'		=> lang('title'),
				'lang:type'			=> lang('type'),
				'lang:unsubscribe'	=> lang('unsubscribe')
			)
		);

		$i = 0;
		foreach ($result_data as $val)
		{
			$rowtemp = $this->_load_element('subscription_result_rows');

			$rowtemp = str_replace('{class}', ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', $rowtemp);

			$rowtemp = str_replace('{path}', $val['path'], $rowtemp);
			$rowtemp = str_replace('{title}', $val['title'], $rowtemp);
			$rowtemp = str_replace('{id}', $val['id'], $rowtemp);
			$rowtemp = str_replace('{type}', $val['type'], $rowtemp);

			$out .= $rowtemp;
		}

		$swap['subscription_results'] = $out.$pagination->render($temp);

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
				'lang:heading'		=>	lang('subscriptions'),
				'lang:message'		=>	lang('subscriptions_removed')
			 )
		);
	}
}
// END CLASS

/* End of file mod.member_subscriptions.php */
/* Location: ./system/expressionengine/modules/member/mod.member_subscriptions.php */