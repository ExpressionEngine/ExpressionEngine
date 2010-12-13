<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Moblog Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Moblog {

	var $cache_name		= 'moblog_cache';		// Name of cache directory
	var $url_title_word = 'moblog';				// If duplicate url title, this is added along with number
	var $message_array  = array();				// Array of return messages
	var $return_data 	= ''; 					// When silent mode is off
	var $silent			= ''; 					// true/false (string) - Returns error information
	var $moblog_array	= array(); 				// Row information for moblog being processed

	var $fp				= ''; 					// fopen resource
	var $pop_newline	= "\n";					// Newline for POP Server. Switch to \r\n for Microsoft servers
	var $total_size		= 0;					// Total size of emails being checked in bytes
	var $checked_size	= 0;					// Accumulated size of emails checked thus far in bytes
	var $max_size		= 5;					// Maximum amount of email to check, in MB
	var $email_sizes	= array();				// The sizes of the new emails being checked, in bytes

	var $boundary 		= FALSE; 				// Boundary marker in emails
	var $multi_boundary = '';					// Boundary for multipart content types
	var	$newline		= '1n2e3w4l5i6n7e8'; 	// Newline replacement
	var $charset		= 'auto';				// Character set for main body of email

	var $author  		= '';					// Author of current email being processed
	var $body			= '';					// Main text contents of email being processed
	var $sender_email	= '';					// Email address that sent email
	var $uploads		= 0;					// Number of file uploads for this check
	var $email_files	= array();				// Array containing filenames of uploads for this email
	var $emails_done	= 0;					// Number of emails processed
	var $entries_added	= 0;					// Number of entries added
	var $pings_sent		= 0;					// Number of servers pinged
	var $upload_dir_code = '';					// {filedir_2} for entry's
	var $upload_path	= '';					// Server path for upload directory
	var $entry_data		= array();				// Data for entry's custom fields
	var $post_data		= array();				// Post data retrieved from email being processed: Subject, IP, Categories, Status
	var $template		= '';					// Moblog's template
	var $sticky			= 'n';					// Default Sticky Value

	// These settings are for a specific problem with AT&T phones
	var $attach_as_txt	= FALSE;				// Email's Message as txt file?
	var $attach_text	= '';					// If $attach_as_txt is true, this is the text
	var $attach_name	= '';					// If $attach_as_txt is true, this is the name

	var $time_offset	= '5';					// Number of seconds entries are offset by negatively, higher if you are putting in many entries

	var $movie			= array();				// Suffixes for accepted movie files
	var $audio			= array();				// Suffixes for accepted audio files
	var $image			= array();				// Suffixes for accepted image files
	var $files			= array();				// Suffixes for other types of accepted files

	var $txt_override	= FALSE;				// When set to TRUE, all .txt files are treated as message text


	// ------------------------------------------------------------------------
	
	/**
	 * 	Constructor
	 */
	function Moblog()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		/** -----------------------------
		/**  Default file formats
		/** -----------------------------*/

		$this->movie = array('3gp','mov','mpg','avi','movie');
		$this->audio = array('mid','midi','mp2','mp3','aac','mp4','aif','aiff','aifc','ram','rm','rpm','wav','ra','rv','wav');
		$this->image = array('bmp','gif','jpeg','jpg','jpe','png','tiff','tif');
		$this->files = array('doc','xls','zip','tar','tgz','swf','sit','php','txt','html','asp','js','rtf', 'pdf');

		if ( ! defined('LD'))
			define('LD', '{');

		if ( ! defined('RD'))
			define('RD', '}');

		if ( ! defined('SLASH'))
			define('SLASH',	'&#47;');

		$this->max_size = $this->max_size * 1024 * 1000;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Check for Expired Moblogs
	 */
	function check()
	{
		$which 			= ( ! $this->EE->TMPL->fetch_param('which'))	? '' : $this->EE->TMPL->fetch_param('which');
		$this->silent	= ( ! $this->EE->TMPL->fetch_param('silent'))	? 'true' : $this->EE->TMPL->fetch_param('silent');

		if ($which == '')
		{
			$this->return_data = ($this->silent == 'true') ? '' : 'No Moblog Indicated';
			return $this->return_data ;
		}

		$this->EE->lang->loadfile('moblog');

		$sql = "SELECT * FROM exp_moblogs WHERE moblog_enabled = 'y'";
		$sql .= ($which == 'all') ? '' : $this->EE->functions->sql_andor_string($which, 'moblog_short_name', 'exp_moblogs');
		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			$this->return_data = ($this->silent == 'true') ? '' : $this->EE->lang->line('no_moblogs');
			return $this->return_data;
		}

		// Check Cache

		if ( ! @is_dir(APPPATH.'cache/'.$this->cache_name))
		{
			if ( ! @mkdir(APPPATH.'cache/'.$this->cache_name, DIR_WRITE_MODE))
			{
				$this->return_data = ($this->silent == 'true') ? '' : $this->EE->lang->line('no_cache');
				return $this->return_data;
			}
		}

		@chmod(APPPATH.'cache/'.$this->cache_name, DIR_WRITE_MODE);

		//$this->EE->functions->delete_expired_files(APPPATH.'cache/'.$this->cache_name);

		$expired = array();

		foreach($query->result_array() as $row)
		{
			$cache_file = APPPATH.'cache/'.$this->cache_name.'/t_moblog_'.$row['moblog_id'];

			if ( ! file_exists($cache_file) OR (time() > (filemtime($cache_file) + ($row['moblog_time_interval'] * 60))))
			{
				$this->set_cache($row['moblog_id']);
				$expired[] = $row['moblog_id'];
			}
			elseif ( ! $fp = @fopen($cache_file, FOPEN_READ_WRITE))
			{
				if ($this->silent == 'false')
				{
					$this->return_data .= '<p><strong>'.$row['moblog_full_name'].'</strong><br />'.
									$this->EE->lang->line('no_cache')."\n</p>";
				}
			}
		}

		if (count($expired) == 0)
		{
			$this->return_data = ($this->silent == 'true') ? '' : $this->EE->lang->line('moblog_current');
			return $this->return_data;
		}

		/** ------------------------------
		/**  Process Expired Moblogs
		/** ------------------------------*/

		foreach($query->result_array() as $row)
		{
			if (in_array($row['moblog_id'],$expired))
			{
				$this->moblog_array = $row;

				if ($this->moblog_array['moblog_email_type'] == 'imap')
				{
					if ( ! $this->check_imap_moblog())
					{
						if ($this->silent == 'false' && count($this->message_array) > 0)
						{
							$this->return_data .= '<p><strong>'.$this->moblog_array['moblog_full_name'].'</strong><br />'.
										$this->errors()."\n</p>";
						}
					}
					}
				else
				{
					if ( ! $this->check_pop_moblog())
					{
						if ($this->silent == 'false' && count($this->message_array) > 0)
						{
							$this->return_data .= '<p><strong>'.$this->moblog_array['moblog_full_name'].'</strong><br />'.
										$this->errors()."\n</p>";
						}
					}
				}

				$this->message_array = array();
			}
		}

		if ($this->silent == 'false')
		{
			$this->return_data .= $this->EE->lang->line('moblog_successful_check')."<br />\n";
			$this->return_data .= $this->EE->lang->line('emails_done')." {$this->emails_done}<br />\n";
			$this->return_data .= $this->EE->lang->line('entries_added')." {$this->entries_added}<br />\n";
			$this->return_data .= $this->EE->lang->line('attachments_uploaded')." {$this->uploads}<br />\n";
			$this->return_data .= $this->EE->lang->line('pings_sent')." {$this->pings_sent}<br />\n";
		}

		return $this->return_data ;
	}



	/** -------------------------------------
	/**  Set cache
	/** -------------------------------------*/
	function set_cache($moblog_id)
	{
		$cache_file = APPPATH.'cache/'.$this->cache_name.'/t_moblog_'.$moblog_id;

		if ($fp = @fopen($cache_file, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, 'hi');
			flock($fp, LOCK_UN);
			fclose($fp);
		}

		@chmod($cache_file, FILE_WRITE_MODE);

	}


	/** -------------------------------------
	/**  Return errors
	/** -------------------------------------*/
	function errors()
	{
		$message = '';

		if (count($this->message_array) == 0 OR $this->silent == 'true')
		{
			return $message;
		}

		foreach($this->message_array as $row)
		{
			$message .= ($message == '') ? '' : "<br />\n";
			$message .= ( ! $this->EE->lang->line($row)) ? $row : $this->EE->lang->line($row);
		}

		return $message;

	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Check Pop3 Moblog
	 *
	 * 	
	 */
	
	function check_pop_moblog()
	{
		/** ------------------------------
		/**  Email Login Check
		/** ------------------------------*/
		
		$port = 110;
		$ssl = (substr($this->moblog_array['moblog_email_server'], 0, 6) == 'ssl://');
		
		if ($ssl OR stripos($this->moblog_array['moblog_email_server'], 'gmail') !== FALSE)
		{
			if ( ! $ssl)
			{
				$this->moblog_array['moblog_email_server'] = 'ssl://'.$this->moblog_array['moblog_email_server'];
			}

			$port = 995;
		}

		if ( ! $this->fp = @fsockopen($this->moblog_array['moblog_email_server'], $port, $errno, $errstr, 20))
		{
			$this->message_array[] = 'no_server_connection';
			return FALSE;
		}

		if (strncasecmp(fgets($this->fp, 1024), '+OK', 3) != 0)
		{
			$this->message_array[] = 'invalid_server_response';
			@fclose($this->fp);
			return FALSE;
		}

		if (strncasecmp($this->pop_command("USER ".base64_decode($this->moblog_array['moblog_email_login'])), '+OK', 3) != 0)
		{
			// Windows servers something require a different line break.
			// So, we change the line break and try again.

			$this->pop_newline = "\r\n";

			if (strncasecmp($this->pop_command("USER ".base64_decode($this->moblog_array['moblog_email_login'])), '+OK', 3) != 0)
			{
				$this->message_array[] = 'invalid_username';
				$line = $this->pop_command("QUIT");
				@fclose($this->fp);
				return FALSE;
			}
		}

		if (strncasecmp($this->pop_command("PASS ".base64_decode($this->moblog_array['moblog_email_password'])), '+OK', 3) != 0)
		{			
			$this->message_array[] = 'invalid_password';
			$line = $this->pop_command("QUIT");
			@fclose($this->fp);
			return FALSE;
		}


		/** ------------------------------
		/**  Got Mail?
		/** ------------------------------*/

		if ( ! $line = $this->pop_command("STAT"))
		{
			$this->message_array[] = 'unable_to_retrieve_emails';
			$line = $this->pop_command("QUIT");
			@fclose($this->fp);
			return FALSE;
		}

		$stats = explode(" ", $line);
		$total = ( ! isset($stats['1'])) ? 0 : $stats['1'];
		$this->total_size = ( ! isset($stats['2'])) ? 0 : $stats['2'];

		if ($total == 0)
		{
			$this->message_array[] = 'no_valid_emails';
			$line = $this->pop_command("QUIT");
			@fclose($this->fp);
			return;
		}

		/** ------------------------------
		/**  Determine Sizes of Emails
		/** ------------------------------*/

		if ($this->total_size > $this->max_size)
		{
			if ( ! $line = $this->pop_command("LIST"))
			{
				$this->message_array[] = 'unable_to_retrieve_emails';
				$line = $this->pop_command("QUIT");
				@fclose($this->fp);
				return FALSE;
			}

			do {
				$data = fgets($this->fp, 1024);
				$data = $this->iso_clean($data);

				if(empty($data) OR trim($data) == '.')
				{
					break;
				}

				$x = explode(' ', $data);

				if (count($x) == 1) break;

				$this->email_sizes[$x['0']] = $x['1'];

			} while (strncmp($data, ".\r\n", 3) != 0);
		}


		/** ------------------------------
		/**  Find Valid Emails
		/** ------------------------------*/

		$valid_emails = array();
		$valid_froms = explode("|",$this->moblog_array['moblog_valid_from']);

		for ($i=1; $i <= $total; $i++)
		{
			if (strncasecmp($this->pop_command("TOP {$i} 0"), '+OK', 3) != 0)
			{
				$line = $this->pop_command("QUIT");
				@fclose($this->fp);
				return FALSE;
			}

			$valid_subject = 'n';
			$valid_from = ($this->moblog_array['moblog_valid_from'] != '') ? 'n' : 'y';
			$str = fgets($this->fp, 1024);

			while (strncmp($str, ".\r\n", 3) != 0)
			{
				$str = fgets($this->fp, 1024);
				$str = $this->iso_clean($str);

				if (empty($str))
				{
					break;
				}

				// ------------------------
				// Does email contain correct prefix? (if prefix is set)
				// Liberal interpretation of prefix location
				// ------------------------

				if($this->moblog_array['moblog_subject_prefix'] == '')
				{
					$valid_subject = 'y';
				}
				elseif (preg_match("/Subject:(.*)/", $str, $subject))
				{
					if(strpos(trim($subject['1']), $this->moblog_array['moblog_subject_prefix']) !== FALSE)
					{
						$valid_subject = 'y';
					}
				}

				if ($this->moblog_array['moblog_valid_from'] != '')
				{
					if (preg_match("/From:\s*(.*)\s*\<(.*)\>/", $str, $from) OR preg_match("/From:\s*(.*)\s*/", $str, $from))
					{
						$address = ( ! isset($from['2'])) ? $from['1'] : $from['2'];

						if(in_array(trim($address),$valid_froms))
						{
							$valid_from = 'y';
						}
					}
				}
			}

			if ($valid_subject == 'y' && $valid_from == 'y')
			{
				$valid_emails[] = $i;
			}
		}

		unset($subject);
		unset($str);

		if (count($valid_emails) == 0)
		{
			$this->message_array[] = 'no_valid_emails';
			$line = $this->pop_command("QUIT");
			@fclose($this->fp);
			return;
		}

		/** ------------------------------
		/**  Process Valid Emails
		/** ------------------------------*/

		foreach ($valid_emails as $email_id)
		{
			// Reset Variables
			$this->post_data = array();
			$this->email_files = array();
			$this->body = '';
			$this->sender_email = '';
			$this->entry_data = array();
			$email_data = '';
			$this->attach_as_txt = FALSE;

			/** ------------------------------------------
			/**  Do Not Exceed Max Size During a Moblog Check
			/** ------------------------------------------*/

			if ($this->total_size > $this->max_size && isset($this->email_sizes[$email_id]))
			{
				if ($this->checked_size + $this->email_sizes[$email_id] > $this->max_size)
				{
					continue;
				}

				$this->checked_size += $this->email_sizes[$email_id];
			}

			/** ---------------------------------------
			/**  Failure does happen at times
			/** ---------------------------------------*/

			if (strncasecmp($this->pop_command("RETR {$email_id}"), '+OK', 3) != 0)
			{
				continue;
			}

			// Under redundant, see redundant
			$this->post_data['subject'] = 'Moblog Entry';
			$this->post_data['ip'] = '127.0.0.1';
			$format_flow = 'n';

			/** ------------------------------
			/**  Retrieve Email data
			/** ------------------------------*/

			do{

				$data = fgets($this->fp, 1024);
				$data = $this->iso_clean($data);

				if(empty($data))
				{
					break;
				}

				if ($format_flow == 'n' && stristr($data,'format=flowed'))
				{
					$format_flow = 'y';
				}

				$email_data .= $data;

			} while (strncmp($data, ".\r\n", 3) != 0);

			//echo $email_data."<br /><br />\n\n";

			if (preg_match("/charset=(.*?)(\s|".$this->newline.")/is", $email_data, $match))
			{
				$this->charset = trim(str_replace(array("'", '"', ';'), '', $match['1']));
			}

			/** --------------------------
			/**  Set Subject, Remove Moblog Prefix
			/** --------------------------*/

			if (preg_match("/Subject:(.*)/", trim($email_data), $subject))
			{
				if($this->moblog_array['moblog_subject_prefix'] == '')
				{
					$this->post_data['subject'] = (trim($subject['1']) != '') ? trim($subject['1']) : 'Moblog Entry';
				}
				elseif (strpos(trim($subject['1']), $this->moblog_array['moblog_subject_prefix']) !== FALSE)
				{
					$str_subject = str_replace($this->moblog_array['moblog_subject_prefix'],'',$subject['1']);
					$this->post_data['subject'] = (trim($str_subject) != '') ? trim($str_subject) : 'Moblog Entry';
				}

				// If the subject header was read with imap_utf8() in the iso_clean() method, then
				// we don't need to do anything further
				if ( ! function_exists('imap_utf8'))
				{
					// If subject header was processed with MB or Iconv functions, then the internal encoding
					// must be used to decode the subject, not the charset used by the email
					if (function_exists('mb_convert_encoding'))
					{
						$this->post_data['subject'] = mb_convert_encoding($this->post_data['subject'], strtoupper($this->EE->config->item('charset')), mb_internal_encoding());
					}
					elseif(function_exists('iconv'))
					{
						$this->post_data['subject'] = iconv(iconv_get_encoding('internal_encoding'), strtoupper($this->EE->config->item('charset')), $this->post_data['subject']);
					}
					elseif(strtolower($this->EE->config->item('charset')) == 'utf-8' && strtolower($this->charset) == 'iso-8859-1')
					{
						$this->post_data['subject'] = utf8_encode($this->post_data['subject']);
					}
					elseif(strtolower($this->EE->config->item('charset')) == 'iso-8859-1' && strtolower($this->charset) == 'utf-8')
					{
						$this->post_data['subject'] = utf8_decode($this->post_data['subject']);
					}
				}
			}

			/** --------------------------
			/**  IP Address of Sender
			/** --------------------------*/

			if (preg_match("/Received:\s*from\s*(.*)\[+(.*)\]+/", $email_data, $subject))
			{
				if (isset($subject['2']) && $this->EE->input->valid_ip(trim($subject['2'])))
				{
					$this->post_data['ip'] = trim($subject['2']);
				}
			}

			/** --------------------------
			/**  Check if AT&T email
			/** --------------------------*/

			if (preg_match("/From:\s*(.*)\s*\<(.*)\>/", $email_data, $from) OR preg_match("/From:\s*(.*)\s*/", $email_data, $from))
				{
				$this->sender_email = ( ! isset($from['2'])) ? $from['1'] : $from['2'];

				if (strpos(trim($this->sender_email),'mobile.att.net') !== FALSE)
				{
					$this->attach_as_txt = TRUE;
				}
			}

			/** -------------------------------------
			/**  Eliminate new line confusion
			/** -------------------------------------*/

			$email_data = $this->remove_newlines($email_data,$this->newline);

			/** -------------------------------------
			/**  Determine Boundary
			/** -------------------------------------*/

			if ( ! $this->find_boundary($email_data) OR $this->moblog_array['moblog_upload_directory'] == '0')
			{
				/** -------------------------
				/**  No files, just text
				/** -------------------------*/

				$duo = $this->newline.$this->newline;
				$this->body = $this->find_data($email_data, $duo,$duo.'.'.$this->newline);

				if ($this->body == '')
				{
					$this->body = $this->find_data($email_data, $duo,$this->newline.'.'.$this->newline);
				}

				// Check for Quoted-Printable and Base64 encoding
				if (stristr($email_data,'Content-Transfer-Encoding'))
				{
					$encoding = $this->find_data($email_data, "Content-Transfer-Encoding: ", $this->newline);

					if ( ! stristr(trim($encoding), "quoted-printable") AND ! stristr(trim($encoding), "base64"))
					{
						// try it without the space after the colon...
						$encoding = $this->find_data($email_data, "Content-Transfer-Encoding:", $this->newline);
					}

					if(stristr(trim($encoding),"quoted-printable"))
					{
						$this->body = str_replace($this->newline,"\n",$this->body);
						$this->body = quoted_printable_decode($this->body);
						$this->body = (substr($this->body,0,1) != '=') ? $this->body : substr($this->body,1);
						$this->body = (substr($this->body,-1) != '=') ? $this->body : substr($this->body,0,-1);
						$this->body = $this->remove_newlines($this->body,$this->newline);
					}
					elseif(stristr(trim($encoding),"base64"))
					{
						$this->body = str_replace($this->newline,"\n",$this->body);
						$this->body = base64_decode(trim($this->body));
						$this->body = $this->remove_newlines($this->body,$this->newline);
					}
				}

				if ($this->charset != $this->EE->config->item('charset'))
            	{
            		if (function_exists('mb_convert_encoding'))
            		{
            			$this->body = mb_convert_encoding($this->body, strtoupper($this->EE->config->item('charset')), strtoupper($this->charset));
            		}
            		elseif(function_exists('iconv') AND ($iconvstr = @iconv(strtoupper($this->charset), strtoupper($this->EE->config->item('charset')), $this->body)) !== FALSE)
            		{
            			$this->body = $iconvstr;
            		}
            		elseif(strtolower($this->EE->config->item('charset')) == 'utf-8' && strtolower($this->charset) == 'iso-8859-1')
            		{
            			$this->body = utf8_encode($this->body);
            		}
            		elseif(strtolower($this->EE->config->item('charset')) == 'iso-8859-1' && strtolower($this->charset) == 'utf-8')
            		{
            			$this->body = utf8_decode($this->body);
            		}
            	}
			}
			else
			{
				if ( ! $this->parse_email($email_data))
				{
					$this->message_array[] = 'unable_to_parse';
					return FALSE;
				}

				// Email message as .txt file?
				// Make the email body the attachment's contents
				// Unset attachment from files array.
				if ($this->attach_as_txt === TRUE && trim($this->body) == '' && $this->attach_text != '')
				{
					$this->body = $this->attach_text;
					$this->attach_text = '';

					foreach ($this->post_data['files'] as $key => $value)
					{
						if ($value == $this->attach_name)
						{
							unset($this->post_data['files'][$key]);
						}
					}
				}

			}

			/** ---------------------------
			/**  Authorization Check
			/** ---------------------------*/

			if ( ! $this->check_login())
			{
				if ($this->moblog_array['moblog_auth_required'] == 'y')
				{
					/** -----------------------------
					/**  Delete email?
					/** -----------------------------*/

					if ($this->moblog_array['moblog_auth_delete'] == 'y' && strncasecmp($this->pop_command("DELE {$email_id}"), '+OK', 3) != 0)
					{
						$this->message_array[] = 'undeletable_email'; //.$email_id;
						return FALSE;
					}

					/** -----------------------------
					/**  Delete any uploaded images
					/** -----------------------------*/

					if (count($this->email_files) > 0)
					{
						foreach ($this->email_files as $axe)
						{
							@unlink($this->upload_path.$axe);
						}
					}

					// Error...
					$this->message_array[] = 'authorization_failed';
					$this->message_array[] = $this->post_data['subject'];
					continue;
				}
			}

			/** -----------------------------
			/**  Format Flow Fix - Oh Joy!
			/** -----------------------------*/

			if ($format_flow == 'y')
			{
				$x = explode($this->newline,$this->body);
				$wrap_point = 10;

				if (count($x) > 1)
				{
					$this->body = '';

					// First, find wrap point
					for($p=0; $p < count($x); $p++)
					{
						$wrap_point = (strlen($x[$p]) > $wrap_point) ? strlen($x[$p]) : $wrap_point;
					}

					// Unwrap the Content
					for($p=0; $p < count($x); $p++)
					{
						$next = (isset($x[$p+1]) && count($y = explode(' ',$x[$p+1]))) ? $y['0'] : '';
						$this->body .= (strlen($x[$p]) < $wrap_point && strlen($x[$p].$next) <= $wrap_point) ? $x[$p].$this->newline : $x[$p];
					}
				}
			}

			$allow_overrides = ( ! isset($this->moblog_array['moblog_allow_overrides'])) ? 'y' : $this->moblog_array['moblog_allow_overrides'];

			/** -----------------------------
			/**  Image Archive set in email?
			/** -----------------------------*/

			if ($allow_overrides == 'y' &&
				(preg_match("/\{file_archive\}(.*)\{\/file_archive\}/", $this->body, $matches) OR
				 preg_match("/\<file_archive\>(.*)\<\/file_archive\>/", $this->body, $matches)))
			{
				$matches['1'] = trim($matches['1']);

				if ($matches['1'] == 'y' OR $matches['1'] == 'true' OR $matches['1'] == '1')
				{
					$this->moblog_array['moblog_file_archive'] = 'y';
				}
				else
				{
					$this->moblog_array['moblog_file_archive'] = 'n';
				}

				$this->body = str_replace($matches['0'],'',$this->body);
			}

			/** -----------------------------
			/**  Categories set in email?
			/** -----------------------------*/

			if ($allow_overrides == 'n' OR ( ! preg_match("/\{category\}(.*)\{\/category\}/", $this->body, $cats) &&
											 ! preg_match("/\<category\>(.*)\<\/category\>/", $this->body, $cats)))
			{
				$this->post_data['categories'] = trim($this->moblog_array['moblog_categories']);
			}
			else
			{
				$cats['1'] = str_replace(':','|',$cats['1']);
				$cats['1'] = str_replace(',','|',$cats['1']);
				$this->post_data['categories'] = $cats['1'];
				$this->body = str_replace($cats['0'],'',$this->body);
			}

			/** -----------------------------
			/**  Status set in email
			/** -----------------------------*/

			if ($allow_overrides == 'n' OR ( ! preg_match("/\{status\}(.*)\{\/status\}/", $this->body, $cats) &&
											 ! preg_match("/\<status\>(.*)\<\/status\>/", $this->body, $cats)))
			{
				$this->post_data['status'] = trim($this->moblog_array['moblog_status']);
			}
			else
			{
				$this->post_data['status'] = $cats['1'];
				$this->body = str_replace($cats['0'],'',$this->body);
			}

			/** -----------------------------
			/**  Sticky Set in Email
			/** -----------------------------*/

			if ($allow_overrides == 'n' OR ( ! preg_match("/\{sticky\}(.*)\{\/sticky\}/", $this->body, $mayo) &&
											 ! preg_match("/\<sticky\>(.*)\<\/sticky\>/", $this->body, $mayo)))
			{
				$this->post_data['sticky'] = ( ! isset($this->moblog_array['moblog_sticky_entry'])) ? $this->sticky : $this->moblog_array['moblog_sticky_entry'];
			}
			else
			{
				$this->post_data['sticky'] = (trim($mayo['1']) == 'yes' OR trim($mayo['1']) == 'y') ? 'y' : 'n';
				$this->body = str_replace($mayo['0'],'',$this->body);
			}



			/** -----------------------------
			/**  Default Field set in email?
			/** -----------------------------*/

			if ($allow_overrides == 'y' && (preg_match("/\{field\}(.*)\{\/field\}/", $this->body, $matches) OR
											preg_match("/\<field\>(.*)\<\/field\>/", $this->body, $matches)))
			{
				$this->EE->db->select('field_id');
				$this->EE->db->from('channel_fields, channels');
				$this->EE->db->where('channels.field_group', 'channel_fields.group_id');
				$this->EE->db->where('channels.channel_id', $this->moblog_array['moblog_channel_id']);
				$this->EE->db->where('channel_fields.group_id', $query->row('field_group'));
				$this->EE->db->where('(channel_fields.field_name = "'.$matches[1].'" OR '.$this->EE->db->dbprefix('channel_fields').'.field_label = "'.$matches[1].'")', NULL, FALSE);

				/* -------------------------------------
				/*  Hidden Configuration Variable
				/*  - moblog_allow_nontextareas => Removes the textarea only restriction
				/*	for custom fields in the moblog module (y/n)
				/* -------------------------------------*/
				if ($this->EE->config->item('moblog_allow_nontextareas') != 'y')
				{
					$this->EE->db->where('channel_fields.field_name', 'textarea');
				}
				
				$results = $this->EE->db->get();

				if ($results->num_rows() > 0)
				{
					$this->moblog_array['moblog_field_id'] = trim($results->row('field_id') );
				}

				$this->body = str_replace($matches['0'],'',$this->body);
			}


			/** -----------------------------
			/**  Set Entry Title in Email
			/** -----------------------------*/

			if (preg_match("/\{entry_title\}(.*)\{\/entry_title\}/", $this->body, $matches) OR preg_match("/\<entry_title\>(.*)\<\/entry_title\>/", $this->body, $matches))
			{
				if (strlen($matches['1']) > 1)
				{
					$this->post_data['subject'] = trim(str_replace($this->newline,"\n",$matches['1']));
				}

				$this->body = str_replace($matches['0'],'',$this->body);
			}

			/** ----------------------------
			/**  Post Entry
			/** ----------------------------*/

			if ($this->moblog_array['moblog_channel_id'] != '0' && $this->moblog_array['moblog_file_archive'] == 'n')
			{
				$this->template = $this->moblog_array['moblog_template'];

				$tag = 'field';

				if($this->moblog_array['moblog_field_id'] != 'none' OR
					preg_match("/".LD.'field:'."(.*?)".RD."(.*?)".LD.'\/'.'field:'."(.*?)".RD."/s", $this->template, $matches) OR
					preg_match("/[\<\{]field\:(.*?)[\}\>](.*?)[\<\{]\/field\:(.*?)[\}\>]/", $this->body, $matches)
					)
				{
					$this->post_entry();
				}
				else
				{
					$this->emails_done++;
					continue;
				}
			}


			/** -------------------------
			/**  Delete Email
			/** -------------------------*/

			if (strncasecmp($this->pop_command("DELE {$email_id}"), '+OK', 3) != 0)
			{
				$this->message_array[] = 'undeletable_email'; //.$email_id;
				return FALSE;
			}


			/** -------------------------
			/**  Send Pings
			/** -------------------------*/

			if (isset($this->moblog_array['moblog_ping_servers']) && $this->moblog_array['moblog_ping_servers'] != '')
			{
				if($pings_sent = $this->send_pings($this->moblog_array['channel_title'], $this->moblog_array['channel_url'], $this->moblog_array['rss_url']))
				{
					$this->pings_sent = $this->pings_sent + count($pings_sent);
				}
			}


			$this->emails_done++;
		}

		/** -----------------------------
		/**  Close Email Connection
		/** -----------------------------*/

		$line = $this->pop_command("QUIT");

		@fclose($this->fp);

		/** ---------------------------------
		/**  Clear caches if needed
		/** ---------------------------------*/

		if ($this->emails_done > 0)
		{
			if ($this->EE->config->item('new_posts_clear_caches') == 'y')
			{
				$this->EE->functions->clear_caching('all');
			}
			else
			{
				$this->EE->functions->clear_caching('sql_cache');
			}
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Post Entry 
	 */
	function post_entry()
	{
		// Default Channel Data

		$channel_id = $this->moblog_array['moblog_channel_id'];
		
		$this->EE->db->select('site_id, channel_title, channel_url, rss_url, ping_return_url, comment_url, deft_comments, cat_group, field_group, channel_notify, channel_notify_emails');
		$query = $this->EE->db->get_where('channels', array('channel_id' => $channel_id));

		if ($query->num_rows() == 0)
		{
			$this->message_array[] = 'invalid_channel'; // How the hell did this happen?
			return FALSE;
		}

		$site_id = $query->row('site_id');
		$notify_address = ($query->row('channel_notify')  == 'y' AND $query->row('channel_notify_emails')  != '') ? $query->row('channel_notify_emails')  : '';


		// Collect the meta data
		
		$this->post_data['subject'] = strip_tags($this->post_data['subject']);
		
		$this->moblog_array['moblog_author_id'] = ($this->moblog_array['moblog_author_id'] == 'none') ? '1' : $this->moblog_array['moblog_author_id'];
		$author_id = ($this->author != '') ? $this->author : $this->moblog_array['moblog_author_id'];

		if ( ! is_numeric($author_id) OR $author_id == '0')
		{
			$author_id = '1';
		}

		// Load the text helper
		$this->EE->load->helper('text');
		$entry_date = ($this->EE->localize->now + $this->entries_added - $this->time_offset);

		$data = array(
						'channel_id'		=> $channel_id,
						'site_id'			=> $site_id,
						'author_id'			=> $author_id,
						'title'				=> ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($this->post_data['subject']) : $this->post_data['subject'],
						'ip_address'		=> $this->post_data['ip'],
						'entry_date'		=> $entry_date,
						'edit_date'			=> gmdate("YmdHis", $entry_date),						
						'year'				=> gmdate('Y', $entry_date),
						'month'				=> gmdate('m', $entry_date),
						'day'				=> gmdate('d', $entry_date),
						'sticky'			=> (isset($this->post_data['sticky'])) ? $this->post_data['sticky'] : $this->sticky,
						'status'			=> ($this->post_data['status'] == 'none') ? 'open' : $this->post_data['status'],
						'allow_comments'	=> $query->row('deft_comments'),
						'ping_servers'		=> FALSE   // Pings are already sent above.  Should probably be hooked into API CHannel Entries as well.
					 );

		// Remove ignore text

		$this->body = preg_replace("#<img\s+src=\s*[\"']cid:(.*?)\>#si", '', $this->body);  // embedded images

		$this->moblog_array['moblog_ignore_text'] = $this->remove_newlines($this->moblog_array['moblog_ignore_text'],$this->newline);

		// One biggo chunk
		if ($this->moblog_array['moblog_ignore_text'] != '' && stristr($this->body,$this->moblog_array['moblog_ignore_text']) !== FALSE)
		{
			$this->body = str_replace($this->moblog_array['moblog_ignore_text'], '',$this->body);
		}
		elseif($this->moblog_array['moblog_ignore_text'] != '')
		{
			// By line
			$delete_text	= $this->remove_newlines($this->moblog_array['moblog_ignore_text'],$this->newline);
			$delete_array	= explode($this->newline,$delete_text);

			if (count($delete_array) > 0)
			{
				foreach($delete_array as $ignore)
				{
					if (trim($ignore) != '')
					{
						$this->body = str_replace(trim($ignore), '',$this->body);
					}
				}
			}
		}


		/** -------------------------------------
		/**  Specified Fields for Email Text
		/** -------------------------------------*/

		if (preg_match_all("/[\<\{]field\:(.*?)[\}\>](.*?)[\<\{]\/field\:(.*?)[\}\>]/", $this->body, $matches))
		{
			$this->EE->db->select('channel_fields.field_id, channel_fields.field_name, channel_fields.field_label, channel_fields.field_fmt');
			$this->EE->db->from('channels, channel_fields');
			$this->EE->db->where('channels.field_group = '.$this->EE->db->dbprefix('channel_fields').'.group_id', NULL, FALSE);
			$this->EE->db->where('channels.channel_id', $this->moblog_array['moblog_channel_id']);

			/* -------------------------------------
			/*  Hidden Configuration Variable
			/*  - moblog_allow_nontextareas => Removes the textarea only restriction
			/*	for custom fields in the moblog module (y/n)
			/* -------------------------------------*/
			if ($this->EE->config->item('moblog_allow_nontextareas') != 'y')
			{
				$this->EE->db->where('channel_fields.field_name', 'textarea');
			}

			$results = $this->EE->db->get();

			if ($results->num_rows() > 0)
			{
				$field_name  = array();
				$field_label = array();
				$field_format = array();

				foreach($results->result_array() as $row)
				{
					$field_name[$row['field_id']]	= $row['field_name'];
					$field_label[$row['field_id']]	= $row['field_label'];
					$field_format[$row['field_id']] = $row['field_fmt'];
				}

				unset($results);

				for($i=0; $i < count($matches[0]); $i++)
				{
					$x = preg_split("/[\s]+/", $matches['1'][$i]);

					if ($key = array_search($x['0'],$field_name) OR $key = array_search($x['0'],$field_label))
					{
						$format = ( ! isset($x['1']) OR ! stristr($x['1'],"format")) ? $field_format[$key] : preg_replace("/format\=[\"\'](.*?)[\'\"]/","$1",trim($x['1']));

						$matches['2'][$i] = str_replace($this->newline, "\n",$matches['2'][$i]);

						if ( ! isset($this->entry_data[$key]))
						{
							$this->entry_data[$key] = array('data' => $matches['2'][$i],
															'format' => $format);
						}
						else
						{
							$this->entry_data[$key] = array('data' => $matches['2'][$i].$this->entry_data[$key]['data'],
															'format' => $format);
						}

						$this->body = str_replace($matches['0'][$i], '', $this->body);
					}
				}
			}
		}


		// Return New Lines
		
		$this->body = str_replace($this->newline, "\n",$this->body);


		// Parse template

		$tag = 'field';

		if( ! preg_match_all("/".LD.$tag."(.*?)".RD."(.*?)".LD.'\/'.$tag.RD."/s", $this->template, $matches))
		{
			$this->parse_field($this->moblog_array['moblog_field_id'],$this->template, $query->row('field_group') );
		}
		else
		{
			for($i=0; $i < count($matches['0']) ; $i++)
			{
				$params = $this->assign_parameters($matches['1'][$i]);

				$params['format']	= ( ! isset($params['format'])) ? '' : $params['format'];
				$params['name'] 	= ( ! isset($params['name'])) 	? '' : $params['name'];

				$this->parse_field($params,$matches['2'][$i], $query->row('field_group') ); 
				$this->template = str_replace($matches['0'],'',$this->template);
			}

			if (trim($this->template) != '')
			{
				$this->parse_field($this->moblog_array['moblog_field_id'],$this->template, $query->row('field_group') );
			}
		}


		// Prep entry data

		if (count($this->entry_data) > 0)
		{
			foreach($this->entry_data as $key => $value)
			{
				// ----------------------------------------
				//  Put this in here in case some one has
				//  {field:body}{/field:body} in their email
				//  and yet has their default field set to none
				// ----------------------------------------

				if ($key == 'none')
				{
					continue;
				}

				// Load the text helper
				$this->EE->load->helper('text');

				$combined_data = $value['data'];
				$combined_data = ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities(trim($combined_data)) : trim($combined_data);

				$data['field_id_'.$key] = $combined_data;
				$data['field_ft_'.$key] = $value['format'];
			}
		}


		$data['category'] = array();

		if ($this->post_data['categories'] == 'all')
		{
			$cat_groups = explode('|', $query->row('cat_group'));
			$this->EE->load->model('category_model');

			foreach($cat_groups as $cat_group_id)
			{
				$cats_q = $this->EE->category_model->get_channel_categories($cat_group_id);

				if ($cats_q->num_rows() > 0)
				{
					foreach($cats_q->result() as $row)
					{
						$data['category'][] = $row->cat_id;
					}
				}
			}
			
			$data['category'] = array_unique($data['category']);
		}
		elseif ($this->post_data['categories'] != 'none')
		{
			$data['category'] = explode('|', $this->post_data['categories']);
			$data['category'] = array_unique($data['category']);
		}

		// forgive me, please.
		$orig_group_id = $this->EE->session->userdata('group_id');
		$orig_can_assign = $this->EE->session->userdata('can_assign_post_authors');
		$orig_can_edit = $this->EE->session->userdata('can_edit_other_entries');
		$this->EE->session->userdata['group_id'] = 1;
		$this->EE->session->userdata['can_assign_post_authors'] = 'y';
		$this->EE->session->userdata['can_edit_other_entries'] = 'y';

		// Insert the Entry
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_entries');

		$result = $this->EE->api_channel_entries->submit_new_entry($data['channel_id'], $data);

		if ( ! $result)
		{
			// echo '<pre>';print_r($this->EE->api_channel_entries->errors);echo'</pre>';
		}
		else
		{
			$this->entries_added++;
		}
		
		$this->EE->session->userdata['can_assign_post_authors'] = $orig_can_assign;
		$this->EE->session->userdata['group_id'] = $orig_group_id;
		$this->EE->session->userdata['can_edit_other_entries'] = $orig_can_edit;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Send Pings
	 *
	 * 	@param string	title
	 * 	@param string	url
	 * 
	 */

	function send_pings($title, $url)
	{
		$ping_servers = explode('|', $this->moblog_array['moblog_ping_servers']);

		$sql = "SELECT server_name, server_url, port FROM exp_ping_servers WHERE id IN (";

		foreach ($ping_servers as $id)
		{
			$sql .= "'$id',";
		}

		$sql = substr($sql, 0, -1).') ';

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$this->EE->load->library('xmlrpc');
		
		$result = array();

		foreach ($query->result_array() as $row)
		{
			if ($this->EE->xmlrpc->weblogs_com_ping($row['server_url'], $row['port'], $title, $url))
			{
				$result[] = $row['server_name'];
			}
		}

		return $result;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Assign Params
	 *
	 *	Creates an associative array from a string
	 *	of parameters: sort="asc" limit="2" etc.
	 *
	 * 	Return parameters as an array - Use TMPL one eventually
	 *
	 *	@param string
	 */
	function assign_parameters($str)
	{
		if ($str == "")
		{
			return FALSE;
		}

		// \047 - Single quote octal
		// \042 - Double quote octal

		// I don't know for sure, but I suspect using octals is more reliable than ASCII.
		// I ran into a situation where a quote wasn't being matched until I switched to octal.
		// I have no idea why, so just to be safe I used them here. - Rick

		if (preg_match_all("/(\S+?)\s*=[\042\047](\s*.+?\s*)[\042\047]\s*/", $str, $matches))
		{
			$result = array();

			for ($i = 0; $i < count($matches['1']); $i++)
			{
				$result[$matches['1'][$i]] = $matches['2'][$i];
			}

			return $result;
		}
 
		return FALSE;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	parse_field
	 *
	 *	@param mixed - params
	 * 	@param 
	 *	@param string
	 */
	function parse_field($params, $field_data, $field_group)
	{
		$field_id = '1';
		$format = 'none';

		/** -----------------------------
		/**  Determine Field Id and Format
		/** -----------------------------*/

		if ( ! is_array($params))
		{
			$field_id = $params;

			$this->EE->db->select('field_fmt');
			$this->EE->db->where('field_id', $field_id);
			$results = $this->EE->db->get('channel_fields');

			$format = ($results->num_rows() > 0) ? $results->row('field_fmt')  : 'none';
		}
		else
		{
			if ($params['name'] != '' && $params['format'] == '')
			{
				$xsql = ($this->EE->config->item('moblog_allow_nontextareas') == 'y') ? "" : " AND exp_channel_fields.field_type = 'textarea' ";

				$this->EE->db->select('field_id, field_fmt');
				$this->EE->db->where('group_id', $field_id);
				$this->EE->db->where('(field_name = "'.$params['name'].'" OR field_label = "'.$params['name'].'")', NULL, FALSE);
				
				if ($this->EE->config->item('moblog_allow_nontextareas') != 'y')
				{
					$this->EE->db->where('field_type', 'textarea');
				}
				
				$results = $this->EE->db->get('channel_fields');
									 
				$field_id	= ($results->num_rows() > 0) ? $results->row('field_id')  : $this->moblog_array['moblog_field_id'];
				$format 	= ($results->num_rows() > 0) ? $results->row('field_fmt')  : 'none';
			}
			elseif($params['name'] == '' && $params['format'] == '')
			{
				$field_id = $this->moblog_array['moblog_field_id'];
				
				$this->EE->db->seledct('field_fmt');
				$this->EE->db->where('field_id', $field_id);
				
				$results = $this->EE->db->get('channel_fields');
													 
				$format	= $results->row('field_fmt') ;
			}
			elseif($params['name'] == '' && $params['format'] != '')
			{
				$field_id	= $this->moblog_array['moblog_field_id'];
				$format		= $params['format'];
			}
			elseif($params['name'] != '' && $params['format'] != '')
			{
				$xsql = ($this->EE->config->item('moblog_allow_nontextareas') == 'y') ? "" : " AND exp_channel_fields.field_type = 'textarea' ";

				$this->EE->db->select('field_id');
				$this->EE->db->where('group_id', $field_group);
				$this->EE->db->where('(field_name = "'.$params['name'].'" OR field_label = "'.$params['name'].'")');
				
				if ($this->EE->config->item('moblog_allow_nontextareas') != 'y')
				{
					$this->EE->db->where('field_type', 'textarea');
				}
				
				$results = $this->EE->db->get('channel_fields');
										 
				$field_id	= ($results->num_rows() > 0) ? $results->row('field_id')  : $this->moblog_array['moblog_field_id'];
				$format		= $params['format'];
			}
		}

		/** -----------------------------
		/**  Parse Content
		/** -----------------------------*/

		$pair_array = array('images','audio','movie','files'); 
		$float_data = $this->post_data;
		$params = array();

		foreach ($pair_array as $type)
		{
			if ( ! preg_match_all("/".LD.$type."(.*?)".RD."(.*?)".LD.'\/'.$type.RD."/s", $field_data, $matches))
			{
				continue;
			}

			if(count($matches['0']) == 0)
			{
				continue;
			}

			for ($i=0; $i < count($matches['0']) ; $i++)
			{
				$template_data = '';

				if ($type != 'files' && ( ! isset($float_data[$type]) OR count($float_data[$type]) == 0))
				{
					$field_data = str_replace($matches['0'][$i],'',$field_data);
					continue;
				}

				// Assign parameters, if any
				if(isset($matches['1'][$i]) && trim($matches['1'][$i]) != '')
				{
					$params = $this->assign_parameters(trim($matches['1'][$i]));
				}

				$params['match'] = ( ! isset($params['match'])) ? '' : $params['match'];

				/** ----------------------------
				/**  Parse Pairs
				/** ----------------------------*/

				// Files is a bit special.  It goes last and will clear out remaining files.  Has match parameter
				if ($type == 'files' && $params['match'] != '')
				{
					if (count($float_data) > 0)
					{
						foreach ($float_data as $ftype => $value)
						{
							if (in_array($ftype,$pair_array) && ($params['match'] == 'all' OR stristr($params['match'],$ftype)))
							{ 
								foreach($float_data[$ftype] as $k => $file)
								{
									if ( ! is_array($file))
									{
										$template_data .= str_replace('{file}',$this->upload_dir_code.$file,$matches['2'][$i]);
									}
									elseif(is_array($file) && $ftype == 'images')
									{
										$temp_data = '';
										$details = array();
										$filename					= ( ! isset($file['filename'])) ? '' : $this->upload_dir_code.$file['filename'];
										$details['width']			= ( ! isset($file['width'])) ? '' : $file['width'];
										$details['height']			= ( ! isset($file['height'])) ? '' : $file['height'];
										$details['thumbnail']		= ( ! isset($file['thumbnail'])) ? '' : $this->upload_dir_code.$file['thumbnail'];
										$details['thumb_width']		= ( ! isset($file['thumb_width'])) ? '' : $file['thumb_width'];
										$details['thumb_height']	= ( ! isset($file['thumb_height'])) ? '' : $file['thumb_height'];

										$temp_data = str_replace('{file}',$filename,$matches['2'][$i]);

										foreach($details as $d => $dv)
										{
											$temp_data = str_replace('{'.$d.'}',$dv,$temp_data);
										}

										$template_data .= $temp_data;
									}

									//unset($float_data[$ftype][$k]);
								}
							}
						}
					}
				}
				elseif(isset($float_data[$type]))
				{
					foreach($float_data[$type] as $k => $file)
					{
						if ( ! is_array($file))
						{
							$template_data .= str_replace('{file}',$this->upload_dir_code.$file,$matches['2'][$i]);
						}
						elseif(is_array($file) && $type == 'images')
						{
							$temp_data = '';
							$details = array();
							$filename					= ( ! isset($file['filename'])) ? '' : $this->upload_dir_code.$file['filename'];
							$details['width']			= ( ! isset($file['width'])) ? '' : $file['width'];
							$details['height']			= ( ! isset($file['height'])) ? '' : $file['height'];
							$details['thumbnail']		= ( ! isset($file['thumbnail'])) ? '' : $this->upload_dir_code.$file['thumbnail'];
							$details['thumb_width']		= ( ! isset($file['thumb_width'])) ? '' : $file['thumb_width'];
							$details['thumb_height']	= ( ! isset($file['thumb_height'])) ? '' : $file['thumb_height'];

							$temp_data = str_replace('{file}',$filename,$matches['2'][$i]);

							foreach($details as $d => $dv)
							{
								$temp_data = str_replace('{'.$d.'}',$dv,$temp_data);
							}

							$template_data .= $temp_data;
						}

						//unset($float_data[$type][$k]);
					}  
				}

				// Replace tag pair with template data
				$field_data = str_replace($matches['0'][$i],$template_data,$field_data);

				// Unset member of float data array
				if (isset($float_data[$type]) && count($float_data[$type]) == 0)
				{
					unset($float_data[$type]);
				}
			}
		}

		/** ------------------------------
		/**  Variable Single:  text
		/** ------------------------------*/

		$field_data = str_replace(array('{text}', '{sender_email}'), array($this->body, $this->sender_email), $field_data);

		$this->entry_data[$field_id]['data'] 	= ( ! isset($this->entry_data[$field_id])) ? $field_data : $this->entry_data[$field_id]['data']."\n".$field_data;
		$this->entry_data[$field_id]['format'] 	= $format;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Parse Email
	 *
	 *	@param mixed - Email Data
	 * 	@param 
	 */
	function parse_email($email_data,$type='norm')
	{
		$boundary = ($type != 'norm') ? $this->multi_boundary : $this->boundary;
		$email_data = str_replace('boundary='.substr($boundary,2),'BOUNDARY_HERE',$email_data);

		$email_parts = explode($boundary, $email_data);

		if (count($email_parts) < 2)
		{
			$boundary = str_replace("+","\+", $boundary);
			$email_parts = explode($boundary, $email_data);
		}

		if (count($email_parts) < 2)
		{
			return FALSE;
			unset($email_parts);
			unset($email_data);
		}

		/** ---------------------------
		/**  Determine Upload Path
		/** ---------------------------*/

		$query = $this->EE->db->query("SELECT server_path FROM exp_upload_prefs
							  WHERE id = '".$this->EE->db->escape_str($this->moblog_array['moblog_upload_directory'])."'");
							 
		if ($query->num_rows() == 0)
		{
			$this->message_array[] = 'invalid_upload_directory';
			return FALSE;
		}

		$this->upload_path = $query->row('server_path');

		if ( ! is_really_writable($this->upload_path))
		{
			$system_absolute = str_replace('/modules/moblog/mod.moblog.php','',__FILE__);
			$addon = (substr($this->upload_path,0,2) == './') ? substr($this->upload_path,2) : $this->upload_path;

			while(substr($addon,0,3) == '../')
			{
				$addon = substr($addon,3);

				$p1 = (strrpos($system_absolute,'/') !== FALSE) ? strrpos($system_absolute,'/') : strlen($system_absolute);
				$system_absolute = substr($system_absolute,0,$p1);
			}

			if (substr($system_absolute,-1) != '/')
			{
				$system_absolute .= '/';
			}

			$this->upload_path = $system_absolute.$addon;

			if ( ! is_really_writable($this->upload_path))
			{
				$this->message_array[] = 'upload_directory_unwriteable';
				return FALSE;
			}
		}

		if (substr($this->upload_path, -1) != '/')
		{
			$this->upload_path .= '/';
		}

		$this->upload_dir_code = '{filedir_'.$this->moblog_array['moblog_upload_directory'].'}';

		/** ---------------------------
		/**  Find Attachments
		/** ---------------------------*/

		foreach($email_parts as $key => $value)
		{
			// Skip headers and those with no content-type
			if ($key == '0' OR stristr($value, 'Content-Type:') === FALSE)
			{
				continue;
			}

			$contents		= $this->find_data($value, "Content-Type:", $this->newline);
			$x				= explode(';',$contents);
			$content_type	= $x['0'];

			$content_type	= strtolower($content_type);
			$pieces			= explode('/',trim($content_type));
			$type			= trim($pieces['0']);
			$subtype		= ( ! isset($pieces['1'])) ? '0' : trim($pieces['1']);

			$charset		= 'auto';

			/** --------------------------
			/**  Outlook Exception
			/** --------------------------*/
			if ($type == 'multipart' && $subtype != 'appledouble')
			{
				if( ! stristr($value,'boundary='))
				{
					continue;
				}

				$this->multi_boundary = "--".$this->find_data($value, "boundary=", $this->newline);
				$this->multi_boundary = trim(str_replace('"','',$this->multi_boundary));

				if (strlen($this->multi_boundary) == 0)
				{
					continue;
				}

				$this->parse_email($value,'multi');
				$this->multi_boundary = '';
				continue;
			}


			/** --------------------------
			/**  Quick Grab of Headers
			/** --------------------------*/
			$headers = $this->find_data($value, '', $this->newline.$this->newline);

			/** ---------------------------
			/**  Text : plain, html, rtf
			/** ---------------------------*/
			if ($type == 'text' && $headers != '' &&
				(($this->txt_override === TRUE && $subtype == 'plain') OR ! stristr($headers,'name=')))
			{
				$duo	=  $this->newline.$this->newline;
				$text  = $this->find_data($value, $duo,'');

				if ($text == '')
				{
					$text = $this->find_data($value, $this->newline,'');
				}

				/** ------------------------------------
				/**  Charset Available?
				/** ------------------------------------*/

				if (preg_match("/charset=(.*?)(\s|".$this->newline.")/is", $headers, $match))
				{
					$charset = trim(str_replace(array("'", '"', ';'), '', $match['1']));
				}

				/** ------------------------------------
				/**  Check for Encoding of Text
				/** ------------------------------------*/
				if (stristr($value,'Content-Transfer-Encoding'))
				{
					$encoding = $this->find_data($value, "Content-Transfer-Encoding:", $this->newline);

					/** ------------------------------------
					/**  Check for Quoted-Printable encoding
					/** ------------------------------------*/

					if(stristr($encoding,"quoted-printable"))
					{
						$text = str_replace($this->newline,"\n",$text);
						$text = quoted_printable_decode($text);
						$text = (substr($text,0,1) != '=') ? $text : substr($text,1);
						$text = (substr($text,-1) != '=') ? $text : substr($text,0,-1);
						$text = $this->remove_newlines($text,$this->newline);
					}

					/** ------------------------------------
					/**  Check for Base 64 encoding:  MIME
					/** ------------------------------------*/

					elseif(stristr($encoding,"base64"))
					{
						$text = str_replace($this->newline,"\n", $text);
						$text = base64_decode(trim($text));
						$text = $this->remove_newlines($text,$this->newline);
					}

				}

				/** ----------------------------------
				/**  Spring PCS - Picture Share
				/** ----------------------------------*/

				// http://pictures.sprintpcs.com//shareImage/13413001858_235.jpg
				// http://pictures.sprintpcs.com/mi/8516539_30809087_0.jpeg?inviteToken=sETr4TJ9m85YizVzoka0

				if (trim($text) != '' && strpos($text, 'pictures.sprintpcs.com') !== FALSE)
				{
					// Find Message
					$sprint_msg = $this->find_data($value, '<b>Message:</b>', '</font>');

					// Find Image
					if ($this->sprint_image($text) && $sprint_msg != '')
					{
						$text = $sprint_msg;
					}
					else
					{
						continue;
					}
				}

				/** ----------------------------------
				/**  Bell Canada - Episode Two, Attack of the Sprint Clones
				/** ----------------------------------*/

				// http://mypictures.bell.ca//i/99376001_240.jpg?invite=SELr4RJHhma1cknzLQoU

				if (trim($text) != '' && strpos($text, 'mypictures.bell.ca') !== FALSE)
				{
					// Find Message
					$bell_msg = $this->find_data($value, 'Vous avez re&ccedil;u une photo de <b>5147103855', '<img');
					$bell_msg = $this->find_data($bell_msg, '<p>', '</p>');

					// Find Image
					if ($this->bell_image($text) && $bell_msg != '')
					{
						$text = trim($bell_msg);
					}
					else
					{
						continue;
					}
				}


				/** ----------------------------------
				/**  T-Mobile - In cyberspace, no one can hear you cream.
				/** ----------------------------------*/

				if (trim($text) != '' && stristr($text, 'This message was sent from a T-Mobile wireless phone') !== FALSE)
				{
					$text = '';
				}

				if ($this->charset != $this->EE->config->item('charset'))
            	{
            		if (function_exists('mb_convert_encoding'))
            		{
            			$text = mb_convert_encoding($text, strtoupper($this->EE->config->item('charset')), strtoupper($this->charset));
            		}
            		elseif(function_exists('iconv') AND ($iconvstr = @iconv(strtoupper($this->charset), strtoupper($this->EE->config->item('charset')), $text)) !== FALSE)
            		{
            			$text = $iconvstr;
            		}
            		elseif(strtolower($this->EE->config->item('charset')) == 'utf-8' && strtolower($this->charset) == 'iso-8859-1')
            		{
            			$text = utf8_encode($text);
            		}
            		elseif(strtolower($this->EE->config->item('charset')) == 'iso-8859-1' && strtolower($this->charset) == 'utf-8')
            		{
            			$text = utf8_decode($text);
            		}
            	}

				// RTF and HTML are considered alternative text
				$subtype = ($subtype != 'html' && $subtype != 'rtf') ? 'plain' : 'alt';

				// Same content type, then join together
				$this->post_data[$type][$subtype] = (isset($this->post_data[$type][$subtype])) ? $this->post_data[$type][$subtype]." $text" : $text;

				// Plain text takes priority for body data.
				$this->body = ( ! isset($this->post_data[$type]['plain'])) ? $this->post_data[$type]['alt'] : $this->post_data[$type]['plain'];

			}
			elseif($type == 'image' OR $type == 'application' OR $type == 'audio' OR $type == 'video' OR $subtype == 'appledouble' OR $type == 'text') // image or application
			{
				if ($subtype == 'appledouble')
				{
					if ( ! $data = $this->appledouble($value))
					{
						continue;
					}
					else
					{
						$value 		= $data['value'];
						$subtype 	= $data['subtype'];
						$type		= $data['type'];
						unset($data);
					}
				}

				/** ------------------------------
				/**  Determine Filename
				/** ------------------------------*/
				$contents = $this->find_data($value, "name=", $this->newline);

				if ($contents == '')
				{
					$contents = $this->find_data($value, 'Content-Location:', $this->newline);
				}

				if ($contents == '')
				{
					$contents = $this->find_data($value, 'Content-ID:', $this->newline);
					$contents = str_replace('<','', $contents);
					$contents = str_replace('<','', $contents);
				}

				$x = explode(';',trim($contents));
				$filename = ($x['0'] == '') ? 'moblogfile' : $x['0'];

				$filename = trim(str_replace('"','',$filename));
				$filename = str_replace($this->newline,'',$filename);
				$filename = $this->safe_filename($filename);

				if (stristr($filename, 'dottedline') OR stristr($filename, 'spacer.gif') OR stristr($filename, 'masthead.jpg'))
				{
					continue;
				}


				/** ------------------------------
				/**  Check and adjust for multiple files with same file name
				/** ------------------------------*/

				$filename = $this->unique_filename($filename, $subtype);

				/** --------------------------------
				/**  File/Image Code and Cleanup
				/** --------------------------------*/

				$duo = $this->newline.$this->newline;
				$file_code = $this->find_data($value, $duo,'');

				if ($file_code == '')
				{
					$file_code = $this->find_data($value, $this->newline,'');

					if ($file_code == '')
					{
						$this->message_array = 'invalid_file_data';
						return FALSE;
					}
				}

				/** --------------------------------
				/**  Determine Encoding
				/** --------------------------------*/

				$contents = $this->find_data($value, "Content-Transfer-Encoding:", $this->newline);
				$x = explode(';',$contents);
				$encoding = $x['0'];
				$encoding = trim(str_replace('"','',$encoding));
				$encoding = str_replace($this->newline,'',$encoding);

				if ( ! stristr($encoding,"base64") &&  ! stristr($encoding,"7bit") &&  ! stristr($encoding,"8bit") && ! stristr($encoding,"quoted-printable"))
				{
					if ($type == 'text')
					{
						// RTF and HTML are considered alternative text
						$subtype = ($subtype != 'html' && $subtype != 'rtf') ? 'plain' : 'alt';

						// Same content type, then join together
						$this->post_data[$type][$subtype] = (isset($this->post_data[$type][$subtype])) ? $this->post_data[$type][$subtype].' '.$file_code : $file_code;

						// Plain text takes priority for body data.
						$this->body = ( ! isset($this->post_data[$type]['plain'])) ? $this->post_data[$type]['alt'] : $this->post_data[$type]['plain'];
					}

					continue;
				}

				// Eudora and Mail.app use this by default
				if(stristr($encoding,"quoted-printable"))
				{
					$file_code = quoted_printable_decode($file_code);
				}

				// Base64 gets no space and no line breaks
				$replace = ( ! stristr($encoding,"base64")) ? "\n" : '';
				$file_code = trim(str_replace($this->newline,$replace,$file_code));

				// PHP function sometimes misses opening and closing equal signs
				if(stristr($encoding,"quoted-printable"))
				{
					$file_code = (substr($file_code,0,1) != '=') ? $file_code : substr($file_code,1);
					$file_code = (substr($file_code,-1) != '=') ? $file_code : substr($file_code,0,-1);
				}

				// Clean out 7bit and 8bit files.
				if ( ! stristr($encoding,"base64"))
				{
					$file_code = $this->EE->security->xss_clean($file_code);
				}

				/** ------------------------------
				/**  Check and adjust for multiple files with same file name
				/** ------------------------------*/

				$filename = $this->unique_filename($filename, $subtype);

				/** ---------------------------
				/**  Put Info in Post Data array
				/** ---------------------------*/

				if (in_array(substr($filename,-3),$this->movie) OR in_array(substr($filename,-5),$this->movie)) // Movies
				{
					$this->post_data['movie'][] = $filename;
				}
				elseif (in_array(substr($filename,-3),$this->audio) OR in_array(substr($filename,-4),$this->audio) OR in_array(substr($filename,-2),$this->audio)) // Audio
				{
					$this->post_data['audio'][] = $filename;
				}
				elseif (in_array(substr($filename,-3),$this->image) OR in_array(substr($filename,-4),$this->image)) // Images
				{
					$this->post_data['images'][] = array('filename' => $filename);

					$key = count($this->post_data['images']) - 1;

					$type = 'image'; // For those crazy application/octet-stream images
				}
				elseif (in_array(substr($filename,-2),$this->files) OR in_array(substr($filename,-3),$this->files) OR in_array(substr($filename,-4),$this->files)) // Files
				{
					$this->post_data['files'][] = $filename;
				}
				else
				{
					// $this->post_data['files'][] = $filename;
					continue;
				}


				// AT&T phones send the message as a .txt file
				// This checks to see if this email is from an AT&T phone,
				// not an encoded file, and has a .txt file extension in the filename

				if ($this->attach_as_txt === TRUE && ! stristr($encoding,"base64"))
				{
					if(stristr($filename,'.txt') && preg_match("/Content-Disposition:\s*inline/i",$headers,$found))
					{
						$this->attach_text = $file_code;
						$this->attach_name = $filename;
						continue; // No upload of file.
					}
				}

				/** ------------------------------
				/**  Write File to Upload Directory
				/** ------------------------------*/

				if ( ! $fp = @fopen($this->upload_path.$filename,FOPEN_WRITE_CREATE_DESTRUCTIVE))
				{
					$this->message_array[] = 'error_writing_attachment'; //.$this->upload_path.$filename;
					return FALSE;
				}

				$attachment = ( ! stristr($encoding,"base64")) ? $file_code : base64_decode($file_code);
				fwrite($fp,$attachment);
				fclose($fp);

				@chmod($this->upload_path.$filename, FILE_WRITE_MODE);

				unset($attachment);
				unset($file_code);

				$this->email_files[] = $filename;
				$this->uploads++;


				// Only images beyond this point.
				if ($type != 'image')
				{
					continue;
				}
				
				$this->image_resize($filename, $key);

			} // End files/images section

		} // End foreach

		return TRUE;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Remove Images from Sprint.
	 *
	 * eg:  <img src="http://pictures.sprintpcs.com//shareImage/13413001858_235.jpg?border=1,255,255,255,1,0,0,0&amp;invite=OEKJJD5XYYhMZ5hY8amx" border="0" />
	 *
	 *	@param string
	 */
	function sprint_image($text)
	{
		if (preg_match_all("|(http://pictures.sprintpcs.com(.*?))\?inviteToken\=(.*?)&|i", str_replace($this->newline,"\n",$text), $matches))
		{
			for($i = 0; $i < count($matches['0']); $i++)
			{
				/*
				if (stristr($matches['1'][$i], 'jpeg') === FALSE && stristr($matches['1'][$i], 'jpg') === FALSE)
				{
					continue;
				}
				*/

				/** ------------------------------
				/**  Filename Creation
				/** ------------------------------*/

				$x = explode('/', $matches['1'][$i]);

				$filename = array_pop($x);

				if (strlen($filename) < 4)
				{
					$filename .= array_pop($x);
				}

				if (stristr($filename, 'jpeg') === FALSE && stristr($filename, 'jpg') === FALSE)
				{
					$filename .= '.jpg';
				}

				/** -------------------------------
				/**  Download Image
				/** -------------------------------*/

				$image_url	= $matches['1'][$i];
				$invite		= $matches['3'][$i];

				$r = "\r\n";
				$bits = parse_url($image_url);

				if ( ! isset($bits['path']))
				{
					return FALSE;
				}

				$host = $bits['host'];
				$path = ( ! isset($bits['path'])) ? '/' : $bits['path'];
				$path .= "inviteToken={$invite}";

				if ( ! $fp = @fsockopen ($host, 80))
				{
					continue;
				}

				fputs ($fp, "GET " . $path . " HTTP/1.0\r\n" );
				fputs ($fp, "Host: " . $bits['host'] . "\r\n" );
				fputs ($fp, "Content-type: application/x-www-form-urlencoded\r\n" );
				fputs ($fp, "User-Agent: EE/EllisLab PHP/" . phpversion() . "\r\n");
				fputs ($fp, "Connection: close\r\n\r\n" );

				$this->external_image($fp, $filename);
			}
		}

		if(preg_match_all("#<img\s+src=\s*[\"']http://pictures.sprintpcs.com/+shareImage/(.+?)[\"'](.*?)\s*\>#si", $text, $matches))
		{
			for($i = 0; $i < count($matches['0']); $i++)
			{
				$parts = explode('jpg',$matches['1'][$i]);

				if ( ! isset($parts['1']))
				{
					continue;
				}

				$filename = $parts['0'].'jpg';
				$image_url = 'http://pictures.sprintpcs.com/shareImage/'.$filename;

				$invite = $this->find_data($parts['1'], 'invite=','');

				if ($invite == '')
				{
					$invite = $this->find_data($parts['1'], 'invite=','&');

					if ($invite == '')
					{
						continue;
					}
				}

				/** -------------------------------
				/**  Download Image
				/** -------------------------------*/

				$r = "\r\n";
				$bits = parse_url($image_url);

				if ( ! isset($bits['path']))
				{
					return FALSE;
				}

				$host = $bits['host'];
				$path = ( ! isset($bits['path'])) ? '/' : $bits['path'];
				$data = "invite={$invite}";

				if ( ! $fp = @fsockopen ($host, 80))
				{
					continue;
				}

				fputs ($fp, "GET " . $path . " HTTP/1.0\r\n" );
				fputs ($fp, "Host: " . $bits['host'] . "\r\n" );
				fputs ($fp, "Content-type: application/x-www-form-urlencoded\r\n" );
				fputs ($fp, "User-Agent: EE/EllisLab PHP/" . phpversion() . "\r\n");
				fputs ($fp, "Content-length: " . strlen($data) . "\r\n" );
				fputs ($fp, "Connection: close\r\n\r\n" );
				fputs ($fp, $data);

				$this->external_image($fp, $filename);
			}
		}

		return TRUE;

	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * 	Bell Images
	 *
	 * eg:  <img src="http://mypictures.bell.ca//i/99376001_240.jpg?invite=SELr4RJHhma1cknzLQoU" alt="Retrieving picture..."/>
	 *
	 *	@param string
	 */
	function bell_image($text)
	{
		$text = trim(str_replace($this->newline,"\n",$text));

		if(preg_match_all("#<img\s+src=\"http://mypictures.bell.ca(.*?)\"(.*?)alt=\"Retrieving picture\.\.\.\"(.*?)\/\>#i", $text, $matches))
		{
			for($i = 0; $i < count($matches['0']); $i++)
			{
				$parts = explode('jpg',$matches['1'][$i]);

				if ( ! isset($parts['1']))
				{
					continue;
				}
				else
				{
					$pos = strrpos($parts['0'], '/');

					if ($pos === FALSE)
 					{
 						continue;
 					}
 
 					$parts['0'] = substr($parts['0'], $pos+1, strlen($parts['0'])-$pos-1);
				}


				$filename = $parts['0'].'jpg';
				$image_url = 'http://mypictures.bell.ca'.$matches['1'][$i];

				/** -------------------------------
				/**  Download Image
				/** -------------------------------*/

				$r = "\r\n";
				$bits = parse_url($image_url);

				if ( ! isset($bits['path']))
				{
					return FALSE;
				}

				$host = $bits['host'];
				$path = ( ! isset($bits['path'])) ? '/' : $bits['path'];

				if ( ! $fp = @fsockopen ($host, 80))
				{
					continue;
				}

				fputs ($fp, "GET " . $path.'?'.$bits['query'] . " HTTP/1.0\r\n" );
				fputs ($fp, "Host: " . $bits['host'] . "\r\n" );
				fputs ($fp, "User-Agent: EE/EllisLab PHP/" . phpversion() . "\r\n");
				fputs ($fp, "Connection: close\r\n\r\n" );

				$this->external_image($fp, $filename);
			}
		}

		return TRUE;

	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * 	Get Images from External Server
	 *
	 *	@param string
	 *	@param string
	 */
	function external_image($fp, $filename)
	{
		$data = '';
		$headers = '';
		$getting_headers = TRUE;

		while ( ! feof($fp))
		{
			$line = fgets($fp, 4096);

			if ($getting_headers == FALSE)
			{
				$data .= $line;
			}
			elseif (trim($line) == '')
			{
				$getting_headers = FALSE;
			}
			else
			{
				$headers .= $line;
			}
		}


		/** -------------------------------
		/**  Save Image
		/** -------------------------------*/

		$filename = $this->safe_filename($filename);
		$filename = $this->unique_filename($filename);

		$this->post_data['images'][] = array( 'filename' => $filename);
		$key = count($this->post_data['images']) - 1;

		if ( ! $fp = @fopen($this->upload_path.$filename,FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			$this->message_array[] = 'error_writing_attachment'; //.$this->upload_path.$filename;
			return FALSE;
		}

		@fwrite($fp,$data);
		@fclose($fp);

		@chmod($this->upload_path.$filename, FILE_WRITE_MODE);

		$this->email_files[] = $filename;
		$this->uploads++;

		/** -------------------------------
		/**  Image Resizing
		/** -------------------------------*/
		
		$this->image_resize($filename,$key);

		return TRUE;

	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * 	Strip Apple Double Crap
	 *
	 *	@param string
	 */
	function appledouble($data)
	{
		if (stristr($data, 'boundary=') === FALSE)
		{
			return FALSE;
		}

		$boundary		= "--".$this->find_data($data, "boundary=", $this->newline);
		$boundary		= trim(str_replace('"','',$boundary));
		$boundary		= str_replace("+","\+", $boundary);
		$email_parts	= explode($boundary, $data);

		if (count($email_parts) < 2)
		{
			return FALSE;
		}

		foreach($email_parts as $value)
		{
			$content_type	= $this->find_data($value, "Content-Type:", ";");
			$pieces			= explode('/',trim($content_type));
			$type			= trim($pieces['0']);
			$subtype		= ( ! isset($pieces['1'])) ? '0' : trim($pieces['1']);

			if ($type == 'image' OR $type == 'audio' OR $type == 'video')
			{
				$data = array( 'value' => $value,
								'type' => $type,
								'subtype' => $subtype);

				return $data;
			}
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Check Login
	 */
	function check_login()
	{
		$this->body	= trim($this->body);
		$login		= $this->find_data($this->body, '', $this->newline);

		if ($login == '' OR ! stristr($login,':'))
		{
			$login = $this->find_data($this->body, 'AUTH:', $this->newline);
		}

		if ($login == '' OR ! stristr($login,':'))
		{
			return FALSE;
		}

		$x = explode(":", $login);

		$username = (isset($x['1']) && $x['0'] == 'AUTH') ? $x['1'] : $x['0'];
		$password = (isset($x['2']) && $x['0'] == 'AUTH') ? $x['2'] : $x['1'];

		/** --------------------------------------
		/**  Check Username and Password, First
		/** --------------------------------------*/

		$this->EE->db->select('member_id, group_id');
		$this->EE->db->where('username', $username);
		$this->EE->db->where('password', $this->EE->functions->hash(stripslashes($password)));
		$query = $this->EE->db->get('members');

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		elseif($query->row('group_id')  == '1')
		{
			$this->author	=  $query->row('member_id') ;
			$this->body		= str_replace($login,'',$this->body);
			return TRUE;
		}

		$this->EE->db->where('group_id', $query->row('group_id'));
		$this->EE->db->where('channel_id', $this->moblog_array['moblog_channel_id']);
		$count = $this->EE->db->count_all_results('channel_member_groups');

		if ($count == 0)
		{
			return FALSE;
		}

		$this->author	=  $query->row('member_id') ;
		$this->body		= str_replace($login,'',$this->body);

		return TRUE;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * 	Find Boundary
	 */
	function find_boundary($email_data)
	{
		if (stristr($email_data, 'boundary=') === FALSE)
		{
			return FALSE;
		}
		else
		{
			$this->boundary = "--".$this->find_data($email_data, "boundary=", $this->newline);
			$x = explode(';',$this->boundary);
			$this->boundary = trim(str_replace('"','',$x['0']));

			return TRUE;
		}
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Pop Command.
	 *
	 * 	Send pop command to the server.
	 *
	 *	@param string
	 *	@return string
	 */
	function pop_command($cmd = "")
	{
		if ( ! $this->fp)
		{
			return FALSE;
		}

		if ($cmd != "")
		{
			fwrite($this->fp, $cmd.$this->pop_newline);
		}

		$line = $this->remove_newlines(fgets($this->fp, 1024));

		return $line;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Remove New Lines
	 *
	 *	@param string
	 *	@param	string
	 *	@return string
	 */
	function remove_newlines($str,$replace='')
	{
		if (strpos($str, "\r") !== FALSE OR strpos($str, "\n") !== FALSE)
		{
			$str = str_replace(array("\r\n", "\r", "\n"), $replace, $str);
		}

		return $str;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	ISO Clean
	 *
	 *	@param string
	 *	@return string
	 */
	function iso_clean($str)
	{
		if (stristr($str, '=?') === FALSE)
		{
			return $str;
		}

		// -------------------------------------------------
		//  There exists two functions that do this for us
		//  but they are not available on all servers and some
		//  seem to work better than others I have found. The
		//  base64_decode() method works for many encodings
		//  but I am not sure how well it handles non Latin
		//  characters.
		//
		//  The mb_decode_mimeheader() function seems to trim
		//  any line breaks off the end of the str, so we put
		//  those back because we need it for the Header
		//  matching stuff.  I added it on for the imap_utf8()
		//  function just in case.
		// -------------------------------------------------


		if (function_exists('imap_utf8') && strtoupper($this->EE->config->item('charset')) == 'UTF-8')
		{
			return rtrim(imap_utf8($str))."\r\n";
		}

		if (function_exists('mb_decode_mimeheader'))
		{
			// mb_decode_mimeheader() doesn't replace underscores
			return str_replace('_', ' ', rtrim(mb_decode_mimeheader($str)))."\r\n";
		}

		if (function_exists('iconv_mime_decode'))
		{
			return rtrim(iconv_mime_decode($str))."\r\n";
		}

		if (substr(trim($str), -2) != '?=')
		{
			$str = trim($str).'?=';
		}

		if (preg_match("|\=\?iso\-(.*?)\?[A-Z]{1}\?(.*?)\?\=|i", trim($str), $mime))
		{
			if ($mime['1'] == '8859-1')
			{
				$charHex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');

				for ($z=0, $sz=count($charHex); $z < $sz; ++$z)
				{
					for ($i=0, $si=count($charHex); $i < $si; ++$i)
					{
						$mime['2'] = str_replace('='.$charHex[$z].$charHex[$i], chr(hexdec($charHex[$z].$charHex[$i])), $mime['2']);
					}
				}

				$str = str_replace($mime['0'], $mime['2'], $str);
			}
			else
			{
				$str = str_replace($mime['0'], base64_decode($mime['2']), $str);
			}

			$str = str_replace('_', ' ', $str);
		}

		return ltrim($str);
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Find Data
	 *
	 *	@param	string
	 * 	@param	string
	 *	@param 	string
	 *	@return string
	 */
	function find_data($str, $begin, $end)
	{
		$new = '';

		if ($begin == '')
		{
			$p1 = 0;
		}
		else
		{
			if (strpos(strtolower($str), strtolower($begin)) === FALSE)
			{
				return $new;
			}

			$p1 = strpos(strtolower($str), strtolower($begin)) + strlen($begin);
		}

		if ($end == '')
		{
			$p2 = strlen($str);
		}
		else
		{
			if (strpos(strtolower($str), strtolower($end), $p1) === FALSE)
			{
				return $new;
			}

			$p2 = strpos(strtolower($str), strtolower($end), $p1);
		}

		$new = substr($str, $p1, ($p2-$p1));
		return $new;
	}


	// ------------------------------------------------------------------------
	
	/**
	 * 	Image Properties
	 *
	 */
	function image_properties($file)
	{
		if (function_exists('getimagesize'))
		{
			if ( ! $D = @getimagesize($file))
			{
				return FALSE;
			}

			$parray = array();

			$parray['width']	= $D['0'];
			$parray['height']  = $D['1'];
			$parray['imgtype'] = $D['2'];

			return $parray;
		}

		return FALSE;
	}


	// ------------------------------------------------------------------------
	
	/**
	 * 	Safe Filename
	 *
	 *	@param string
	 *	@return string
	 */
	function safe_filename($str)
	{
		$str = strip_tags(strtolower($str));
		$str = preg_replace('/\&#\d+\;/', "", $str);

		// Use dash as separator

		if ($this->EE->config->item('word_separator') == 'dash')
		{
			$trans = array(
							"_"									=> '-',
							"\&\#\d+?\;"						=> '',
							"\&\S+?\;"						  => '',
							"['\"\?\!*\$\#@%;:,\_=\(\)\[\]]"  	=> '',
							"\s+"								=> '-',
							"\/"								=> '-',
							"[^a-z0-9-_\.]"						=> '',
							"-+"								=> '-',
							"\&"								=> '',
							"-$"								=> '',
							"^_"								=> ''
							);
		}
		else // Use underscore as separator
		{
			$trans = array(
							"-"									=> '_',
							"\&\#\d+?\;"						=> '',
							"\&\S+?\;"						  => '',
							"['\"\?\!*\$\#@%;:,\-=\(\)\[\]]"  => '',
							"\s+"								=> '_',
							"\/"								=> '_',
							"[^a-z0-9-_\.]"						=> '',
							"_+"								=> '_',
							"\&"								=> '',
							"_$"								=> '',
							"^_"								=> ''
							);
		}

		foreach ($trans as $key => $val)
		{
			$str = preg_replace("#".$key."#", $val, $str);
		}

		$str = trim(stripslashes($str));

		return $str;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * 	Resize Images
	 *
	 *	@param string
	 *	@param string
	 *	@return string
	 */
	function image_resize($filename, $key)
	{
		/** --------------------------
		/**  Set Properties for Image
		/** --------------------------*/

		if( ! $properties = $this->image_properties($this->upload_path.$filename))
		{
			$properties = array('width'	  => $this->moblog_array['moblog_image_width'],
								'height'  => $this->moblog_array['moblog_image_height']);
		}

		$this->post_data['images'][$key]['width']  = $properties['width'];
		$this->post_data['images'][$key]['height'] = $properties['height'];

		$this->EE->load->library('image_lib');
		$this->EE->image_lib->clear();

		/** ------------------------------
		/**  Resize Image
		/** ------------------------------*/

		if ($this->moblog_array['moblog_resize_image'] == 'y')
		{
			if ($this->moblog_array['moblog_resize_width'] != 0 OR $this->moblog_array['moblog_resize_height'] != 0)
			{
				// Temp vars
				$resize_width	= $this->moblog_array['moblog_resize_width'];
				$resize_height	= $this->moblog_array['moblog_resize_height'];

				/** ----------------------------
				/**  Calculations based on one side?
				/** ----------------------------*/

				if ($this->moblog_array['moblog_resize_width'] == 0 && $this->moblog_array['moblog_resize_height'] != 0)
				{
					// Resize based on height, calculate width
					$resize_width = ceil(($this->moblog_array['moblog_resize_height']/$properties['height']) * $properties['width']);
				}
				elseif ($this->moblog_array['moblog_resize_width'] != 0 && $this->moblog_array['moblog_resize_height'] == 0)
				{
					// Resize based on width, calculate height
					$resize_height = ceil(($this->moblog_array['moblog_resize_width']/$properties['width']) * $properties['height']);
				}

				$config = array(
						'resize_protocol'	=> $this->EE->config->item('image_resize_protocol'),
						'libpath'			=> $this->EE->config->item('image_library_path'),
						'source_image'		=> $this->upload_path.$filename,
						'quality'			=> '90',
						'width'				=> $resize_width,
						'height'			=> $resize_height
				);

				$this->EE->image_lib->initialize($config);

				if ($this->EE->image_lib->resize() === FALSE)
				{
					$this->message_array[] = 'unable_to_resize';
					$this->message_array = array_merge($this->message_array,$this->EE->image_lib->error_msg);
					return FALSE;
				}

				$this->post_data['images'][$key]['width']  = $this->EE->image_lib->width;
				$this->post_data['images'][$key]['height'] = $this->EE->image_lib->height;

				if( ! $properties = $this->image_properties($this->upload_path.$filename))
				{
					$properties = array('width'	  => $this->EE->image_lib->width,
										'height'  => $this->EE->image_lib->height);
				}
			}
		}

		/** ------------------------------
		/**  Create Thumbnail
		/** ------------------------------*/

		if ($this->moblog_array['moblog_create_thumbnail'] == 'y')
		{
			if ($this->moblog_array['moblog_thumbnail_width'] != 0 OR $this->moblog_array['moblog_thumbnail_height'] != 0)
			{
				// Temp vars
				$resize_width	= $this->moblog_array['moblog_thumbnail_width'];
				$resize_height	= $this->moblog_array['moblog_thumbnail_height'];

				/** ----------------------------
				/**  Calculations based on one side?
				/** ----------------------------*/

				if ($this->moblog_array['moblog_thumbnail_width'] == 0 && $this->moblog_array['moblog_thumbnail_height'] != 0)
				{
					// Resize based on height, calculate width
					$resize_width = ceil(($this->moblog_array['moblog_thumbnail_height']/$properties['height']) * $properties['width']);
				}
				elseif ($this->moblog_array['moblog_thumbnail_width'] != 0 && $this->moblog_array['moblog_thumbnail_height'] == 0)
				{
					// Resize based on width, calculate height
					$resize_height = ceil(($this->moblog_array['moblog_thumbnail_width']/$properties['width']) * $properties['height']);
				}
				
				$this->EE->image_lib->clear();

				$config = array(
					'resize_protocol'	=> $this->EE->config->item('image_resize_protocol'),
					'libpath'			=> $this->EE->config->item('image_library_path'),
					'source_image'		=> $this->upload_path.$filename,
					'thumb_prefix'		=> 'thumb',
					'quality'			=> '90',
					'width'				=> $resize_width,
					'height'			=> $resize_height					
					);
				
				$this->EE->image_lib->initialize($config);

				if ($this->EE->image_lib->resize() === FALSE)
				{
					$this->message_array[] = 'unable_to_resize';
					$this->message_array = array_merge($this->message_array,$this->EE->image_lib->error_msg);
					return FALSE;
				}

				$name = substr($filename, 0, strpos($filename, "."));
				$ext  = substr($filename, strpos($filename, "."));

				$this->post_data['images'][$key]['thumbnail']  = $name.'_thumb'.$ext;
				$this->post_data['images'][$key]['thumb_width']  = $resize_width;
				$this->post_data['images'][$key]['thumb_height'] = $resize_height;
				$this->email_files[] = $name.'_thumb'.$ext;
				$this->uploads++;

			}	// End thumbnail resize conditional
		}	// End thumbnail 
  	} 

	// ------------------------------------------------------------------------
	
	/**
	 * 	Unique Filename
	 *
	 *	@param string
	 *	@param string
	 *	@return string
	 */
  	function unique_filename($filename, $subtype='0')
  	{
  		$i = 0;
  
  		$subtype = ($subtype != '0') ? '.'.$subtype : '';
  
  		/** ----------------------------
  		/**  Strips out _ and - at end of name part of file name
  		/** ----------------------------*/
  		$x			= explode('.',$filename);
		$name		=  ( ! isset($x['1'])) ? $filename : $x['0'];
		$sfx		=  ( ! isset($x['1']) OR is_numeric($x[count($x) - 1])) ? $subtype : '.'.$x[count($x) - 1];
		$name		=  (substr($name,-1) == '_' OR substr($name,-1) == '-') ? substr($name,0,-1) : $name;
  		$filename	= $name.$sfx;
  
		while (file_exists($this->upload_path.$filename))
		{
			$i++;
			$n			= - strlen($i);
			$x			= explode('.',$filename);
			$name		=  ( ! isset($x['1'])) ? $filename : $x['0'];
			$sfx		=  ( ! isset($x['1'])) ? '' : '.'.$x[count($x) - 1];
			$name		=  ($i==1) ? $name : substr($name,0,$n);
			$name		=  (substr($name,-1) == '_' OR substr($name,-1) == '-') ? substr($name,0,-1) : $name;
			$filename	=  $name."$i".$sfx;
		}

		return $filename;
	}

}
// END CLASS

/* End of file mod.moblog.php */
/* Location: ./system/expressionengine/modules/moblog/mod.moblog.php */
