<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Communicate Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Communicate extends Utilities {

	/**
	 * Index
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		ee()->load->model('addons_model');
		ee()->load->model('communicate_model');

		$default = array(
			'name'			=> '',
			'from'		 	=> ee()->session->userdata('email'),
			'recipient'  	=> '',
			'cc'			=> '',
			'bcc'			=> '',
			'subject' 		=> '',
			'message'		=> '',
			'plaintext_alt'	=> '',
			'priority'		=>  3,
			'text_fmt'		=> 'none',
			'mailtype'		=> $this->config->item('mail_format'),
			'wordwrap'		=> $this->config->item('word_wrap')
		);

		$vars = array(
			'text_formatting_options' => ee()->addons_model->get_plugin_formatting(TRUE)
		);

		$member_groups = array();

		/** -----------------------------
		/**  Fetch form data from cache
		/** -----------------------------*/
		if ($id = $this->input->get('id'))
		{
			$query = $this->communicate_model->get_cached_member_groups($id);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$member_groups[] = $row['group_id'];
				}
			}
		}

		// Set up mailing list options
		if ( ! $this->cp->allowed_group('can_email_mailinglist')
			OR ! isset($this->mailinglist_exists)
			OR $this->mailinglist_exists == FALSE)
		{
			$vars['mailing_lists'] = FALSE;
		}
		else
		{
			$query = ee()->communicate_model->get_mailing_lists();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$checked = (ee()->input->post('list_'.$row->list_id) !== FALSE OR in_array($row->list_id, $mailing_lists));
					$vars['mailing_lists'][$row->list_title] = array('name' => 'list_'.$row->list_id, 'value' => $row->list_id, 'checked' => $checked);
				}
			}
			else
			{
				$vars['mailing_lists'] = FALSE;
			}
		}

		// Set up member group emailing options
		if ( ! $this->cp->allowed_group('can_email_member_groups'))
		{
			$vars['member_groups'] = FALSE;
		}
		else
		{
			$addt_where = array('include_in_mailinglists' => 'y');

			$query = $this->member_model->get_member_groups('', $addt_where);

			foreach ($query->result() as $row)
			{
				$checked = ($this->input->post('group_'.$row->group_id) !== FALSE OR in_array($row->group_id, $member_groups));

				$vars['member_groups'][$row->group_title] = array('name' => 'group_'.$row->group_id, 'value' => $row->group_id, 'checked' => $checked);
			}
		}

		ee()->view->cp_page_title = lang('communicate');
		ee()->cp->render('utilities/communicate', $vars + $default);
	}

}
// END CLASS

/* End of file Communicate.php */
/* Location: ./system/expressionengine/controllers/cp/Utilities/Communicate.php */
