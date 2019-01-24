<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Simple Commerce Module
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
	function __construct()
	{
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

		if (ee()->config->item('sc_encrypt_buttons') === 'y' && function_exists('openssl_pkcs7_sign'))
		{
			$this->encrypt = TRUE;

			foreach(array('certificate_id', 'public_certificate', 'private_key', 'paypal_certificate') as $val)
			{
				if (ee()->config->item('sc_'.$val) === FALSE OR ee()->config->item('sc_'.$val) == '')
				{
					$this->encrypt = FALSE;
					break;
				}
				else
				{
					$this->$val = ee()->config->item('sc_'.$val);
				}
			}

			// Not required
			if ($this->encrypt === TRUE && ee()->config->item('sc_temp_path') !== FALSE)
			{
				$this->temp_path = ee()->config->item('sc_temp_path');
			}

		}
	}



	/** ----------------------------------------
	/**  Output Item Info
	/** ----------------------------------------*/
	function purchase()
	{
		if (($entry_id = ee()->TMPL->fetch_param('entry_id')) === FALSE) return;
		if (($success = ee()->TMPL->fetch_param('success')) === FALSE) return;
		$cached = FALSE;

		$paypal_account = ( ! ee()->config->item('sc_paypal_account')) ? ee()->config->item('webmaster_email') : ee()->config->item('sc_paypal_account');
		$cancel	 		= ( ! ee()->TMPL->fetch_param('cancel'))  ? ee()->functions->fetch_site_index() : ee()->TMPL->fetch_param('cancel');
		$currency		= ( ! ee()->TMPL->fetch_param('currency'))  ? 'USD' : ee()->TMPL->fetch_param('currency');
		$country_code	= ( ! ee()->TMPL->fetch_param('country_code')) ? 'US' : strtoupper(ee()->TMPL->fetch_param('country_code'));
		$show_disabled  = ( ee()->TMPL->fetch_param('show_disabled') == 'yes') ? TRUE : FALSE;

		if (substr($success, 0, 4) !== 'http')
		{
			$success = ee()->functions->create_url($success);
		}

		if (substr($cancel, 0, 4) !== 'http')
		{
			$cancel = ee()->functions->create_url($cancel);
		}

		if ($show_disabled !== TRUE)
		{
			ee()->db->where('simple_commerce_items.item_enabled', 'y');
		}

		$query = ee()->db->select('t.title as item_name, simple_commerce_items.*')
		  		->where('simple_commerce_items.entry_id', $entry_id)
				->where('simple_commerce_items.entry_id = t.entry_id', NULL, FALSE)
		  		->from('simple_commerce_items')
		  		->from('channel_titles t')
		  		->limit(1)
		  		->get();


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
										'custom'			=> ee()->session->userdata['member_id']
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
										'custom'			=> ee()->session->userdata['member_id']
										);

		if ($this->encrypt === TRUE)
		{
			$url = $subscribe['action'].'?cmd=_s-xclick&amp;encrypted='.urlencode($this->encrypt_data($subscribe['hidden_fields']));
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
												'custom'			=> ee()->session->userdata['member_id']
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

		$output = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $variables);


		foreach (ee()->TMPL->var_pair as $key => $val)
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
				$output = ee()->TMPL->delete_var_pairs($key, $key, $output);
				continue;
			}

			$data['id']		= 'paypal_form_'.$row['item_id'].'_'.$key;
			$data['secure'] = FALSE;

			$form	= ee()->functions->form_declaration($data).
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
			$data['class']	= ee()->TMPL->form_class;
			$data['secure'] = FALSE;
			$data = $hidden;

			$form	= ee()->functions->form_declaration($data).
					  '<input type="submit" name="submit" value="\\1" class="paypal_button" />'."\n".
					  '</form>'."\n\n";
			return 'dude';
	}

	/** -------------------------------------
	/**  Round Money
	/** -------------------------------------*/

	function round_money($value, $dec=2)
	{
		$decimal = (ee()->TMPL->fetch_param('decimal') == ',')  ? ',' : '.';

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

			ee()->load->library('email');
			$debug_to = ($this->debug_email_address == '') ? ee()->config->item('webmaster_email') : $this->debug_email_address;

			ee()->email->from(ee()->config->item('webmaster_email'),
										ee()->config->item('site_name'));
			ee()->email->to($debug_to);
			ee()->email->subject('EE Debug: Incoming IPN Response');
			ee()->email->message($msg);
			ee()->email->mailtype = ee()->config->item('mail_format');
			ee()->email->send();
			ee()->email->EE_initialize();

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


		$paypal_account = ( ! ee()->config->item('sc_paypal_account')) ? ee()->config->item('webmaster_email') : ee()->config->item('sc_paypal_account');

		/** ----------------------------------------
		/**  Prep, Prep, Prep
		/** ----------------------------------------*/

		foreach($this->possible_post as $value)
		{
			$this->post[$value] = '';
		}

		foreach($_POST as $key => $value)
		{
			$this->post[$key] = ee('Security/XSS')->clean($value);
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
			if (ee()->extensions->active_hook('simple_commerce_evaluate_ipn_response') === TRUE)
			{
				$result = ee()->extensions->call('simple_commerce_evaluate_ipn_response', $this, $result);
				if (ee()->extensions->end_script === TRUE) return;
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

			if (trim(strtolower($paypal_account)) != trim(strtolower($this->post['receiver_email'])) OR ! isset($this->post['txn_type']))
			{
				return FALSE;
			}

			//  User Valid?
			$query = ee()->db->select('screen_name')
						->where('member_id', $this->post['custom'])
						->get('members');

			if ($query->num_rows() == 0) return FALSE;

			$this->post['screen_name'] = $query->row('screen_name');

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

				ee()->db->where('txn_id', $this->post['txn_id']);
				ee()->db->from('simple_commerce_purchases');

				if (ee()->db->count_all_results()  > 0) return FALSE;

				//A regular purchase should be completed at this point
				if (trim($this->post['payment_status']) != 'Completed')
				{
					return FALSE;
				}

				if ($this->post['num_cart_items'] != '' && $this->post['num_cart_items'] > 0 && isset($_POST['item_number1']))
				{
					for($i=1; $i <= $this->post['num_cart_items']; ++$i)
					{
						if (($item_id = ee()->input->get_post('item_number'.$i)) !== FALSE)
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
			// Note: get_magic_quotes_gpc FALSE as of PHP 5.4.0
			$stripped = (get_magic_quotes_gpc()) ? stripslashes(str_replace("\n", "\r\n", $value)) : str_replace("\n", "\r\n", $value);
			$postdata .= "&$key=".urlencode($stripped);
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
			// Note: get_magic_quotes_gpc FALSE as of PHP 5.4.0
			$stripped = (get_magic_quotes_gpc()) ? stripslashes(str_replace("\n", "\r\n", $value)) : str_replace("\n", "\r\n", $value);
			$postdata .= "&$key=".urlencode($stripped);
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
		$query = ee()->db->select('t.title as item_name, simple_commerce_items.*')
		  		->where('simple_commerce_items.entry_id = t.entry_id', NULL, FALSE)
		  		->where('simple_commerce_items.item_id', $item_id)
		  		->from('simple_commerce_items')
		  		->from('channel_titles t')
		  		->get();

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
			if (ee()->extensions->active_hook('simple_commerce_perform_actions_start') === TRUE)
			{
				ee()->extensions->call('simple_commerce_perform_actions_start', $this, $query->row());
				if (ee()->extensions->end_script === TRUE) return;
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
					  'purchase_date'	=> ee()->localize->now,
					  'item_cost'		=> $cost,
					  'paypal_details'	=> serialize($this->post));

			if ( ! is_numeric($qnty) OR $qnty == 1)
			{
				ee()->db->insert('simple_commerce_purchases', $data);

				ee()->db->where('item_id', $item_id);
				ee()->db->set('item_purchases', "item_purchases + 1", FALSE);
				ee()->db->update('simple_commerce_items');
			}
			else
			{
				for($i=0;  $i < $qnty; ++$i)
				{
					ee()->db->insert('simple_commerce_purchases', $data);
				}

				ee()->db->where('item_id', $item_id);
				ee()->db->set('item_purchases', "item_purchases + {$qnty}", FALSE);
				ee()->db->update('simple_commerce_items');
			}
		}  // end non-sub entry


		//  New Member Group
		if ($new_member_group != '' && $new_member_group != 0)
		{
			ee()->db->where('member_id', $this->post['custom']);
			ee()->db->where('group_id !=', 1);
			ee()->db->update('members', array('group_id' => $new_member_group));
		}


		//  Send Emails!

		ee()->load->library('email');

		if ($customer_email_template != '' && $customer_email_template != 0)
		{
			ee()->db->select('email');
			$result = ee()->db->get_where('members', array('member_id' => $this->post['custom']));

			$cust_row = $result->row();
			$to = $cust_row->email;

			ee()->db->select('email_subject, email_body');
			$result = ee()->db->get_where('simple_commerce_emails', array('email_id' => $customer_email_template));

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
				ee()->load->helper('text');

				ee()->email->from(ee()->config->item('webmaster_email'),
										ee()->config->item('site_name'));
				ee()->email->to($to);
				ee()->email->subject($subject);
				ee()->email->message(entities_to_ascii($message));
				ee()->email->mailtype = ee()->config->item('mail_format');
				ee()->email->send();
				ee()->email->EE_initialize();
			}
		}

		if ($row->admin_email_address != '' && $admin_email_template != '' && $admin_email_template != 0)
		{
			ee()->db->select('email_subject, email_body');
			$result = ee()->db->get_where('simple_commerce_emails', array('email_id' => $admin_email_template));


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
				ee()->load->helper('text');

				ee()->email->from(ee()->config->item('webmaster_email'),
										ee()->config->item('site_name'));
				ee()->email->to($row->admin_email_address);
				ee()->email->subject($subject);
				ee()->email->message(entities_to_ascii($message));
				ee()->email->mailtype = ee()->config->item('mail_format');
				ee()->email->send();
				ee()->email->EE_initialize();
			}
		}



		/* -------------------------------------
		/*  'simple_commerce_perform_actions_end' hook.
		/*  - After a purchase is recorded, do more processing
		/*  - Added EE 1.5.1
		*/
			if (ee()->extensions->active_hook('simple_commerce_perform_actions_end') === TRUE)
			{
				ee()->extensions->call('simple_commerce_perform_actions_end', $this, $query->row());
				if (ee()->extensions->end_script === TRUE) return;
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
		$query = ee()->db->select('purchase_id, item_id')
					->where('member_id', $this->post['custom'])
					->where('paypal_subscriber_id', $this->post['subscr_id'])
					->get('simple_commerce_purchases');

		// What if multiple subscriptions?
		// Note that paypal_subscriber_id is unique to each subscription despite the way it sounds

		// k- 0 is still subscribed.  If it has a date?
		// They were unsubscribed then.  So- null if not subscription type.

        if ($query->num_rows() == 0)
        {
        	return FALSE;
        }

		$data = array('subscription_end_date' => ee()->localize->now);

		ee()->db->where('purchase_id', $query->row('purchase_id'));
		ee()->db->update('simple_commerce_purchases', $data);

		ee()->db->where('item_id', $query->row('item_id'));
		ee()->db->set('current_subscriptions', "current_subscriptions - 1 ", FALSE);
		ee()->db->update('simple_commerce_items');

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
					  'purchase_date'			=> ee()->localize->now,
					  'item_cost'				=> $this->post['mc_amount3'],
					  'paypal_details'			=> serialize($this->post),
					  'paypal_subscriber_id'	=> $this->post['subscr_id']);

		ee()->db->insert('simple_commerce_purchases', $data);

		// Don't update count until it's paid

    	return TRUE;
    }
	/* END start_subscription() */


	function subscription_payment($row)
	{
        //  Check for Subscription Sign-up
		ee()->db->select('purchase_id, item_id');
		ee()->db->where('member_id', $this->post['custom']);
		ee()->db->where('paypal_subscriber_id', $this->post['subscr_id']);
		$query = ee()->db->get('simple_commerce_purchases');

		// What if multiple subscriptions?
		// Note that paypal_subscriber_id is unique to each subscription despite the way it sounds
		// k- 0 is still subscribed.  If it has a date?
		// They were unsubscribed then.  So- null if not subscription type.

        // Note- it's possible to get the payment notification before the start_subscription
		// data- in which case, num_rows will be empty BUT we want Paypal to resend
		if ($query->num_rows() == 0)
        {
			//return 400 header so paypal resends
			@header("HTTP/1.1 400 Bad Request");
			exit('Invalid');
        }

		$data = array('txn_id' => $this->post['txn_id']);


		ee()->db->where('paypal_subscriber_id', $this->post['subscr_id']);
		ee()->db->update('simple_commerce_purchases', $data);

		ee()->db->where('item_id', $row->item_id);
		ee()->db->set('item_purchases', "item_purchases + 1", FALSE);
		ee()->db->set('current_subscriptions', "current_subscriptions + 1", FALSE);
		ee()->db->update('simple_commerce_items');

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
		ee()->load->helper('typography');

		$str = str_replace('&amp;', '&', $str);
		$str = urlencode(ee('Security/XSS')->entity_decode($str, 'utf-8'));

		return $str;
	}


}

// EOF
