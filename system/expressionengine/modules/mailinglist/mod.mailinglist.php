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
 * ExpressionEngine Blacklist Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Mailinglist {

	var $email_confirm	= TRUE;  // TRUE/FALSE - whether to send an email confirmation when users sign up
	var $return_data	= '';

	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function Mailinglist()
	{
		$this->EE =& get_instance();
	}

	/** ----------------------------------------
	/**  Mailing List Submission Form
	/** ----------------------------------------*/
	function form()
	{
		$tagdata = $this->EE->TMPL->tagdata;

		$list = ($this->EE->TMPL->fetch_param('list') === FALSE) ? '0' : $this->EE->TMPL->fetch_param('list');
		$name = '';

		if ($list !== FALSE)
		{
			if (preg_match("/full_name/", $tagdata))
			{
				$query = $this->EE->db->query("SELECT list_title FROM exp_mailing_lists WHERE list_name ='".$this->EE->db->escape_str($list)."'");

				if ($query->num_rows() == 1)
				{
					$name = $query->row('list_title') ;
				}
			}
		}

		$tagdata = str_replace(LD.'full_name'.RD, $name, $tagdata);

		if ($this->EE->session->userdata('email') != '')
		{
			$tagdata = str_replace(LD.'email'.RD, $this->EE->session->userdata('email'), $tagdata);
		}
		else
		{
			$tagdata = str_replace(LD.'email'.RD, '', $tagdata);
		}

		/** ----------------------------------------
		/**  Create form
		/** ----------------------------------------*/

		if ($this->EE->TMPL->fetch_param('name') !== FALSE &&
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $this->EE->TMPL->fetch_param('name'), $match))
		{
			$data['name'] = $this->EE->TMPL->fetch_param('name');
		}

		$data['id']				= ($this->EE->TMPL->form_id == '') ? 'mailinglist_form' : $this->EE->TMPL->form_id;
		$data['class']			= $this->EE->TMPL->form_class;
		
		$data['hidden_fields']	= array(
										'ACT'	=> $this->EE->functions->fetch_action_id('Mailinglist', 'insert_new_email'),
										'RET'	=> $this->EE->functions->fetch_current_uri(),
										'list'	=> $list
									  );

		$res  = $this->EE->functions->form_declaration($data);

		$res .= $tagdata;

		$res .= "</form>";

		return $res;
	}




	/** ----------------------------------------
	/**  Insert new email
	/** ----------------------------------------*/
	function insert_new_email()
	{
		/** ----------------------------------------
		/**  Fetch the mailinglist language pack
		/** ----------------------------------------*/

		$this->EE->lang->loadfile('mailinglist');

		// Is the mailing list turned on?

		if ($this->EE->config->item('mailinglist_enabled') == 'n')
		{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('mailinglist_disabled'));
		}

		 /** ----------------------------------------
		/**  Blacklist/Whitelist Check
		/** ----------------------------------------*/

		if ($this->EE->blacklist->blacklisted == 'y' && $this->EE->blacklist->whitelisted == 'n')
		{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('not_authorized'));
		}

		if ( ! isset($_POST['RET']))
		{
			exit;
		}

		/** ----------------------------------------
		/**  Error trapping
		/** ----------------------------------------*/

		$errors = array();

		$email = $this->EE->input->get_post('email');
		$email = trim(strip_tags($email));
		$list = $this->EE->input->post('list');
		$list_id = FALSE;

		if ($email == '')
		{
			$errors[] = $this->EE->lang->line('ml_missing_email');
		}

		$this->EE->load->helper('email');

		if ( ! valid_email($email))
		{
			$errors[] = $this->EE->lang->line('ml_invalid_email');
		}

		if (count($errors) == 0)
		{
	
			// Secure Forms check - do it early due to amount of further data manipulation before insert
			if ($this->EE->security->check_xid($this->EE->input->post('XID')) == FALSE) 
			{ 
			 	$this->EE->functions->redirect(stripslashes($this->EE->input->post('RET')));
			}
			
			/** ----------------------------------------
			/**  Which list is being subscribed to?
			/** ----------------------------------------*/

			// If there is no list ID we'll have to figure it out.

			if ($list == '0')
			{
				$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_mailing_lists WHERE list_id = 1");

				if ($query->row('count')  != 1)
				{
					$errors[] = $this->EE->lang->line('ml_no_list_id');
				}
				else
				{
					$list_id = 1;
				}
			}
			else
			{
				$query = $this->EE->db->query("SELECT list_id FROM exp_mailing_lists WHERE list_name = '".$this->EE->db->escape_str($list)."'");

				if ($query->num_rows() != 1)
				{
					$errors[] = $this->EE->lang->line('ml_no_list_id');
				}
				else
				{
					$list_id = $query->row('list_id') ;
				}
			}

			// Kill duplicate emails from authorization queue.  This prevents an error if a user
			// signs up but never activates their email, then signs up again.  Note- check for list_id
			// as they may be signing up for two different llists

			$this->EE->db->query("DELETE FROM exp_mailing_list_queue WHERE email = '".$this->EE->db->escape_str($email)."' AND list_id = '".$this->EE->db->escape_str($list_id)."'");

			/** ----------------------------------------
			/**  Is the email already in the list?
			/** ----------------------------------------*/
			if ($list_id !== FALSE)
			{
				$query = $this->EE->db->query("SELECT count(*) AS count FROM exp_mailing_list WHERE email = '".$this->EE->db->escape_str($email)."' AND list_id = '".$this->EE->db->escape_str($list_id)."'");

				if ($query->row('count')  > 0)
				{
					$errors[] = $this->EE->lang->line('ml_email_already_in_list');
				}
			}
		}


		/** ----------------------------------------
		/**  Are there errors to display?
		/** ----------------------------------------*/

		if (count($errors) > 0)
		{
			return $this->EE->output->show_user_error('submission', $errors);
		}


		/** ----------------------------------------
		/**  Insert email
		/** ----------------------------------------*/

		$code = $this->EE->functions->random('alnum', 10);

		$return = '';

		if ($this->email_confirm == FALSE)
		{
			$this->EE->db->query("INSERT INTO exp_mailing_list (list_id, authcode, email, ip_address)
								  VALUES ('".$this->EE->db->escape_str($list_id)."', '".$code."', '".$this->EE->db->escape_str($email)."', '".$this->EE->db->escape_str($this->EE->input->ip_address())."')");

			$content  = $this->EE->lang->line('ml_email_accepted');

			$return = $_POST['RET'];
		}
		else
		{
			$this->EE->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date) VALUES ('".$this->EE->db->escape_str($email)."', '".$this->EE->db->escape_str($list_id)."', '".$code."', '".time()."')");

			$this->send_email_confirmation($email, $code, $list_id);

			$content  = $this->EE->lang->line('ml_email_confirmation_sent')."\n\n";
			$content .= $this->EE->lang->line('ml_click_confirmation_link');
		}

		//  Clear security hash
		$this->EE->security->delete_xid($this->EE->input->post('XID'));

		$site_name = ($this->EE->config->item('site_name') == '') ? $this->EE->lang->line('back') : stripslashes($this->EE->config->item('site_name'));

		$data = array(	'title' 	=> $this->EE->lang->line('ml_mailinglist'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $content,
						'link'		=> array($_POST['RET'], $site_name)
					 );

		$this->EE->output->show_message($data);
	}



	/** ----------------------------------------
	/**  Send confirmation email
	/** ----------------------------------------*/
	function send_email_confirmation($email, $code, $list_id)
	{
		$query = $this->EE->db->query("SELECT list_title FROM exp_mailing_lists WHERE list_id = '".$this->EE->db->escape_str($list_id)."'");

		$action_id  = $this->EE->functions->fetch_action_id('Mailinglist', 'authorize_email');

		$swap = array(
						'activation_url'	=> $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$code,
						'site_name'			=> stripslashes($this->EE->config->item('site_name')),
						'site_url'			=> $this->EE->config->item('site_url'),
						'mailing_list'		=> $query->row('list_title')
					 );

		$template = $this->EE->functions->fetch_email_template('mailinglist_activation_instructions');
		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

		/** ----------------------------
		/**  Send email
		/** ----------------------------*/

		$this->EE->load->library('email');

		$this->EE->email->wordwrap = true;
		$this->EE->email->mailtype = 'plain';
		$this->EE->email->priority = '3';

		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
		$this->EE->email->to($email);
		$this->EE->email->subject($email_tit);
		$this->EE->email->message($email_msg);
		$this->EE->email->send();
	}





	/** ------------------------------
	/**  Authorize email submission
	/** ------------------------------*/
	function authorize_email()
	{
		/** ----------------------------------------
		/**  Fetch the mailinglist language pack
		/** ----------------------------------------*/

		$this->EE->lang->loadfile('mailinglist');

		// Is the mailing list turned on?

		if ($this->EE->config->item('mailinglist_enabled') == 'n')
		{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('mailinglist_disabled'));
		}

		/** ----------------------------------------
		/**  Fetch the name of the site
		/** ----------------------------------------*/

		$site_name = ($this->EE->config->item('site_name') == '') ? $this->EE->lang->line('back') : stripslashes($this->EE->config->item('site_name'));


		/** ----------------------------------------
		/**  No ID?  Tisk tisk...
		/** ----------------------------------------*/

		$id  = $this->EE->input->get_post('id');

		if ($id == FALSE)
		{

			$data = array(	'title' 	=> $this->EE->lang->line('ml_mailinglist'),
							'heading'	=> $this->EE->lang->line('error'),
							'content'	=> $this->EE->lang->line('invalid_url'),
							'link'		=> array($this->EE->functions->fetch_site_index(), $site_name)
						 );

			$this->EE->output->show_message($data);
		}

		/** ----------------------------------------
		/**  Fetch email associated with auth-code
		/** ----------------------------------------*/

		$expire = time() - (60*60*48);

		$this->EE->db->query("DELETE FROM exp_mailing_list_queue WHERE date < '$expire' ");

		$query = $this->EE->db->query("SELECT email, list_id FROM exp_mailing_list_queue WHERE authcode = '".$this->EE->db->escape_str($id)."'");

		if ($query->num_rows() == 0)
		{
			$data = array(	'title' 	=> $this->EE->lang->line('ml_mailinglist'),
							'heading'	=> $this->EE->lang->line('error'),
							'content'	=> $this->EE->lang->line('ml_expired_date'),
							'link'		=> array($this->EE->functions->fetch_site_index(), $site_name)
						 );

			echo  $this->EE->output->show_message($data);
			exit;
		}

		/** ----------------------------------------
		/**  Transfer email to the mailing list
		/** ----------------------------------------*/

		$email = $query->row('email') ;
		$list_id = $query->row('list_id') ;

		if ($list_id == 0)
		{
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_mailing_lists WHERE list_id = 1");

			if ($query->row('count')  != 1)
			{
				return $this->EE->output->show_user_error('general', $this->EE->lang->line('ml_no_list_id'));
			}
			else
			{
				$list_id = 1;
			}
		}

		$this->EE->db->query("INSERT INTO exp_mailing_list (list_id, authcode, email, ip_address)
							  VALUES ('".$this->EE->db->escape_str($list_id)."', '$id', '".$this->EE->db->escape_str($email)."', '".$this->EE->db->escape_str($this->EE->input->ip_address())."')");
		$this->EE->db->query("DELETE FROM exp_mailing_list_queue WHERE authcode = '".$this->EE->db->escape_str($id)."'");


		/** ----------------------------------------
		/**  Is there an admin notification to send?
		/** ----------------------------------------*/
		if ($this->EE->config->item('mailinglist_notify') == 'y' AND $this->EE->config->item('mailinglist_notify_emails') != '')
		{
			$query = $this->EE->db->query("SELECT list_title FROM exp_mailing_lists WHERE list_id = '".$this->EE->db->escape_str($list_id)."'");

			$swap = array(
							'email'	=> $email,
							'mailing_list' => $query->row('list_title')
						 );

			$template = $this->EE->functions->fetch_email_template('admin_notify_mailinglist');
			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

			/** ----------------------------
			/**  Send email
			/** ----------------------------*/

			$this->EE->load->helper('string');
			// Remove multiple commas
			$notify_address = reduce_multiples($this->EE->config->item('mailinglist_notify_emails'), ',', TRUE);

			if ($notify_address != '')
			{
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/

				$this->EE->load->library('email');

				// Load the text helper
				$this->EE->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					$this->EE->email->EE_initialize();
					$this->EE->email->wordwrap = true;
					$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
					$this->EE->email->to($addy);
					$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
					$this->EE->email->subject($email_tit);
					$this->EE->email->message(entities_to_ascii($email_msg));
					$this->EE->email->send();
				}
			}
		}

		/** ------------------------------
		/**  Success Message
		/** ------------------------------*/
		$data = array(	'title' 	=> $this->EE->lang->line('ml_mailinglist'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('ml_account_confirmed'),
						'link'		=> array($this->EE->functions->fetch_site_index(), $site_name)
					 );

		$this->EE->output->show_message($data);
	}




	/** ------------------------------
	/**  Unsubscribe a user
	/** ------------------------------*/
	function unsubscribe()
	{

		/** ----------------------------------------
		/**  Fetch the mailinglist language pack
		/** ----------------------------------------*/

		$this->EE->lang->loadfile('mailinglist');


		$site_name = ($this->EE->config->item('site_name') == '') ? $this->EE->lang->line('back') : stripslashes($this->EE->config->item('site_name'));

		/** ----------------------------------------
		/**  No ID?  Tisk tisk...
		/** ----------------------------------------*/

		$id  = $this->EE->input->get_post('id');

		if ($id == FALSE)
		{
			$data = array(	'title' 	=> $this->EE->lang->line('ml_mailinglist'),
							'heading'	=> $this->EE->lang->line('error'),
							'content'	=> $this->EE->lang->line('invalid_url'),
							'link'		=> array($this->EE->functions->fetch_site_index(), $site_name)
						 );

			$this->EE->output->show_message($data);
		}

		/** ----------------------------------------
		/**  Fetch email associated with auth-code
		/** ----------------------------------------*/

		$expire = time() - (60*60*48);

		$this->EE->db->query("DELETE FROM exp_mailing_list WHERE authcode = '$id' ");

		if ($this->EE->db->affected_rows() == 0)
		{
			$data = array(	'title' 	=> $this->EE->lang->line('ml_mailinglist'),
							'heading'	=> $this->EE->lang->line('error'),
							'content'	=> $this->EE->lang->line('ml_unsubscribe_failed'),
							'link'		=> array($this->EE->functions->fetch_site_index(), $site_name)
						 );

			$this->EE->output->show_message($data);
		}


		$data = array(	'title' 	=> $this->EE->lang->line('ml_mailinglist'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('ml_unsubscribe'),
						'link'		=> array($this->EE->functions->fetch_site_index(), $site_name)
					 );

		$this->EE->output->show_message($data);
	}


}
// END CLASS

/* End of file mod.mailinglist.php */
/* Location: ./system/expressionengine/modules/mailinglist/mod.mailinglist.php */