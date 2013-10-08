<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Language Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Lang extends CI_Lang {
	
	var $user_lang = '';
	
	// --------------------------------------------------------------------

	/**
	 * Add a language file to the main language array
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function loadfile($which = '', $package = '', $show_errors = TRUE)
	{
		if ($which == '')
		{
			return;
		}
	
		$EE =& get_instance();
		
		if (isset($EE->session->userdata['language']) && $EE->session->userdata['language'] != '')
		{
			$this->user_lang = $EE->session->userdata['language'];
		}
		else
		{
			if ($EE->input->cookie('language'))
			{
				$this->user_lang = $EE->input->cookie('language');
			}
			elseif ($EE->config->item('deft_lang') != '')
			{
				$this->user_lang = $EE->config->item('deft_lang');
			}
			else
			{
				$this->user_lang = 'english';
			}
		}

		$deft_lang = ( ! $EE->config->item('language')) ? 'english' : $EE->config->item('language');
		
		// Sec.ur.ity code.  ::sigh::
		$package = ($package == '') ? $EE->security->sanitize_filename(str_replace(array('lang.', '.php'), '', $which)) : $EE->security->sanitize_filename($package);
		$which = str_replace('lang.', '', $which);
		$this->user_lang = $EE->security->sanitize_filename($this->user_lang);
	
		if ($which == 'sites_cp')
		{			
			$phrase = 'base'.'6'.'4_d'.'ecode';

			eval($phrase(preg_replace("|\s+|is", '', "JEVFLT5sb2FkLT5saWJyYXJ5KCJzaXRlcyIpOyRFRV9TaXRlcyA9IG5ldyB
			FRV9TaXRlcygpOyAkc3RyaW5nID0gYmFzZTY0X2RlY29kZSgkRUVfU2l0ZXMtPnRoZV9zaXRlc19hbGxvd2VkLiRFRV9TaXRlcy0
			+bnVtX3NpdGVzX2FsbG93ZWQuJEVFX1NpdGVzLT5zaXRlc19hbGxvd2VkX251bSk7ICRoYXNoID0gbWQ1KCJNU00gQnkgRWxsaXN
			MYWIiKTsgZm9yICgkaSA9IDAsICRzdHIgPSAiIjsgJGkgPCBzdHJsZW4oJHN0cmluZyk7ICRpKyspIHsgJHN0ciAuPSBzdWJzdHI
			oJHN0cmluZywgJGksIDEpIF4gc3Vic3RyKCRoYXNoLCAoJGkgJSBzdHJsZW4oJGhhc2gpKSwgMSk7IH0gJHN0cmluZyA9ICRzdHI
			7IGZvciAoJGkgPSAwLCAkZGVjID0gIiI7ICRpIDwgc3RybGVuKCRzdHJpbmcpOyAkaSsrKSB7ICRkZWMgLj0gKHN1YnN0cigkc3R
			yaW5nLCAkaSsrLCAxKSBeIHN1YnN0cigkc3RyaW5nLCAkaSwgMSkpOyB9ICRhbGxvd2VkID0gc3Vic3RyKGJhc2U2NF9kZWNvZGU
			oc3Vic3RyKGJhc2U2NF9kZWNvZGUoc3Vic3RyKGJhc2U2NF9kZWNvZGUoc3Vic3RyKCRkZWMsMikpLDUpKSw0KSksMik7ICRxdWV
			yeSA9ICRFRS0+ZGItPnF1ZXJ5KCJTRUxFQ1QgQ09VTlQoKikgQVMgY291bnQgRlJPTSBleHBfc2l0ZXMiKTsgaWYgKCAhIGlzX25
			1bWVyaWMoJGFsbG93ZWQpIE9SICRxdWVyeS0+cm93KCJjb3VudCIpID49ICRhbGxvd2VkKSB7ICR0aGlzLT5sYW5ndWFnZVsiY3J
			lYXRlX25ld19zaXRlIl0gPSAiIjsgaWYgKGlzc2V0KCRfR0VUWyJNIl0pICYmIGluX2FycmF5KCRfR0VUWyJNIl0sIGFycmF5KCJ
			hZGRfZWRpdF9zaXRlIiwgInVwZGF0ZV9zaXRlIikpICYmICEgJEVFLT5pbnB1dC0+Z2V0X3Bvc3QoInNpdGVfaWQiKSkgeyBkaWU
			oIk11bHRpcGxlIFNpdGUgTWFuYWdlciBFcnJvciAtIFNpdGUgTGltaXQgUmVhY2hlZCIpOyB9IH0="))); return;
		}

		$this->load($which, $this->user_lang, FALSE, TRUE, PATH_THIRD.$package.'/', $show_errors);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Load a language file
	 *
	 * Differs from CI's Lang::load() in that it checks each file for a default language version
	 * as a backup.  Not sure this is appropriate for CI at large.
	 * 
	 * @access	public
	 * @param	mixed	the name of the language file to be loaded. Can be an array
	 * @param	string	the language (english, etc.)
	 * @return	mixed
	 */
	function load($langfile = '', $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '', $show_errors = TRUE)
	{
		static $deft_lang;
		
		$langfile = str_replace('.php', '', $langfile);

		if ($add_suffix == TRUE)
		{
			$langfile = str_replace('_lang.', '', $langfile).'_lang';
		}

		$langfile .= '.php';

		if (in_array($langfile, $this->is_loaded, TRUE))
		{
			return;
		}

		if ( ! isset($deft_lang))
		{
			$config =& get_config();
			$deft_lang = ( ! isset($config['language'])) ? 'english' : $config['language'];			
		}
	
		if ($idiom == '')
		{
			$idiom = ($this->user_lang == '') ? 'english' : $this->user_lang;
		}

		// figure out where the lang file is, checking for the requested
		// idiom first, then falling back on the default language
		$paths = array(
						APPPATH.'language/'.$idiom.'/'.$langfile,
						APPPATH.'language/'.$deft_lang.'/'.$langfile,
						BASEPATH.'language/'.$idiom.'/'.$langfile,
						BASEPATH.'language/'.$deft_lang.'/'.$langfile
					);
		
		// if it's in an alternate location, such as a package, check there first
		if ($alt_path != '')
		{
			// Temporary! Rename your language files!
			$third_party_old = 'lang.'.str_replace('_lang.', '.', $langfile);
			
			array_unshift($paths, $alt_path.'language/'.$deft_lang.'/'.$third_party_old);
			array_unshift($paths, $alt_path.'language/'.$idiom.'/'.$third_party_old);
			
			array_unshift($paths, $alt_path.'language/'.$deft_lang.'/'.$langfile);
			array_unshift($paths, $alt_path.'language/'.$idiom.'/'.$langfile);
		}
		
		// if idiom and deft_lang are the same, don't check those paths twice
		$paths = array_unique($paths);
		
		$success = FALSE;
		
		foreach($paths as $path)
		{
			if (file_exists($path) && include $path)
			{
				$success = TRUE;
				break;
			}
		}
		
		if ($show_errors && $success !== TRUE)
		{
			show_error('Unable to load the requested language file: language/'.$idiom.'/'.$langfile);			
		}

		if ( ! isset($lang))
		{
			log_message('error', 'Language file contains no data: language/'.$idiom.'/'.$langfile);
			return;
		}

		if ($return == TRUE)
		{
			return $lang;
		}

		$this->is_loaded[] = $langfile;
		$this->language = array_merge($this->language, $lang);
		unset($lang);

		log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 *   Fetch a specific line of text
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function line($which = '', $label = '')
	{
		if ($which != '')
		{
			$EE =& get_instance();
		
			$line = ( ! isset($this->language[$which])) ? $which : $this->language[$which];					
			
			if ($label != '')
			{
				$line = '<label for="'.$label.'">'.$line."</label>";
			}
			
			return stripslashes($line);
		}
	}

}
// END CLASS

/* End of file EE_Lang.php */
/* Location: ./system/expressionengine/libraries/EE_Lang.php */