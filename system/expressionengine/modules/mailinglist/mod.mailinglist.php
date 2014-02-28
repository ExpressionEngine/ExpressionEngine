<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Blacklist Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Mailinglist {

	var $email_confirm	= TRUE;  // TRUE/FALSE - whether to send an email confirmation when users sign up
	var $return_data	= '';

	/**
	 * Constructor
	 */
	function Mailinglist()
	{
		$this->EE =& get_instance();
	}

	// -------------------------------------------------------------------------

	/**
	 * Mailing List Submission Form
	 */
	function form()
	{
		$tagdata = ee()->TMPL->tagdata;

		$list = (ee()->TMPL->fetch_param('list') === FALSE) ? '0' : ee()->TMPL->fetch_param('list');
		$name = '';

		if ($list !== FALSE)
		{
			if (preg_match("/full_name/", $tagdata))
			{
				$query = ee()->db->query("SELECT list_title FROM exp_mailing_lists WHERE list_name ='".ee()->db->escape_str($list)."'");

				if ($query->num_rows() == 1)
				{
					$name = $query->row('list_title') ;
				}
			}
		}

		$tagdata = str_replace(LD.'full_name'.RD, $name, $tagdata);

		if (ee()->session->userdata('email') != '')
		{
			$tagdata = str_replace(LD.'email'.RD, ee()->session->userdata('email'), $tagdata);
		}
		else
		{
			$tagdata = str_replace(LD.'email'.RD, '', $tagdata);
		}

		/** ----------------------------------------
		/**  Create form
		/** ----------------------------------------*/

		if (ee()->TMPL->fetch_param('name') !== FALSE &&
			preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('name'), $match))
		{
			$data['name'] = ee()->TMPL->fetch_param('name');
		}

		$data['id']		= (ee()->TMPL->form_id == '') ? 'mailinglist_form' : ee()->TMPL->form_id;
		$data['class']	= ee()->TMPL->form_class;

		$data['hidden_fields'] = array(
			'ACT'	=> ee()->functions->fetch_action_id('Mailinglist', 'insert_new_email'),
			'RET'	=> ee()->functions->fetch_current_uri(),
			'list'	=> $list
		);

		$res  = ee()->functions->form_declaration($data);

		$res .= $tagdata;

		$res .= "</form>";

		return $res;
	}

	// -------------------------------------------------------------------------

	/** ----------------------------------------
	/**  Insert new email
	/** ----------------------------------------*/
	function insert_new_email()
	{
		/** ----------------------------------------
		/**  Fetch the mailinglist language pack
		/** ----------------------------------------*/

		ee()->lang->loadfile('mailinglist');

		// Is the mailing list turned on?

		if (ee()->config->item('mailinglist_enabled') == 'n')
		{
			return ee()->output->show_user_error('general', lang('mailinglist_disabled'));
		}

		 /** ----------------------------------------
		/**  Blacklist/Whitelist Check
		/** ----------------------------------------*/

		if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n')
		{
			return ee()->output->show_user_error('general', lang('not_authorized'));
		}

		if ( ! isset($_POST['RET']))
		{
			exit;
		}

		/** ----------------------------------------
		/**  Error trapping
		/** ----------------------------------------*/

		$errors = array();

		$email = ee()->input->get_post('email');
		$email = trim(strip_tags($email));
		$list = ee()->input->post('list');
		$list_id = FALSE;

		if ($email == '')
		{
			$errors[] = lang('ml_missing_email');
		}

		ee()->load->helper('email');

		if ( ! valid_email($email))
		{
			$errors[] = lang('ml_invalid_email');
		}

		if (count($errors) == 0)
		{
			/** ----------------------------------------
			/**  Which list is being subscribed to?
			/** ----------------------------------------*/

			// If there is no list ID we'll have to figure it out.

			if ($list == '0')
			{
				$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_mailing_lists WHERE list_id = 1");

				if ($query->row('count')  != 1)
				{
					$errors[] = lang('ml_no_list_id');
				}
				else
				{
					$list_id = 1;
				}
			}
			else
			{
				$query = ee()->db->query("SELECT list_id FROM exp_mailing_lists WHERE list_name = '".ee()->db->escape_str($list)."'");

				if ($query->num_rows() != 1)
				{
					$errors[] = lang('ml_no_list_id');
				}
				else
				{
					$list_id = $query->row('list_id') ;
				}
			}

			// Kill duplicate emails from authorization queue.  This prevents an error if a user
			// signs up but never activates their email, then signs up again.  Note- check for list_id
			// as they may be signing up for two different llists

			ee()->db->query("DELETE FROM exp_mailing_list_queue WHERE email = '".ee()->db->escape_str($email)."' AND list_id = '".ee()->db->escape_str($list_id)."'");

			/** ----------------------------------------
			/**  Is the email already in the list?
			/** ----------------------------------------*/
			if ($list_id !== FALSE)
			{
				$query = ee()->db->query("SELECT count(*) AS count FROM exp_mailing_list WHERE email = '".ee()->db->escape_str($email)."' AND list_id = '".ee()->db->escape_str($list_id)."'");

				if ($query->row('count')  > 0)
				{
					$errors[] = lang('ml_email_already_in_list');
				}
			}
		}

		/** ----------------------------------------
		/**  Are there errors to display?
		/** ----------------------------------------*/

		if (count($errors) > 0)
		{
			return ee()->output->show_user_error('submission', $errors);
		}


		/** ----------------------------------------
		/**  Insert email
		/** ----------------------------------------*/

		$code = ee()->functions->random('alnum', 10);

		$return = '';

		if ($this->email_confirm == FALSE)
		{
			ee()->db->query("INSERT INTO exp_mailing_list (list_id, authcode, email, ip_address)
								  VALUES ('".ee()->db->escape_str($list_id)."', '".$code."', '".ee()->db->escape_str($email)."', '".ee()->db->escape_str(ee()->input->ip_address())."')");

			$content  = lang('ml_email_accepted');

			$return = $_POST['RET'];
		}
		else
		{
			ee()->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date) VALUES ('".ee()->db->escape_str($email)."', '".ee()->db->escape_str($list_id)."', '".$code."', '".time()."')");

			$this->send_email_confirmation($email, $code, $list_id);

			$content  = lang('ml_email_confirmation_sent')."\n\n";
			$content .= lang('ml_click_confirmation_link');
		}

		$site_name = (ee()->config->item('site_name') == '') ? lang('back') : stripslashes(ee()->config->item('site_name'));

		$data = array(
			'title' 	=> lang('ml_mailinglist'),
			'heading'	=> lang('thank_you'),
			'content'	=> $content,
			'link'		=> array($_POST['RET'], $site_name)
		);

		ee()->output->show_message($data);
	}

	// -------------------------------------------------------------------------

	/**
	 * Send Confirmation Email
	 */
	function send_email_confirmation($email, $code, $list_id)
	{
		$query = ee()->db->query("SELECT list_title FROM exp_mailing_lists WHERE list_id = '".ee()->db->escape_str($list_id)."'");

		$action_id  = ee()->functions->fetch_action_id('Mailinglist', 'authorize_email');

		$swap = array(
			'activation_url'	=> ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$code,
			'site_name'			=> stripslashes(ee()->config->item('site_name')),
			'site_url'			=> ee()->config->item('site_url'),
			'mailing_list'		=> $query->row('list_title')
		);

		$template = ee()->functions->fetch_email_template('mailinglist_activation_instructions');
		$email_tit = ee()->functions->var_swap($template['title'], $swap);
		$email_msg = ee()->functions->var_swap($template['data'], $swap);

		/** ----------------------------
		/**  Send email
		/** ----------------------------*/

		ee()->load->library('email');

		ee()->email->wordwrap = true;
		ee()->email->mailtype = 'plain';
		ee()->email->priority = '3';

		ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
		ee()->email->to($email);
		ee()->email->subject($email_tit);
		ee()->email->message($email_msg);
		ee()->email->send();
	}

	// -------------------------------------------------------------------------

	/**
	 * Authorize email submission
	 */
	function authorize_email()
	{
		/** ----------------------------------------
		/**  Fetch the mailinglist language pack
		/** ----------------------------------------*/

		ee()->lang->loadfile('mailinglist');

		// Is the mailing list turned on?

		if (ee()->config->item('mailinglist_enabled') == 'n')
		{
			return ee()->output->show_user_error('general', lang('mailinglist_disabled'));
		}

		/** ----------------------------------------
		/**  Fetch the name of the site
		/** ----------------------------------------*/

		$site_name = (ee()->config->item('site_name') == '') ? lang('back') : stripslashes(ee()->config->item('site_name'));


		/** ----------------------------------------
		/**  No ID?  Tisk tisk...
		/** ----------------------------------------*/

		$id  = ee()->input->get_post('id');

		if ($id == FALSE)
		{

			$data = array(
				'title' 	=> lang('ml_mailinglist'),
				'heading'	=> lang('error'),
				'content'	=> lang('invalid_url'),
				'link'		=> array(ee()->functions->fetch_site_index(), $site_name)
			);

			ee()->output->show_message($data);
		}

		/** ----------------------------------------
		/**  Fetch email associated with auth-code
		/** ----------------------------------------*/

		$expire = time() - (60*60*48);

		ee()->db->query("DELETE FROM exp_mailing_list_queue WHERE date < '$expire' ");

		$query = ee()->db->query("SELECT email, list_id FROM exp_mailing_list_queue WHERE authcode = '".ee()->db->escape_str($id)."'");

		if ($query->num_rows() == 0)
		{
			$data = array(
				'title' 	=> lang('ml_mailinglist'),
				'heading'	=> lang('error'),
				'content'	=> lang('ml_expired_date'),
				'link'		=> array(ee()->functions->fetch_site_index(), $site_name)
			);

			echo ee()->output->show_message($data);
			exit;
		}

		/** ----------------------------------------
		/**  Transfer email to the mailing list
		/** ----------------------------------------*/

		$email = $query->row('email') ;
		$list_id = $query->row('list_id') ;

		if ($list_id == 0)
		{
			$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_mailing_lists WHERE list_id = 1");

			if ($query->row('count')  != 1)
			{
				return ee()->output->show_user_error('general', lang('ml_no_list_id'));
			}
			else
			{
				$list_id = 1;
			}
		}

		ee()->db->query("INSERT INTO exp_mailing_list (list_id, authcode, email, ip_address)
							  VALUES ('".ee()->db->escape_str($list_id)."', '$id', '".ee()->db->escape_str($email)."', '".ee()->db->escape_str(ee()->input->ip_address())."')");
		ee()->db->query("DELETE FROM exp_mailing_list_queue WHERE authcode = '".ee()->db->escape_str($id)."'");


		/** ----------------------------------------
		/**  Is there an admin notification to send?
		/** ----------------------------------------*/
		if (ee()->config->item('mailinglist_notify') == 'y' AND ee()->config->item('mailinglist_notify_emails') != '')
		{
			$query = ee()->db->select('list_title')
				->get_where(
					'mailing_lists',
					array('list_id' => $list_id)
				);

			$swap = array(
				'email'			=> $email,
				'mailing_list'	=> $query->row('list_title')
			);

			$template = ee()->functions->fetch_email_template('admin_notify_mailinglist');
			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			/** ----------------------------
			/**  Send email
			/** ----------------------------*/

			// Remove multiple commas
			$notify_address = reduce_multiples(ee()->config->item('mailinglist_notify_emails'), ',', TRUE);

			if ($notify_address != '')
			{
				// Send email
				ee()->load->library('email');

				// Load the text helper
				ee()->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					ee()->email->EE_initialize();
					ee()->email->wordwrap = true;
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->to($addy);
					ee()->email->reply_to(ee()->config->item('webmaster_email'));
					ee()->email->subject($email_tit);
					ee()->email->message(entities_to_ascii($email_msg));
					ee()->email->send();
				}
			}
		}

		/** ------------------------------
		/**  Success Message
		/** ------------------------------*/
		$data = array(
			'title' 	=> lang('ml_mailinglist'),
			'heading'	=> lang('thank_you'),
			'content'	=> lang('ml_account_confirmed'),
			'link'		=> array(ee()->functions->fetch_site_index(), $site_name)
		);

		ee()->output->show_message($data);
	}


	// -------------------------------------------------------------------------

	/**
	 * Unsubscribe a user
	 */
	function unsubscribe()
	{
		ee()->lang->loadfile('mailinglist');

		$site_name = (ee()->config->item('site_name') == '') ?
			lang('back') : stripslashes(ee()->config->item('site_name'));

		$id = ee()->input->get_post('id');

		// If $id is invalid, deal with it now
		// $id will be 0 if no id is passed or if it's invalid
		if ($id === 0)
		{
			$data = array(
				'title' 	=> lang('ml_mailinglist'),
				'heading'	=> lang('error'),
				'content'	=> lang('invalid_url'),
				'link'		=> array(ee()->functions->fetch_site_index(), $site_name)
			);

			ee()->output->show_message($data);
		}

		// Fetch email associated with auth-code
		$expire = time() - (60*60*48);

		ee()->db->delete('mailing_list', array('authcode' => $id));

		if (ee()->db->affected_rows() == 0)
		{
			$data = array(
				'title' 	=> lang('ml_mailinglist'),
				'heading'	=> lang('error'),
				'content'	=> lang('ml_unsubscribe_failed'),
				'link'		=> array(ee()->functions->fetch_site_index(), $site_name)
			);

			ee()->output->show_message($data);
		}

		$data = array(
			'title' 	=> lang('ml_mailinglist'),
			'heading'	=> lang('thank_you'),
			'content'	=> lang('ml_unsubscribe'),
			'link'		=> array(ee()->functions->fetch_site_index(), $site_name)
		);

		ee()->output->show_message($data);
	}


}
// END CLASS

/* End of file mod.mailinglist.php */
/* Location: ./system/expressionengine/modules/mailinglist/mod.mailinglist.php */