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
 * ExpressionEngine Simple Commerce Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Simple_commerce {

	var $return_data		= '';
	var $debug				= FALSE;
	var $debug_incoming_ipn = FALSE;  // Will send an email with the incoming ipn post data for debug purposes
	var $debug_email_address = '';  // Address to send the incoming ipn debug data to- defaults to webmaster_email

	var $possible_post;
	var $post				= array();
	
	var $encrypt			= FALSE;
	var $certificate_id		= '';
	var $public_certificate	= '';
	var $private_key		= '';
	var $paypal_certificate	= '';
	var $temp_path			= '';

	/** ----------------------------------------
	/**  Constructor
	/** ----------------------------------------*/
	function Simple_commerce()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->possible_post = array('business', 'receiver_email', 'receiver_id', 'item_name', 
									 'item_number', 'quantity', 'invoice', 'custom', 'memo', 
									 'tax', 'option_name1', 'option_selection1', 'option_name2', 
									 'option_selection2', 'num_cart_items', 'mc_gross', 'mc_fee', 
									 'mc_currency', 'payment_gross', 'payment_fee', 
									 'payment_status', 'pending_reason', 'reason_code', 
									 'payment_date', 'txn_id', 'txn_type', 'payment_type', 
									 'first_name', 'last_name', 
									 'payer_business_name', 'address_name', 'address_street', 
									 'address_city', 'address_state', 'address_zip', 'address_country_code',
									 'address_country', 'address_status', 'payer_email', 
									 'payer_id', 'payer_status', 'member_id',
									 'verify_sign', 'test_ipn');
									 
		if ($this->EE->config->item('sc_encrypt_buttons') === 'y' && function_exists('openssl_pkcs7_sign'))
		{
			$this->encrypt = TRUE;
			
			foreach(array('certificate_id', 'public_certificate', 'private_key', 'paypal_certificate') as $val)
			{
				if ($this->EE->config->item('sc_'.$val) === FALSE OR $this->EE->config->item('sc_'.$val) == '')
				{
					$this->encrypt = FALSE;
					break;
				}
				else
				{
					$this->$val = $this->EE->config->item('sc_'.$val);
				}
			}
			
			// Not required
			if ($this->encrypt === TRUE && $this->EE->config->item('sc_temp_path') !== FALSE)
			{
				$this->temp_path = $this->EE->config->item('sc_temp_path');
			}
			
		}
	}


	
	/** ----------------------------------------
	/**  Output Item Info
	/** ----------------------------------------*/
	function purchase()
	{
		if (($entry_id = $this->EE->TMPL->fetch_param('entry_id')) === FALSE) return;
		if (($success = $this->EE->TMPL->fetch_param('success')) === FALSE) return;
		$cached = FALSE;

		$paypal_account = ( ! $this->EE->config->item('sc_paypal_account')) ? $this->EE->config->item('webmaster_email') : $this->EE->config->item('sc_paypal_account');
		$cancel	 		= ( ! $this->EE->TMPL->fetch_param('cancel'))  ? $this->EE->functions->fetch_site_index() : $this->EE->TMPL->fetch_param('cancel');
		$currency		= ( ! $this->EE->TMPL->fetch_param('currency'))  ? 'USD' : $this->EE->TMPL->fetch_param('currency');
		$country_code	= ( ! $this->EE->TMPL->fetch_param('country_code')) ? 'US' : strtoupper($this->EE->TMPL->fetch_param('country_code'));
		$show_disabled  = ( $this->EE->TMPL->fetch_param('show_disabled') == 'yes') ? TRUE : FALSE;
		
		if (substr($success, 0, 4) !== 'http')
		{
			$success = $this->EE->functions->create_url($success);
		}
		
		if (substr($cancel, 0, 4) !== 'http')
		{
			$cancel = $this->EE->functions->create_url($cancel);
		}
		
			
		if ($show_disabled === TRUE)
		{
			$addsql = '';
		}
		else
		{
			$addsql = "AND sci.item_enabled = 'y' ";
		}
		
		$query = $this->EE->db->query("SELECT wt.title AS item_name, sci.* 
							 FROM exp_simple_commerce_items sci, exp_channel_titles wt
							 WHERE sci.entry_id = '".$this->EE->db->escape_str($entry_id)."'
							 {$addsql}
							 AND sci.entry_id = wt.entry_id
							 LIMIT 1");
							
		if ($query->num_rows() == 0) return;
		
		$row = $query->row_array();
		
 		$variable_row = $row;
		
		if ($this->encrypt !== TRUE)
		{
			$variable_row['item_name'] = str_replace(	array("&","<",">","\"", "'", "-"),
														array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
														$row['item_name'] );
		}
		
		$variable_row['item_regular_price'] = $this->round_money($row['item_regular_price']);
		$variable_row['item_sale_price'] = $this->round_money($row['item_sale_price']);
		$variable_row['item_type'] = ($row['recurring'] == 'y') ? 'subscription' : 'purchase';

		$buy_now['action']			= ($this->debug === TRUE) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
		$buy_now['hidden_fields']	= array(
										'cmd'  				=> '_xclick',
										'upload'			=> "1",
										'business'			=> $paypal_account,
										'return'			=> $success,
										'cancel_return'		=> $cancel,
										'item_name'			=> $row['item_name'] ,
										'item_number'		=> $row['item_id'] ,
										'amount'			=> ($row['item_use_sale']  == 'y') ? $row['item_sale_price']  : $row['item_regular_price'] ,
										'lc'				=> $country_code,
										'currency_code'		=> $currency,
										'custom'			=> $this->EE->session->userdata['member_id']
										);
		
		if ($this->encrypt === TRUE)
		{
			$url = $buy_now['action'].'?cmd=_s-xclick&amp;encrypted='.urlencode($this->encrypt_data($buy_now['hidden_fields']));
		}
		else
		{
			$url = $buy_now['action'];
			
			foreach($buy_now['hidden_fields'] as $k => $v)
			{
				$url .= ($k == 'cmd') ? '?'.$k.'='.$v : '&amp;'.$k.'='.$this->prep_val($v);
			}
		}

		$variable_row['buy_now_url'] = $url;
		


		//  Subscription

		
		$subscribe['action']			= ($this->debug === TRUE) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
		$subscribe['hidden_fields']	= array(
										'cmd'  				=> '_xclick-subscriptions',
										'upload'			=> "1",
										'business'			=> $paypal_account,
										'return'			=> $success,
										'cancel_return'		=> $cancel,
										'item_name'			=> $row['item_name'],
										'item_number'		=> $row['item_id'],
										'p3'				=> $row['subscription_frequency'],
										't3'				=> strtoupper(substr($row['subscription_frequency_unit'], 0, 1)),
										'a3'				=> ($row['item_use_sale'] == 'y') ? $row['item_sale_price'] : $row['item_regular_price'],
										'src'				=> 1,
										'lc'				=> $country_code,
										'currency_code'		=> $currency,
										'custom'			=> $this->EE->session->userdata['member_id']
										);
		
		if ($this->encrypt === TRUE)
		{
			$url = $subscribe['action'].'?cmd=_s-xclick&amp;encrypted='.urlencode($this->encrypt_data($buy_now['hidden_fields']));
		}
		else
		{
			$url = $subscribe['action'];
			
			foreach($subscribe['hidden_fields'] as $k => $v)
			{
				$url .= ($k == 'cmd') ? '?'.$k.'='.$v : '&amp;'.$k.'='.$this->prep_val($v);
			}
		}

		$variable_row['subscribe_now_url'] = $url;


		//  Add to Cart 
		
		$add_to_cart['action']				= ($this->debug === TRUE) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
		$add_to_cart['hidden_fields']	= array(
												'cmd'				=> '_cart',
												'add'				=> "1",
												'business'			=> $paypal_account,
												'return'			=> $success,
												'cancel_return'		=> $cancel,
												'item_name'			=> $row['item_name'],
												'item_number'		=> $row['item_id'],
												'quantity'			=> '1',
												'amount'			=> ($row['item_use_sale'] == 'y') ? $row['item_sale_price'] : $row['item_regular_price'],
												'lc'				=> $country_code,
												'currency_code'		=> $currency,
												'custom'			=> $this->EE->session->userdata['member_id']
												);
										
		if ($this->encrypt === TRUE)
		{
			$url = $add_to_cart['action'].'?cmd=_s-xclick&amp;encrypted='.urlencode($this->encrypt_data($add_to_cart['hidden_fields']));
		}
		else
		{
			$url = $add_to_cart['action'];
			
			foreach($add_to_cart['hidden_fields'] as $k => $v)
			{	
				$url .= ($k == 'cmd') ? '?'.$k.'='.$v : '&amp;'.$k.'='.$this->prep_val($v);
			}
		}
		
		$variable_row['add_to_cart_url'] = $url;

		//  View Cart

		if ($this->debug === TRUE)
		{
			$view_cart['action'] = 'https://www.sandbox.paypal.com/cart/display=1&amp;bn=tm_gl_2.0&amp;business='.$paypal_account;
		}
		else
		{
			$view_cart['action'] = 'https://www.paypal.com/cart/display=1&amp;bn=tm_gl_2.0&amp;business='.$paypal_account;
		}
		
		$variable_row['view_cart_url'] = $view_cart['action'];
		
		/** ----------------------------------------
		/**  Parse the Buttons
		/** ----------------------------------------*/
		
		if ($this->encrypt === TRUE)
		{
			$buy_now['hidden_fields'] = array('cmd' => '_s-xclick',
											  'encrypted' => $this->encrypt_data($buy_now['hidden_fields']));
			
			$add_to_cart['hidden_fields'] = array('cmd' => '_s-xclick',
												  'encrypted' => $this->encrypt_data($add_to_cart['hidden_fields']));
		}

		$variables[] = $variable_row;
		
		$output = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $variables); 
		
		
		foreach ($this->EE->TMPL->var_pair as $key => $val)
		{	 
			$data = array();
			
			if ($key == 'buy_now_button')
			{
				$data = $buy_now;
			}
			elseif ($key == 'add_to_cart_button' && $row['recurring'] != 'y')
			{
				$data = $add_to_cart;
			}
			elseif ($key == 'view_cart_button' && $row['recurring'] != 'y')
			{
				$data = $view_cart;
			}
			else
			{
				$output = $this->EE->TMPL->delete_var_pairs($key, $key, $output);
				continue;
			}
			
			$data['id']		= 'paypal_form_'.$row['item_id'].'_'.$key;
			$data['secure'] = FALSE;
			
			$form	= $this->EE->functions->form_declaration($data).
					  '<input type="submit" name="submit" value="\\1" class="paypal_button" />'."\n".
					  '</form>'."\n\n";
					  
			$output = preg_replace("/".LD.preg_quote($key).RD."(.*?)".LD.'\/'.$key.RD."/s", $form, $output);
		}
		

		$this->return_data = $output;
		
		return $this->return_data;

	}
	
	function button_form($id, $type, $hidden='')
	{
			$data['id']		= 'paypal_form_'.$row['item_id'].'_'.$type;
			$data['class']	= $this->EE->TMPL->form_class;
			$data['secure'] = FALSE;
			$data = $hidden;
			
			$form	= $this->EE->functions->form_declaration($data).
					  '<input type="submit" name="submit" value="\\1" class="paypal_button" />'."\n".
					  '</form>'."\n\n";
			return 'dude';		
	}

	/** -------------------------------------
	/**  Round Money
	/** -------------------------------------*/
	
	function round_money($value, $dec=2)
	{
		$decimal = ($this->EE->TMPL->fetch_param('decimal') == ',')  ? ',' : '.';
		
		$value += 0.0;
		$unit	= floor($value * pow(10, $dec+1)) / 10;
		$round	= round($unit);
		return str_replace('.', $decimal, sprintf("%01.2f", ($round / pow(10, $dec))));
	}
	
	/** ----------------------------------------
	/**  Process an Incoming IPN From PayPal
	/** ----------------------------------------*/
	function incoming_ipn()
	{
		// Send incoming post data if debugging required
		if ($this->debug_incoming_ipn)
		{
			ob_start();
			print_r($_POST);
			$msg = ob_get_contents();
			ob_end_clean();

			$this->EE->load->library('email');
			$debug_to = ($this->debug_email_address == '') ? $this->EE->config->item('webmaster_email') : $this->debug_email_address;
			
			$this->EE->email->from($this->EE->config->item('webmaster_email'), 
										$this->EE->config->item('site_name'));
			$this->EE->email->to($debug_to);
			$this->EE->email->subject('EE Debug: Incoming IPN Response');
			$this->EE->email->message($msg);
			$this->EE->email->send();
			$this->EE->email->EE_initialize();			

		}

		if (empty($_POST))
		{
			@header("HTTP/1.0 404 Not Found");
			@header("HTTP/1.1 404 Not Found");
			exit('No Data Sent');
		}
		elseif($this->debug !== TRUE && isset($_POST['test_ipn']) && $_POST['test_ipn'] == 1)
		{
			@header("HTTP/1.0 404 Not Found");
			@header("HTTP/1.1 404 Not Found");
			exit('Not Debugging Right Now');
		}
		
		
		$paypal_account = ( ! $this->EE->config->item('sc_paypal_account')) ? $this->EE->config->item('webmaster_email') : $this->EE->config->item('sc_paypal_account');
		
		/** ----------------------------------------
		/**  Prep, Prep, Prep
		/** ----------------------------------------*/
		
		foreach($this->possible_post as $value)
		{
			$this->post[$value] = '';
		}
		
		foreach($_POST as $key => $value)
		{
			$this->post[$key] = $this->EE->security->xss_clean($value);
		}
		
		if ($this->debug === TRUE)
		{
			$url = ( ! function_exists('openssl_open')) ? 'http://www.sandbox.paypal.com/cgi-bin/webscr' :  'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}
		else
		{
			$url = ( ! function_exists('openssl_open')) ? 'http://www.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
		}
		
		/** ----------------------------------------
		/**  Ping Them Back For Confirmation
		/** ----------------------------------------*/
		
		if ( function_exists('curl_init'))
		{
			$result = $this->curl_process($url); 
		}
		else
		{
			$result = $this->fsockopen_process($url);
		}
		
		
		/** ----------------------------------------
		/**  Evaluate PayPal's Response
		/** ----------------------------------------*/
		
		/* -------------------------------------
		/*  'simple_commerce_evaluate_ipn_response' hook.
		/*  - Take over processing of PayPal's response to an
		/*  - IPN confirmation
		/*  - Added EE 1.5.1
		*/  
			if ($this->EE->extensions->active_hook('simple_commerce_evaluate_ipn_response') === TRUE)
			{
				$result = $this->EE->extensions->universal_call('simple_commerce_evaluate_ipn_response', $this, $result);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		/*
		/* -------------------------------------*/
		
		
		if (stristr($result, 'VERIFIED'))
		{

			// 'subscr_eot' type should be used to cancel the subscription. This is sent when the user's subscription period has expired.
			// subscr_eot is confusing: https://www.x.com/thread/43174
			// http://stackoverflow.com/questions/1061683/subscriptions-with-paypal-ipn
						
			// 'subscr_signup' - subscription bought payment pending
			// Subscription start and end pings have no payment status, so that check not included

			// Not our paypal account receiving money, so invalid - 
			// and we key off txn_type for our conditional handling

			if (strtolower($paypal_account) != trim($this->post['receiver_email']) OR ! isset($this->post['txn_type']))
			{
				return FALSE;
			}
			
			//  User Valid?
			$this->EE->db->select('screen_name');
			$this->EE->db->where('member_id', $this->post['custom']); 
			$query = $this->EE->db->get('exp_members');
			
			if ($query->num_rows() == 0) return FALSE;
			
			$this->post['screen_name'] = $query->row('screen_name') ;

    		/** --------------------------------------------
			/**  The Subscription Types We Care About
			/**  - According to numerous posts around the internet, these are the only two we should really care about
			/** --------------------------------------------*/

			if (in_array($this->post['txn_type'], array('subscr_signup', 'subscr_eot', 'subscr_payment'))) 
			{
				if ( ! isset($this->post['subscr_id']))
				{
					return FALSE;
				}

	    		//  Successful Subscription Data- send it on!
    			if (isset($this->post['item_number']) && $this->post['item_number'] != '')
    			{
    				$this->perform_actions($this->post['item_number'], '', '', '', $this->post['txn_type']);
    			}
    		}
    		elseif (in_array($this->post['txn_type'], array('cart', 'web_accept')))
    		{

				//  Is this a repeat, perhaps?
				//  Note- subscription signups do not have a txn_id so we check only non-subscriptions
			
				$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_simple_commerce_purchases
								 WHERE txn_id = '".$this->EE->db->escape_str($this->post['txn_id'])."'");
								 
				$this->EE->db->where('txn_id', $this->post['txn_id']); 
				$this->EE->db->from('exp_simple_commerce_purchases');

				if ($this->EE->db->count_all_results()  > 0) return FALSE;

				//A regular purchase should be completed at this point
				if (trim($this->post['payment_status']) != 'Completed')
				{
					return FALSE;
				}
					
				if ($this->post['num_cart_items'] != '' && $this->post['num_cart_items'] > 0 && isset($_POST['item_number1']))
				{
					for($i=1; $i <= $this->post['num_cart_items']; ++$i)
					{
						if (($item_id = $this->EE->input->get_post('item_number'.$i)) !== FALSE)
						{
							$qnty	  = (isset($_POST['quantity'.$i]) && is_numeric($_POST['quantity'.$i])) ? $_POST['quantity'.$i] : 1;
							$subtotal = (isset($_POST['mc_gross_'.$i]) && is_numeric(str_replace('.', '', $_POST['mc_gross_'.$i]))) ? $_POST['mc_gross_'.$i] : 0;
						
							if ($subtotal == 0)
							{
								continue;
							}
						
							$this->perform_actions($item_id, $qnty, $subtotal, $i);
						}
					}
				}
				elseif(isset($this->post['item_number']) && $this->post['item_number'] != '' && is_numeric($this->post['mc_gross']) && $this->post['mc_gross'] > 0)
				{
					
					$this->perform_actions($this->post['item_number'], $this->post['quantity'], $this->post['mc_gross']);
				}
    		
    		}
    		else
    		{
				return FALSE;    		
    		}


			/** ------------------------------
			/**  Paypal Suggests Sending a 200 OK Response Back
			/** ------------------------------*/
			
			@header("HTTP/1.0 200 OK");
			@header("HTTP/1.1 200 OK");
			
			exit('Success');
		}
		elseif (stristr($result, 'INVALID'))
		{
			// Error Checking?
		
			@header("HTTP/1.0 200 OK");
			@header("HTTP/1.1 200 OK");
			
			exit('Invalid');
		}
	} 

	
	/** ----------------------------------------
	/**  Sing a Song, Have a Dance
	/** ----------------------------------------*/
	
	function curl_process($url)
	{
		$postdata = 'cmd=_notify-validate';

		foreach ($_POST as $key => $value)
		{
			// str_replace("\n", "\r\n", $value)
			// put line feeds back to CR+LF as that's how PayPal sends them out
			// otherwise multi-line data will be rejected as INVALID
			$postdata .= "&$key=".urlencode(stripslashes(str_replace("\n", "\r\n", $value)));
		}

		$ch=curl_init(); 
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_URL,$url); 
		curl_setopt($ch,CURLOPT_POST,1); 
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postdata); 

		// Start ob to prevent curl_exec from displaying stuff. 
		ob_start(); 
		curl_exec($ch);

		//Get contents of output buffer 
		$info=ob_get_contents(); 
		curl_close($ch);

		//End ob and erase contents.  
		ob_end_clean(); 

		return $info; 
	}

	
	
	/** ----------------------------------------
	/**  Drinking with Friends is Fun!
	/** ----------------------------------------*/
	
	function fsockopen_process($url)
	{ 
		$parts	= parse_url($url);
		$host	= $parts['host'];
		$path	= ( ! isset($parts['path'])) ? '/' : $parts['path'];
		$port	= ($parts['scheme'] == "https") ? '443' : '80';
		$ssl	= ($parts['scheme'] == "https") ? 'ssl://' : '';
		
		
		if (isset($parts['query']) && $parts['query'] != '')
		{
			$path .= '?'.$parts['query'];
		}
		
		$postdata = 'cmd=_notify-validate';

		foreach ($_POST as $key => $value)
		{
			// str_replace("\n", "\r\n", $value)
			// put line feeds back to CR+LF as that's how PayPal sends them out
			// otherwise multi-line data will be rejected as INVALID
			$postdata .= "&$key=".urlencode(stripslashes(str_replace("\n", "\r\n", $value)));
		}
		
		$info = '';

		$fp = @fsockopen($ssl.$host, $port, $error_num, $error_str, 8); 

		if (is_resource($fp))
		{
			fputs($fp, "POST {$path} HTTP/1.0\r\n"); 
			fputs($fp, "Host: {$host}\r\n"); 
			fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n"); 
			fputs($fp, "Content-Length: ".strlen($postdata)."\r\n"); 
			fputs($fp, "Connection: close\r\n\r\n"); 
			fputs($fp, $postdata . "\r\n\r\n");
			
			while($datum = fread($fp, 4096))
			{
				$info .= $datum;
			}

			@fclose($fp); 
		}
		
		return $info; 
	}

	
	
	/** ----------------------------------------
	/**  Perform Store Item Actions
	/** ----------------------------------------*/
	function perform_actions($item_id, $qnty, $subtotal, $num_in_cart='', $type='')
	{
		$query = $this->EE->db->query("SELECT wt.title as item_name, sc.* 
							 FROM exp_simple_commerce_items sc, exp_channel_titles wt
							 WHERE sc.entry_id = wt.entry_id 
							 AND sc.item_id = '".$this->EE->db->escape_str($item_id)."'");
							
		if ($query->num_rows() != 1)
    	{
    		return;
    	}
		
		$row = $query->row();

		$this->post['item_name']	= $row->item_name;
		$this->post['item_number']	= $item_id;
		$this->post['quantity']		= $qnty;
		$this->post['mc_gross']		= $subtotal;
		$this->post['member_id']	= $this->post['custom'];
			
		$customer_email_template	= $row->customer_email_template;	
		$admin_email_template		= $row->admin_email_template;
		$new_member_group			= $row->new_member_group;
			
        //  Type Specific Actions
		
		// we ignore subscr_cancel actions since they do not affect the current subscription
		if ($type == 'subscr_eot')
		{
			$new_member_group			= $row->member_group_unsubscribe;
			$admin_email_template		= $row->admin_email_template_unsubscribe;
			$customer_email_template	= $row->customer_email_template_unsubscribe;
			
			if ($this->end_subscription() === FALSE)
			{
				return FALSE;
			}
		}
		elseif ($type == 'subscr_signup')
		{
			if ( ! is_numeric($this->post['mc_amount3']) OR $this->post['mc_amount3'] <= 0)
			{
				return FALSE;			
			}

			if ($this->start_subscription($row) === FALSE)
			{
				return FALSE;
			}
			
			// Until payment goes through?  We do not complete and just put it in as pending 
			return;
			
		}
		elseif ($type == 'subscr_payment')
		{
			//if ( ! is_numeric($this->post['mc_amount3']) OR $this->post['mc_amount3'] <= 0)
			//{
			//	return FALSE;			
			//}

			if ($this->subscription_payment($row) === FALSE)
			{
				return FALSE;
			}
		}		
			
		/* -------------------------------------
		/*  'simple_commerce_perform_actions_start' hook.
		/*  - After a purchase is recorded, do more processing before EE's processing
		/*  - Added EE 1.5.1
		*/  
			if ($this->EE->extensions->active_hook('simple_commerce_perform_actions_start') === TRUE)
			{
				$edata = $this->EE->extensions->universal_call('simple_commerce_perform_actions_start', $this, $query->row());
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		/*
		/* -------------------------------------*/	
			
		if ($type == '')
		{
			/* --------------------------------
			/*  Check Price
			/*	- There is a small chance the Admin changed the price between
			/*	purchase and the receipt of the IP, so we give a small bit of
			/* 	wiggle room.  About 10%...
			/* --------------------------------*/
			
			$price = ($row->item_use_sale  == 'y') ? $row->item_sale_price : $row->item_regular_price;
			$cost  = $subtotal/$qnty;
			
			if ($cost < ($price * 0.9))
			{	
				return;
			}
		
			$data = array('txn_id' 			=> $this->post['txn_id'],
					  'member_id' 		=> $this->post['custom'],
					  'item_id'			=> $row->item_id,
					  'purchase_date'	=> $this->EE->localize->now,
					  'item_cost'		=> $cost,
					  'paypal_details'	=> serialize($this->post));
			
			if ( ! is_numeric($qnty) OR $qnty == 1)
			{
				$this->EE->db->insert('exp_simple_commerce_purchases', $data);

				$this->EE->db->where('item_id', $item_id);
				$this->EE->db->set('item_purchases', "item_purchases + 1", FALSE);
				$this->EE->db->update('exp_simple_commerce_items'); 				
			}
			else
			{
				for($i=0;  $i < $qnty; ++$i)
				{
					$this->EE->db->insert('exp_simple_commerce_purchases', $data); 	
				}
				
				$this->EE->db->where('item_id', $item_id);
				$this->EE->db->set('item_purchases', "item_purchases + {$qnty}", FALSE);
				$this->EE->db->update('exp_simple_commerce_items'); 				
			}
		}  // end non-sub entry
		
		
		//  New Member Group
		if ($new_member_group != '' && $new_member_group != 0)
		{
			$this->EE->db->where('member_id', $this->post['custom']);
			$this->EE->db->where('group_id !=', 1);
			$this->EE->db->update('exp_members', array('group_id' => $new_member_group)); 
		}
			

		//  Send Emails!

		$this->EE->load->library('email');
			
		if ($customer_email_template != '' && $customer_email_template != 0)
		{
			$this->EE->db->select('email');
			$result = $this->EE->db->get_where('exp_members', array('member_id' => $this->post['custom']));

			$cust_row = $result->row();
			$to = $cust_row->email;
								
			$this->EE->db->select('email_subject, email_body');
			$result = $this->EE->db->get_where('exp_simple_commerce_emails', array('email_id' => $customer_email_template));
						
			if ($result->num_rows() > 0)
			{
				$email = $result->row();
				$subject = $email->email_subject;
				$message = $email->email_body;
				
				foreach($this->post as $key => $value)
				{
					$subject = str_replace(LD.$key.RD, $value, $subject);
					$message = str_replace(LD.$key.RD, $value, $message);
				}

				// Load the text helper
				$this->EE->load->helper('text');
				
				$this->EE->email->from($this->EE->config->item('webmaster_email'), 
										$this->EE->config->item('site_name'));
				$this->EE->email->to($to);
				$this->EE->email->subject($subject);
				$this->EE->email->message(entities_to_ascii($message));
				$this->EE->email->send();
				$this->EE->email->EE_initialize();
			}
		}
			
		if ($row->admin_email_address != '' && $admin_email_template != '' && $admin_email_template != 0)
		{	
			$this->EE->db->select('email_subject, email_body');
			$result = $this->EE->db->get_where('exp_simple_commerce_emails', array('email_id' => $admin_email_template));									
									
				
			if ($result->num_rows() > 0)
			{
				$email = $result->row();
				$subject = $email->email_subject;
				$message = $email->email_body;
					
				foreach($this->post as $key => $value)
				{
					$subject = str_replace(LD.$key.RD, $value, $subject);
					$message = str_replace(LD.$key.RD, $value, $message);
				}

				// Load the text helper
				$this->EE->load->helper('text');
					
				$this->EE->email->from($this->EE->config->item('webmaster_email'), 
										$this->EE->config->item('site_name'));
				$this->EE->email->to($row->admin_email_address);
				$this->EE->email->subject($subject);
				$this->EE->email->message(entities_to_ascii($message));
				$this->EE->email->send();
				$this->EE->email->EE_initialize();
			}
		}
			


		/* -------------------------------------
		/*  'simple_commerce_perform_actions_end' hook.
		/*  - After a purchase is recorded, do more processing
		/*  - Added EE 1.5.1
		*/  
			if ($this->EE->extensions->active_hook('simple_commerce_perform_actions_end') === TRUE)
			{
				$edata = $this->EE->extensions->universal_call('simple_commerce_perform_actions_end', $this, $query->row());
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		/*
		/* -------------------------------------*/			
		
	} 

	

	/** ----------------------------------------
    /**  End Subscription for Item and USer
    /** ----------------------------------------*/

    function end_subscription()
    {
        //  Check for Subscription
		$this->EE->db->select('purchase_id, item_id');
		$this->EE->db->where('member_id', $this->post['custom']);
		$this->EE->db->where('paypal_subscriber_id', $this->post['subscr_id']);
		$query = $this->EE->db->get('exp_simple_commerce_purchases');

// what if multiple subscriptions?  item_number viable??? -rob1
// http://articles.techrepublic.com.com/5100-10878_11-5331883.html
// k- 0 is still subscribed.  If it has a date?  They were unsubscribed then.  So- null if not subscription type.
     

     
        if ($query->num_rows() == 0)
        {

        	return FALSE;
        }
 	
		$data = array('subscription_end_date' => $this->EE->localize->now);
		
		$this->EE->db->where('purchase_id', $query->row('purchase_id'));
		$this->EE->db->update('exp_simple_commerce_purchases', $data); 		
		
		$this->EE->db->where('item_id', $query->row('item_id'));
		$this->EE->db->set('current_subscriptions', "current_subscriptions - 1 ", FALSE);
		$this->EE->db->update('exp_simple_commerce_items'); 

		return TRUE;
    }
    /* END end_subscription() */
    

    /** ----------------------------------------
    /**  Start Subscription for Item
    /** ----------------------------------------*/

    function start_subscription($row)
    {
    	/* --------------------------------
		/*  Check Price
		/*	- There is a small chance the Admin changed the price between purchase and the receipt
		/* 	of the IP, so we give a small bit of wiggle room.  About 10%...
		/* --------------------------------*/
		
		$price = ($row->item_use_sale == 'y') ? $row->item_sale_price : $row->item_regular_price;
		
		if ($this->post['mc_amount3'] < ($price * 0.9))
		{	
			return FALSE;
		}
		
		/** --------------------------------------------
        /**  Check Subscription!
        /** --------------------------------------------*/
        
        // period3: Regular subscription interval in days, weeks, months, or years (ex: a 4 day interval is "period3: 4 D")
        
        $period = $row->subscription_frequency.' '.strtoupper(substr($row->subscription_frequency_unit, 0, 1));
        
        if ( ! isset($this->post['period3']) OR trim($this->post['period3']) != $period)
        {
        	return FALSE;
        }

        if ( ! isset($this->post['recurring']) OR $this->post['recurring'] != 1)
        {
        	return FALSE;
        }

        //  Insert Subscription

		$data = array('txn_id' 					=> 'pending',
					  'member_id' 				=> $this->post['custom'],
					  'item_id'					=> $row->item_id,
					  'purchase_date'			=> $this->EE->localize->now,
					  'item_cost'				=> $this->post['mc_amount3'],
					  'paypal_details'			=> serialize($this->post),
					  'paypal_subscriber_id'	=> $this->post['subscr_id']);
		
		$this->EE->db->insert('exp_simple_commerce_purchases', $data);
		
		// Don't update count until it's paid
		
		//$this->EE->db->where('item_id', $row->item_id);
		//$this->EE->db->set('item_purchases', "item_purchases + 1", FALSE);
		//$this->EE->db->set('current_subscriptions', "current_subscriptions + 1", FALSE);		
		//$this->EE->db->update('exp_simple_commerce_items');		
    
    	return TRUE;
    } 
	/* END start_subscription() */


	function subscription_payment($row)
	{

        //  Check for Subscription Sign-up
		$this->EE->db->select('purchase_id, item_id');
		$this->EE->db->where('member_id', $this->post['custom']);
		$this->EE->db->where('paypal_subscriber_id', $this->post['subscr_id']);
		$query = $this->EE->db->get('exp_simple_commerce_purchases');

// what if multiple subscriptions?  item_number viable??? -rob1
// http://articles.techrepublic.com.com/5100-10878_11-5331883.html
// k- 0 is still subscribed.  If it has a date?  They were unsubscribed then.  So- null if not subscription type.
        					 
        if ($query->num_rows() == 0)
        {
        	return FALSE;
        }

		$data = array('txn_id' => $this->post['txn_id']);
		

		$this->EE->db->where('paypal_subscriber_id', $this->post['subscr_id']);
		$this->EE->db->update('exp_simple_commerce_purchases', $data);

		$this->EE->db->where('item_id', $row->item_id);
		$this->EE->db->set('item_purchases', "item_purchases + 1", FALSE);
		$this->EE->db->set('current_subscriptions', "current_subscriptions + 1", FALSE);		
		$this->EE->db->update('exp_simple_commerce_items');	
		
		return TRUE;
		
			
	}


	/** ----------------------------------------
	/**  Encrypt Button
	/** ----------------------------------------*/
	
	function encrypt_data($params = array(), $type='button')
	{	
		/** -----------------------------
		/**  Certificates, Keys, and TMP Files
		/** -----------------------------*/
	
		$public_certificate	= file_get_contents($this->public_certificate);
		$private_key		= file_get_contents($this->private_key);
		$paypal_certificate	= file_get_contents($this->paypal_certificate);
		
		$tmpin_file  = tempnam($this->temp_path, 'paypal_');
		$tmpout_file = tempnam($this->temp_path, 'paypal_');
		$tmpfinal_file = tempnam($this->temp_path, 'paypal_');
		
		/** -----------------------------
		/**  Prepare Our Data
		/** -----------------------------*/
		
		$rawdata = '';
		$params['cert_id'] = $this->certificate_id;
		
		foreach ($params as $name => $value)
		{
			$rawdata .= "$name=$value\n";
		}
		
		if ( ! $fp = fopen($tmpin_file, 'w'))
		{
			exit('failure');
		}
		
		fwrite($fp, rtrim($rawdata));
		fclose($fp);
		
		/** -----------------------------
		/**  Sign Our File
		/** -----------------------------*/
		
		if ( ! openssl_pkcs7_sign($tmpin_file, $tmpout_file, $public_certificate, $private_key, array(), PKCS7_BINARY))
		{
			exit("Could not sign encrypted data: " . openssl_error_string());
		}
		
		$data = explode("\n\n", file_get_contents($tmpout_file));
		
		$data = base64_decode($data['1']);
		
		if ( ! $fp = fopen($tmpout_file, 'w'))
		{
			exit("Could not open temporary file '$tmpin_file')");
		}
		
		fwrite($fp, $data);
		fclose($fp);
		
		/** -----------------------------
		/**  Encrypt Our Data
		/** -----------------------------*/
		
		if ( ! openssl_pkcs7_encrypt($tmpout_file, $tmpfinal_file, $paypal_certificate, array(), PKCS7_BINARY))
		{
			exit("Could not encrypt data:" . openssl_error_string());
		}
		
		$encdata = file_get_contents($tmpfinal_file, FALSE);
		
		if (empty($encdata))
		{
			exit("Encryption and signature of data failed.");
		}
		
		$encdata = explode("\n\n", $encdata);
		$encdata = trim(str_replace("\n", '', $encdata['1']));
		$encdata = "-----BEGIN PKCS7-----".$encdata."-----END PKCS7-----";
		
		@unlink($tmpfinal_file);
		@unlink($tmpin_file);
		@unlink($tmpout_file);
		
		/** -----------------------------
		/**  Return The Encrypted Data String
		/** -----------------------------*/
		
		return $encdata;

	}

 

	/** -------------------------------------
	/**  Clean the values for use in URLs
	/** -------------------------------------*/
	function prep_val($str)
	{
		// Oh, PayPal, the hoops I must jump through to woo thee...
		// PayPal is displaying its cart as UTF-8, sending UTF-8 headers, but when
		// processing the form data, is obviously wonking with it.  This will force
		// accented characters in item names to display properly on the shopping cart
		// but alas only for unencrypted data.  PayPal won't accept this same
		// workaround for encrypted form data.

		// Load the typography helper so we can do entity_decode()
		$this->EE->load->helper('typography');

		$str = str_replace('&amp;', '&', $str);
		$str = urlencode(utf8_decode(entity_decode($str, 'utf-8')));
		
		return $str;
	}

	
}



/* End of file mod.simple_commerce.php */
/* Location: ./system/expressionengine/modules/simple_commerce/mod.simple_commerce.php */